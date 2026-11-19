<?php

namespace SilverShop\Geocoding\Extensions;

use Geocoder\Geocoder;
use Geocoder\Provider\Chain\Chain;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Provider\Provider;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as Guzzle7Adapter;
use Psr\Log\LoggerInterface;
use SilverShop\Model\Address;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use Throwable;

/**
 * Adds latitude/longitude to {@link Address} and geocodes the address on save.
 *
 * Default provider is Nominatim (OpenStreetMap), which is keyless and works out of the box. When a
 * `google_maps_api_key` is configured, Google Maps is chained ahead of Nominatim for higher-quality
 * results. Supply any other geocoder at runtime with {@link set_geocoder()}.
 *
 * @extends Extension<Address>
 */
class AddressGeocoding extends Extension
{
    private static $db = [
        'Latitude' => 'Decimal(10,8)', //-90 to 90 degrees
        'Longitude' => 'Decimal(11,8)' //-180 to 180 degrees
    ];

    /**
     * Whether addresses are geocoded automatically on write.
     */
    private static bool $enable_geocoding = true;

    /**
     * Optional Google Maps API key. When set, Google Maps is used ahead of Nominatim.
     */
    private static ?string $google_maps_api_key = null;

    /**
     * User-Agent sent to Nominatim. The public OpenStreetMap server requires a meaningful value that
     * identifies your application, e.g. your site's domain.
     */
    private static string $geocoder_user_agent = 'silvershop/geocoding';

    /**
     * Nominatim server root. Defaults to the public OpenStreetMap server (fair-use: <= 1 req/s). Point
     * this at your own instance for production/bulk use.
     */
    private static string $nominatim_server = 'https://nominatim.openstreetmap.org';

    private static ?Geocoder $inst = null;

    /**
     * Developer-registered extra providers, prepended to the default chain. @see add_provider()
     *
     * @var Provider[]
     */
    private static array $providers = [];

    /**
     * Get the configured geocoder. Chain order: any developer-registered providers first, then Google
     * Maps (when an API key is set), then keyless Nominatim (OSM) as the default fallback.
     */
    public static function get_geocoder(): Geocoder
    {
        if (self::$inst) {
            return self::$inst;
        }

        $config = Address::config();
        $guzzle = new GuzzleClient([
            'timeout' => 5.0,
        ]);
        $adapter = new Guzzle7Adapter($guzzle);
        $userAgent = (string) $config->get('geocoder_user_agent') ?: 'silvershop/geocoding';

        $providers = self::$providers;

        $apiKey = $config->get('google_maps_api_key');
        if ($apiKey) {
            $providers[] = new GoogleMaps($adapter, null, $apiKey);
        }

        $server = (string) $config->get('nominatim_server') ?: 'https://nominatim.openstreetmap.org';
        $providers[] = $server === 'https://nominatim.openstreetmap.org'
            ? Nominatim::withOpenStreetMapServer($adapter, $userAgent)
            : new Nominatim($adapter, $server, $userAgent);

        $geocoder = new ProviderAggregator();
        $geocoder->registerProvider(count($providers) > 1 ? new Chain($providers) : $providers[0]);

        return self::$inst = $geocoder;
    }

    /**
     * Replace the geocoder entirely with your own (highest level of control).
     */
    public static function set_geocoder(?Geocoder $geocoder): void
    {
        self::$inst = $geocoder;
    }

    /**
     * Register an extra provider to prepend to the default chain, so a developer can add their own
     * preferred geocoder (e.g. Mapbox, HERE, a local PDOK provider) without replacing the defaults.
     * Call this from a bootstrap, e.g. a module `_config.php`.
     */
    public static function add_provider(Provider $provider): void
    {
        self::$providers[] = $provider;
        self::$inst = null; // force a rebuild so the new provider is picked up
    }

    /**
     * Clear any providers registered via {@link add_provider()} (chiefly for tests).
     */
    public static function clear_providers(): void
    {
        self::$providers = [];
        self::$inst = null;
    }

    public function onBeforeWrite(): void
    {
        if (!$this->owner->Latitude && !$this->owner->Longitude && Address::config()->get('enable_geocoding')) {
            $this->geocodeAddress();
        }
    }

    public function geocodeAddress(): void
    {
        $geocoder = self::get_geocoder();

        try {
            $geocoded = $geocoder->geocodeQuery(GeocodeQuery::create($this->owner->toString()));
            if (!$geocoded->isEmpty() && $point = $geocoded->first()->getCoordinates()) {
                $this->owner->Latitude = $point->getLatitude();
                $this->owner->Longitude = $point->getLongitude();
            }
        } catch (Throwable $e) {
            Injector::inst()->get(LoggerInterface::class)->notice(
                'Address geocoding failed',
                ['exception' => $e]
            );
        }
    }

    /**
     * Provide distance (in km) to given address.
     * Returns null if inadequate info present.
     *
     * @param Address $address Address to measure distance to
     * @return float|null distance in km
     */
    public function distanceTo(Address $address): ?float
    {
        if (!$this->owner->Latitude ||
            !$this->owner->Longitude ||
            !$address->Latitude ||
            !$address->Longitude
        ) {
            return null;
        }

        return self::haversine_distance(
            $this->owner->Latitude,
            $this->owner->Longitude,
            $address->Latitude,
            $address->Longitude
        ) / 1000; //convert meters to km
    }

    /**
     * Calculates the great-circle distance between two points, with the Haversine formula.
     *
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function haversine_distance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ): float {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}

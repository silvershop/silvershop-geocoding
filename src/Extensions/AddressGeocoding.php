<?php

namespace SilverShop\Geocoding\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverShop\Model\Address;
use BetterBrief\GoogleMapField;
use SilverStripe\Dev\SapphireTest;
use Exception;

class AddressGeocoding extends DataExtension
{
    private static $db = [
        'Latitude' => 'Decimal(10,8)', //-90 to 90 degrees
        'Longitude' => 'Decimal(11,8)' //-180 to 180 degrees
    ];

    private static $inst;

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            "Root.Main",
            GoogleMapField::create($this->owner, "Location", [
                'fieldNames' => [
                    'lat' => 'Latitude',
                    'lng' => 'Longitude'
                ],
                'showSearchBox' => false
            ])
        );
        $fields->removeByName("Latitude");
        $fields->removeByName("Longitude");
    }

    /**
     * Get the configured geocoder.
     * Configures freegeoip, hostip, and googlemaps providers by default.
     * @return \Geocoder\Geocoder Geocoder
     */
    public static function get_geocoder()
    {
        if (self::$inst) {
            return self::$inst;
        }

        $geocoder = new \Geocoder\ProviderAggregator();
        $guzzle = new \GuzzleHttp\Client([
            'timeout' => 2.0,
            'verify' => false,
        ]);

        $adapter  = new \Http\Adapter\Guzzle7\Client($guzzle);
        $chain = new \Geocoder\Provider\Chain\Chain([
            new \Geocoder\Provider\FreeGeoIp\FreeGeoIp($adapter),
            new \Geocoder\Provider\HostIp\HostIp($adapter),
            new \Geocoder\Provider\GoogleMaps\GoogleMaps($adapter),
        ]);

        $geocoder->registerProvider($chain);

        return self::$inst = $geocoder;
    }

    public function set_geocoder(\Geocoder\Geocoder $geocoder)
    {
        self::$inst = $geocoder;
    }

    public function onBeforeWrite()
    {
        if (!$this->owner->Latitude && !$this->owner->Longitude && Address::config()->enable_geocoding) {
            $this->geocodeAddress();
        }
    }

    public function geocodeAddress()
    {
        $geocoder = self::get_geocoder();

        try {
            $geocoded = $geocoder->geocode($this->owner->toString());
            if ($geocoded->first() && $point = $geocoded->first()->getCoordinates()) {
                $this->owner->Latitude = $point->getLatitude();
                $this->owner->Longitude = $point->getLongitude();
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Provide distance (in km) to given address.
     * Returns null if inadequate info present.
     *
     * @param  Address $address Address to measure distance to
     * @return float|null distance in km
     */
    public function distanceTo(Address $address)
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
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
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
    ) {
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

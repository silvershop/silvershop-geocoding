<?php

/**
 * @package silvershop-geocoding
 */
class AddressGeocoding extends DataExtension
{

    private static $db = array(
        'Latitude' => 'Decimal(10,8)', //-90 to 90 degrees
        'Longitude' => 'Decimal(11,8)' //-180 to 180 degrees
    );

    private static $inst;

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab("Root.Main",
            GoogleMapField::create($this->owner, "Location", array(
                'fieldNames' => array(
                    'lat' => 'Latitude',
                    'lng' => 'Longitude'
                ),
                'showSearchBox' => false
            ))
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
        $geocoder = new \Geocoder\Geocoder();
        $adapter  = new \Geocoder\HttpAdapter\CurlHttpAdapter();
        $chain = new \Geocoder\Provider\ChainProvider(array(
            new \Geocoder\Provider\HostIpProvider($adapter),
            new \Geocoder\Provider\GoogleMapsProvider($adapter)
        ));
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
        //we don't want geocoding to occur during testing
        //TODO: we could possibly switch to a mock setup
        if (class_exists('SapphireTest', false) && SapphireTest::is_running_test()) {
            return;
        }
        //TODO: check if address is valid
        $geocoder = self::get_geocoder();
        try {
            $geocoded = $geocoder->geocode($this->owner->toString());
            $this->owner->Latitude = $geocoded->getLatitude();
            $this->owner->Longitude = $geocoded->getLongitude();
        } catch (Exception $e) {
            SS_Log::log($e, SS_Log::ERR);
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
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000
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

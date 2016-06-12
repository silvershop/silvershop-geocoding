<?php

/**
 * @package silvershop-geocoding
 */
class GeocodedUserInfo extends DataExtension
{

    public function contentcontrollerInit()
    {
        $location = ShopUserInfo::singleton()->getAddress();
        $autocode = Page::config()->geocode_visitor_ip;
        if ((!$location && $autocode) || Controller::curr()->getRequest()->getVar('relocateuser')) {
            ShopUserInfo::singleton()->setAddress(new Address($this->findLocation()));
        }
    }

    protected function findLocation()
    {
        $ip = Controller::curr()->getRequest()->getIP();
        //TODO:what to do if there is no ip?
        //rewrite localhost to a testing ip
        if (in_array($ip, array( '127.0.0.1', '::1' ))) {
            $ip = Address::config()->test_ip;
        }
        return $this->addressFromIP($ip);
    }

    protected function addressFromIP($ip)
    {
        $geocoder = AddressGeocoding::get_geocoder();
        $geodata = array();
        try {
            if ($ip) {
                $geodata = $geocoder->geocode($ip)->toArray();
            }
        } catch (Exception $e) {
            SS_Log::log($e, SS_Log::ERR);
        }
        $geodata = array_filter($geodata);
        $datamap = array(
            'Country' => 'countryCode',
            'County' => 'county',
            'State' => 'region',
            'PostalCode' => 'zipcode',
            'Latitude' => 'latitude',
            'Longitude' => 'longitude'
        );
        $mappeddata = array();
        foreach ($datamap as $addressfield => $geofield) {
            if (is_array($geofield)) {
                if ($data = implode(" ", array_intersect_key($geodata, array_combine($geofield, $geofield)))) {
                    $mappeddata[$addressfield] = $data;
                }
            } elseif (isset($geodata[$geofield])) {
                $mappeddata[$addressfield] = $geodata[$geofield];
            }
        }

        return $mappeddata;
    }
}

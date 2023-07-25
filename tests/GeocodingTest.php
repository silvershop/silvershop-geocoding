<?php

namespace SilverShop\Geocoding\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverShop\Model\Address;

class GeocodingTest extends SapphireTest
{

    public static $fixture_file = 'addresses.yml';

    public function testAddressModel()
    {
        $address = $this->objFromFixture(Address::class, "address1");
        $this->assertEquals(174.77908, $address->Longitude);
        $this->assertEquals(-41.292915, $address->Latitude);
    }

    public function testAddressDistanceTo()
    {
        $from = $this->objFromFixture(Address::class, "address1");
        $to = $this->objFromFixture(Address::class, "address2");
        $this->assertEquals(0, $from->distanceTo($from));
        $this->assertEquals(0, $to->distanceTo($to));
        $this->assertEquals(494.42414833321, round($from->distanceTo($to), 11));
        $this->assertEquals(494.42414833321, round($to->distanceTo($from), 11));
    }
}

<?php

class GeocodingTest extends SapphireTest
{

    public static $fixture_file = 'shop_geocoding/tests/addresses.yml';
    
    public function testAddressModel()
    {
        $address = $this->objFromFixture("Address", "address1");
        $this->assertEquals(174.77908, $address->Longitude);
        $this->assertEquals(-41.292915, $address->Latitude);
    }

    public function testAddressDistanceTo()
    {
        $from = $this->objFromFixture("Address", "address1");
        $to = $this->objFromFixture("Address", "address2");
        $this->assertEquals(0, $from->distanceTo($from));
        $this->assertEquals(0, $to->distanceTo($to));
        $this->assertEquals(494.42414833321, $from->distanceTo($to));
        $this->assertEquals(494.42414833321, $to->distanceTo($from));
    }
}

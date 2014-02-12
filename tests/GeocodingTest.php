<?php

class GeocodingTest extends SapphireTest{

	public static $fixture_file = 'shop_geocoding/tests/addresses.yml';
	
	function testAddressModel() {
		$address = $this->objFromFixture("Address", "address1");
		$this->assertEquals(174.77908, $address->Longitude);
		$this->assertEquals(-41.292915, $address->Latitude);
	}

}
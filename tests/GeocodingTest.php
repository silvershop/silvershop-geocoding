<?php

use SilverShop\Model\Address;
use SilverStripe\Dev\SapphireTest;

class GeocodingTest extends SapphireTest
{

	protected static $fixture_file = 'shop_geocoding/tests/addresses.yml';
	
	public function testAddressModel(): void
	{
		$address = $this->objFromFixture(Address::class, "address1");
		$this->assertEquals(174.77908, $address->Longitude);
		$this->assertEquals(-41.292915, $address->Latitude);
	}

	public function testAddressDistanceTo(): void
	{
		$from = $this->objFromFixture(Address::class, "address1");
		$to = $this->objFromFixture(Address::class, "address2");
		$this->assertEquals(0, $from->distanceTo($from));
		$this->assertEquals(0, $to->distanceTo($to));
		$this->assertEqualsWithDelta(494.42414833321, $from->distanceTo($to), 0.000001);
		$this->assertEqualsWithDelta(494.42414833321, $to->distanceTo($from), 0.000001);
	}

}
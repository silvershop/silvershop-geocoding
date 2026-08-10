<?php

namespace SilverShop\Geocoding\Tests;

use Error;
use Psr\Log\LoggerInterface;
use SilverShop\Geocoding\Extensions\AddressGeocoding;
use SilverShop\Geocoding\Extensions\GeocodedUserInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverShop\Model\Address;

class GeocodingTest extends SapphireTest
{

    public static $fixture_file = 'addresses.yml';

    protected function tearDown(): void
    {
        $this->setGeocoderInstance(null);
        parent::tearDown();
    }

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

    public function testAddressGeocodingLogsQuietlyOnFailure()
    {
        $originalLogger = Injector::inst()->get(LoggerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with(
                'Address geocoding failed',
                $this->callback(function ($context) {
                    return isset($context['exception']) && $context['exception'] instanceof Error;
                })
            );
        Injector::inst()->registerService($logger, LoggerInterface::class);

        try {
            $this->setGeocoderInstance($this->createThrowingGeocoder());
            $address = $this->objFromFixture(Address::class, "address1");
            $address->Latitude = null;
            $address->Longitude = null;
            $address->geocodeAddress();
        } finally {
            Injector::inst()->registerService($originalLogger, LoggerInterface::class);
        }
    }

    public function testIpGeocodingLogsQuietlyOnFailure()
    {
        $originalLogger = Injector::inst()->get(LoggerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with(
                'IP geocoding failed',
                $this->callback(function ($context) {
                    return isset($context['exception']) && $context['exception'] instanceof Error;
                })
            );
        Injector::inst()->registerService($logger, LoggerInterface::class);

        try {
            $this->setGeocoderInstance($this->createThrowingGeocoder());
            $extension = new class extends GeocodedUserInfo {
                public function lookupAddressFromIP($ip)
                {
                    return $this->addressFromIP($ip);
                }
            };
            $this->assertSame([], $extension->lookupAddressFromIP('8.8.8.8'));
        } finally {
            Injector::inst()->registerService($originalLogger, LoggerInterface::class);
        }
    }

    private function createThrowingGeocoder()
    {
        return new class {
            public function geocode($query)
            {
                throw new Error('Geocoding service unavailable');
            }
        };
    }

    private function setGeocoderInstance($geocoder): void
    {
        $instanceProperty = new \ReflectionProperty(AddressGeocoding::class, 'inst');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $geocoder);
    }
}

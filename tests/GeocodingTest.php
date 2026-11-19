<?php

namespace SilverShop\Geocoding\Tests;

use Geocoder\Collection;
use Geocoder\Geocoder;
use Geocoder\Model\Address as GeoAddress;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Log\LoggerInterface;
use SilverShop\Geocoding\Extensions\AddressGeocoding;
use SilverShop\Model\Address;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class GeocodingTest extends SapphireTest
{
    protected static $fixture_file = 'addresses.yml';

    protected function tearDown(): void
    {
        AddressGeocoding::set_geocoder(null);
        AddressGeocoding::clear_providers();
        parent::tearDown();
    }

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
        $this->assertNull(Address::create()->distanceTo($from), 'Missing coordinates should yield null');
        $this->assertEquals(0.0, $from->distanceTo($from));
        $this->assertEquals(0.0, $to->distanceTo($to));
        $this->assertEqualsWithDelta(494.42414833321, $from->distanceTo($to), 0.00001);
        $this->assertEqualsWithDelta(494.42414833321, $to->distanceTo($from), 0.00001);
    }

    public function testAddProviderIsUsed(): void
    {
        AddressGeocoding::add_provider($this->stubProvider(1.23, 4.56));

        $address = Address::create(['Country' => 'NZ', 'Address' => 'Somewhere']);
        $address->geocodeAddress();

        $this->assertEqualsWithDelta(1.23, (float) $address->Latitude, 0.00001);
        $this->assertEqualsWithDelta(4.56, (float) $address->Longitude, 0.00001);
    }

    public function testAddressGeocodingLogsQuietlyOnFailure(): void
    {
        $originalLogger = Injector::inst()->get(LoggerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with(
                'Address geocoding failed',
                $this->callback(fn ($context) => isset($context['exception'])
                    && $context['exception'] instanceof \Throwable)
            );
        Injector::inst()->registerService($logger, LoggerInterface::class);

        try {
            AddressGeocoding::set_geocoder($this->throwingGeocoder());
            $address = $this->objFromFixture(Address::class, "address1");
            $address->Latitude = null;
            $address->Longitude = null;
            $address->geocodeAddress();
        } finally {
            Injector::inst()->registerService($originalLogger, LoggerInterface::class);
        }
    }

    /**
     * A provider that returns fixed coordinates for any query, to prove add_provider() is honoured.
     */
    private function stubProvider(float $lat, float $lng): Provider
    {
        return new class ($lat, $lng) implements Provider {
            public function __construct(private float $lat, private float $lng)
            {
            }

            public function geocodeQuery(GeocodeQuery $query): Collection
            {
                return new AddressCollection([
                    GeoAddress::createFromArray([
                        'providedBy' => 'stub',
                        'latitude' => $this->lat,
                        'longitude' => $this->lng,
                    ]),
                ]);
            }

            public function reverseQuery(ReverseQuery $query): Collection
            {
                return new AddressCollection([]);
            }

            public function getName(): string
            {
                return 'stub';
            }
        };
    }

    /**
     * A geocoder whose lookups always throw, to exercise the quiet-failure logging path.
     */
    private function throwingGeocoder(): Geocoder
    {
        return new class extends ProviderAggregator {
            public function geocodeQuery(GeocodeQuery $query): Collection
            {
                throw new \RuntimeException('Geocoding service unavailable');
            }
        };
    }
}

# SilverStripe Shop Geocoding

Adds geocoding support to the shop:

 * Work out address coordinates, based on entered address.

Make use of the [geocoder-php/Geocoder](https://github.com/geocoder-php/Geocoder) library.

## Configuration

A default configuration is provided, but you can define your own.

In _config.php:
```php
$geocoder = new \Geocoder\Geocoder();
$adapter  = new \Geocoder\HttpAdapter\CurlHttpAdapter();
$geocoder->registerProvider(
	new \Geocoder\Provider\ChainProvider(array(
		new \Geocoder\Provider\FreeGeoIpProvider($adapter),
		new \Geocoder\Provider\HostIpProvider($adapter),
		new \Geocoder\Provider\GoogleMapsProvider($adapter)
	))
);
AddressGeocoding::set_geocoder($geocoder);
```

To test ips locally, you can configure a 'test ip' in your config:

```yaml
Address:
  test_ip: 202.160.48.114
```

Add `relocateuser=1` to a url to rerun the geocoder.

## TODO
 
 * Work out address from map coordinates.
 * Provide a way to view/set location via map interface

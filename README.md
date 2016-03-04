# SilverShop Geocoding

Adds geocoding support to SilverShop. Work out address coordinates, based on entered address.

Makes use of the [geocoder-php/Geocoder](https://github.com/geocoder-php/Geocoder) library.

## Installation

```sh
composer reqire silvershop/geocoding
```

## Configuration

A default configuration is provided, but you can define your own.

In _config.php:
```php
$geocoder = new \Geocoder\Geocoder();
$adapter  = new \Geocoder\HttpAdapter\CurlHttpAdapter();
$geocoder->registerProvider(
	new \Geocoder\Provider\ChainProvider(array(
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

### Disable address coordinates geocoding

By default an address's latitude and longitude is automatically retrieved on save if it has not already been worked out.
This behavior can be disabled like this:

```yaml
Address:
  enable_geocoding: false
```

### Disable automatic visitor ip geocoding

By default this module geocodes the ip of every visitor. This behaviour can be disabled like this:

```yaml
Page:
  geocode_visitor_ip: false
```

## Warning

Relying on 3rd-party geocoding services can potentially slow down your website, especially if the external service
comes under heavy load. You may want to consider setting up your own geocoding server instance.

# Map fall back

If an address can't be geocoded, then provide a fallback checkout step for designating the coordinates with a google
map field.

Be sure to add the checkout step to yaml config. After billing address will probably work best:

```yaml
CheckoutPage:
  steps:
    'membership' : 'CheckoutStep_Membership'
    'contactdetails' : 'CheckoutStep_ContactDetails'
    'shippingaddress' : 'CheckoutStep_Address'
    'billingaddress' : 'CheckoutStep_Address'
    'addresslocation' : 'CheckoutStep_AddressLocationFallback' #here
    'shippingmethod' : 'CheckoutStep_ShippingMethod'
    'paymentmethod' : 'CheckoutStep_PaymentMethod'
    'summary' : 'CheckoutStep_Summary'
```

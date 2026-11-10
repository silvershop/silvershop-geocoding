# SilverShop Geocoding

Adds geocoding support to [SilverShop](https://github.com/silvershop/silvershop-core):

* Works out an address's coordinates (latitude/longitude) from the entered address, automatically on save.
* Provides a `distanceTo()` helper to measure the great-circle distance between two addresses.

Built on the [geocoder-php/Geocoder](https://github.com/geocoder-php/Geocoder) library. The default
provider is [Nominatim](https://nominatim.org/) (OpenStreetMap), which is keyless and works out of the
box. Google Maps is used automatically when an API key is configured.

## Requirements

* SilverStripe CMS 6
* SilverShop core ^6
* PHP 8.2+

## Installation

```sh
composer require silvershop/geocoding
```

Then run `dev/build?flush=1`.

## Configuration

All settings live on `SilverShop\Model\Address`.

### Disable automatic geocoding

By default an address's latitude and longitude are worked out on save if they have not been set yet.
Disable this with:

```yaml
SilverShop\Model\Address:
  enable_geocoding: false
```

### Use Google Maps

Provide a [Google Maps API key](https://developers.google.com/maps/documentation/geocoding/get-api-key)
to use Google Maps ahead of Nominatim for higher-quality results:

```yaml
SilverShop\Model\Address:
  google_maps_api_key: 'your-key-here'
```

### Nominatim fair use

The public OpenStreetMap Nominatim server has an [acceptable-use policy](https://operations.osmfoundation.org/policies/nominatim/):
at most 1 request per second, and a `User-Agent` that identifies your application. Set your own
identifier, and for production or bulk use point the module at your own Nominatim instance:

```yaml
SilverShop\Model\Address:
  geocoder_user_agent: 'example.com'
  nominatim_server: 'https://nominatim.example.com'
```

### Add your own provider

Drop in any [geocoder-php provider](https://github.com/geocoder-php/Geocoder#providers) (Mapbox, HERE,
a local PDOK provider, etc.) alongside the defaults. Registered providers are tried first, then Google
(if keyed), then Nominatim. Call this from a bootstrap, e.g. a module `_config.php`:

```php
use SilverShop\Geocoding\Extensions\AddressGeocoding;

AddressGeocoding::add_provider($myProvider);
```

### Replace the geocoder entirely

For full control, supply your own assembled `Geocoder` instance:

```php
use SilverShop\Geocoding\Extensions\AddressGeocoding;

AddressGeocoding::set_geocoder($myGeocoder);
```

## Usage

```php
$distanceInKm = $addressA->distanceTo($addressB); // float, or null if either lacks coordinates
```

Addresses expose `Latitude` and `Longitude` fields, populated on save when geocoding succeeds.

## Upgrading

Version 4 targets SilverStripe 6 and removes the map picker and visitor-IP features (the archived
`betterbrief/silverstripe-googlemapfield` dependency is gone). Your `Latitude` / `Longitude` data is
preserved. If you are coming from version 3, see [UPGRADING.md](UPGRADING.md) for the breaking changes
and the YAML lines to remove.

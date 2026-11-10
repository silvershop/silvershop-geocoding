# Upgrading

## 4.0 (SilverStripe 6)

Version 4 targets SilverStripe CMS 6 and SilverShop core ^6, and deliberately trims the module back to
its core job: geocoding an address to coordinates, plus a distance helper. The map picker and visitor-IP
features have been removed.

### Requirements

* SilverStripe CMS 6
* SilverShop core ^6
* PHP 8.2+

### Your data is safe

The `Latitude` and `Longitude` fields on `Address` are unchanged, so no data migration is needed, and
`Address::distanceTo()` / `AddressGeocoding::haversine_distance()` keep working as before.

### Removed, and what to do

The map picker depended on `betterbrief/silverstripe-googlemapfield`, which is archived and never
supported SilverStripe 6. These pieces are gone:

| Removed | Action required |
| --- | --- |
| `SilverShop\Geocoding\Checkout\CheckoutStepAddressLocationFallback` | Remove it from any `CheckoutPage.steps` config, or the checkout will fatal with "class not found". |
| `SilverShop\Geocoding\Extensions\GeocodedUserInfo` (visitor-IP geolocation) | Remove any YAML that applies this extension or sets `geocode_visitor_ip`. |
| CMS Address map picker | None. The Address form now shows plain `Latitude` / `Longitude` fields. |
| `Address.mapdefaults` config | Remove it; it only fed the map picker. |
| `betterbrief/silverstripe-googlemapfield` dependency | Removed from `composer.json`. |
| `geocoder-php/free-geoip-provider`, `geocoder-php/host-ip-provider` | Removed. Both services are defunct. |

### Geocoding provider changed

The default provider is now [Nominatim](https://nominatim.org/) (OpenStreetMap), which is keyless and
works out of the box. The previous default chain (FreeGeoIp, HostIp, Google Maps) is gone.

* To use Google Maps, set `SilverShop\Model\Address.google_maps_api_key`. It is then chained ahead of
  Nominatim.
* To add your own provider, call `AddressGeocoding::add_provider($provider)`.
* To replace the geocoder entirely, call `AddressGeocoding::set_geocoder($geocoder)`.

See the [README](README.md) for the full configuration reference.

### Minor API change

`AddressGeocoding::set_geocoder()` is now a static method (it was previously a non-static method that
set static state).

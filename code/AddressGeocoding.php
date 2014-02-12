<?php

class AddressGeocoding extends DataExtension{

	private static $db = array(
		'Latitude' => 'Decimal(10,8)', //-90 to 90
		'Longitude' => 'Decimal(11,8)' //-180 to 180
	);

	private static $inst;

	/**
	 * Get the configured geocoder.
	 * Configures freegeoip, hostip, and googlemaps providers by default.
	 * @return \Geocoder\Geocoder Geocoder
	 */
	public static function get_geocoder() {
		if(self::$inst){
			return self::$inst;
		}
		$geocoder = new \Geocoder\Geocoder();
		$adapter  = new \Geocoder\HttpAdapter\CurlHttpAdapter();
		$geocoder->registerProvider(
			new \Geocoder\Provider\ChainProvider(array(
				new \Geocoder\Provider\FreeGeoIpProvider($adapter),
				new \Geocoder\Provider\HostIpProvider($adapter),
				new \Geocoder\Provider\GoogleMapsProvider($adapter)
			))
		);

		return self::$inst = $geocoder;
	}

	public function set_geocoder(\Geocoder\Geocoder $geocoder) {
		self::$inst = $geocoder;
	}
	
	function onBeforeWrite() {
		if(!$this->owner->Latitude && !$this->owner->Longitude){
			$this->geocodeAddress();
		}
	}

	function geocodeAddress() {
		//TODO: check if address is valid
		$geocoder = self::get_geocoder();
		try {
			$geocoded = $geocoder->geocode($this->owner->toString());
			$this->owner->Latitude = $geocoded->getLatitude();
			$this->owner->Longitude = $geocoded->getLongitude();
		} catch (Exception $e) {
			SS_Log::log($e, SS_Log::ERR);
		}
	}

}
<?php

/**
 * A fallback to use if geocoding fails to locate address.
 */
class CheckoutStep_AddressLocationFallback extends CheckoutStep{
	
	private static $allowed_actions = array(
		"addresslocation",
		"AddressLocationForm"
	);

	function addresslocation() {
		$shippingaddress = $this->getShippingAddress();
		//TODO: verify shipping address exists
		if((int)$shippingaddress->Latitude && (int)$shippingaddress->Longitude){
			return $this->owner->redirect($this->NextStepLink());
		}
		$form = $this->AddressLocationForm();

		return array(
			'OrderForm' => $form
		);
	}

	function AddressLocationForm(){
		//required by GoogleMapField
		Requirements::javascript(THIRDPARTY_DIR."/jquery-entwine/dist/jquery.entwine-dist.js");
		$shippingaddress = $this->getShippingAddress();
		$config = array(
			'fieldNames' => array(
				'lat' => 'Latitude',
				'lng' => 'Longitude'
			),
			'coords' => array(
				'-27.7949688',
				'136.4324989'
			),
			'map' => array(
				'zoom' => 4
			),
			'showSearchBox' => false
		);
		$fields = new FieldList(
			LiteralField::create("locationneededmessage", "<p class=\"message warning\">We could not automatically determine your shipping location. Please find and click the exact location on the map:</p>"),
			GoogleMapField::create($shippingaddress, "Location", $config)
				->setDescription("Please click the exact location of your address")
		);
		$actions = new FieldList(
			new FormAction("setAddressLocation", "Continue")
		);

		$form = new Form($this->owner, "AddressLocationForm",$fields, $actions);
		return $form;
	}

	function setAddressLocation($data, $form) {
		$shippingaddress = $this->getShippingAddress();
		$form->saveInto($shippingaddress);
		$shippingaddress->write();

		return $this->owner->redirect($this->NextStepLink());
	}

	protected function getShippingAddress(){
		return ShoppingCart::singleton()->current()->getShippingAddress();
	}

}
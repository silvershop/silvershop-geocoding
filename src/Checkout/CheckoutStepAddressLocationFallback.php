<?php

namespace SilverShop\Geocoding\Checkout;

use SilverShop\Checkout\Step\CheckoutStep;
use SilverShop\Model\Address;
use SilverStripe\Form\FieldList;
use SilverStripe\Form\FormAction;
use SilverStripe\Form\LiteralField;
use SilverStripe\Form\Form;
use BetterBrief\GoogleMapField;

class CheckoutStepAddressLocationFallback extends CheckoutStep
{
    private static $allowed_actions = [
        "addresslocation",
        "AddressLocationForm"
    ];

    public function addresslocation()
    {
        $shippingaddress = $this->getShippingAddress();

        if ((int)$shippingaddress->Latitude && (int)$shippingaddress->Longitude) {
            return $this->owner->redirect($this->NextStepLink());
        }

        $form = $this->AddressLocationForm();

        return [
            'OrderForm' => $form
        ];
    }

    public function AddressLocationForm()
    {
        $shippingaddress = $this->getShippingAddress();

        $config = [
            'fieldNames' => [
                'lat' => 'Latitude',
                'lng' => 'Longitude'
            ],
            'coords' => [
                Address::config()->mapdefaults['latitude'],
                Address::config()->mapdefaults['longitude']
            ],
            'map' => [
                'zoom' => Address::config()->mapdefaults['zoom']
            ],
            'showSearchBox' => false
        ];
        $fields = new FieldList(
            LiteralField::create("locationneededmessage", "<p class=\"message warning\">We could not automatically determine your shipping location. Please find and click the exact location on the map:</p>"),
            GoogleMapField::create($shippingaddress, "Location", $config)
                ->setDescription("Please click the exact location of your address")
        );
        $actions = new FieldList(
            new FormAction("setAddressLocation", "Continue")
        );

        $form = new Form($this->owner, "AddressLocationForm", $fields, $actions);
        return $form;
    }

    public function setAddressLocation($data, $form)
    {
        $shippingaddress = $this->getShippingAddress();
        $form->saveInto($shippingaddress);
        $shippingaddress->write();

        return $this->owner->redirect($this->NextStepLink());
    }

    protected function getShippingAddress()
    {
        return ShoppingCart::singleton()->current()->getShippingAddress();
    }
}

<?php

require '../vendor/autoload.php';
require 'config.php';


use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\CallCourier;
use Mijora\Omniva\Shipment\Order;
use Mijora\Omniva\Shipment\Package\Address;
use Mijora\Omniva\Shipment\Package\Contact;


// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

try {
    
    $address = new Address();
    $address
            ->setCountry('LT')
            ->setPostcode('72201')
            ->setDeliverypoint('City')
            ->setStreet('Test g. 1');
    
    // Sender contact data
    $senderContact = new Contact();
    $senderContact
            ->setAddress($address)
            ->setMobile('+37060000000')
            ->setPersonName('Stefan Dexter');
    
    
    $call = new CallCourier();
    $call->setAuth($username, $password);
    $call->setSender($senderContact);
    
    $result = $call->callCourier();
    
    if ($result) {
        echo "Courier called";
    } else {
        echo "Failed to call courier";
    }
    

} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
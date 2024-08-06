<?php

require '../vendor/autoload.php';
require 'config.php';


use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\CallCourier;
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
    $call
        ->setAuth($username, $password)
        ->setSender($senderContact)
        ->setEarliestPickupTime('10:00')
        ->setLatestPickupTime('15:00')
        ->setComment('Third door on he left')
        ->setIsHeavyPackage(true)
        ->setIsTwoManPickup(false)
        ->setParcelsNumber(3);

    // display what was sent to OMNIVA OMX API
    echo json_encode($call->getCallCourierOmxRequest(), JSON_PRETTY_PRINT) . PHP_EOL;

    // If sucess $result will be call requset ID
    $result = $call->callCourier();
    
    if ($result) {
        echo "Courier called: " . $result . PHP_EOL;
        // Lets cancel since this is only a test
        $is_canceled = $call->cancelCourierOmx($result);
        echo ($is_canceled ? $result . ' Canceled.' : $result . ' Failed to cancel') . PHP_EOL;
    } else {
        echo "Failed to call courier";
    }

} catch (OmnivaException $e) {
    echo json_encode($e->getData(), JSON_PRETTY_PRINT) . PHP_EOL;
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
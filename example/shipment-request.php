<?php

require '../vendor/autoload.php';
require 'config.php';

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\Package\AdditionalService;
use Mijora\Omniva\Shipment\Package\Address;
use Mijora\Omniva\Shipment\Package\Contact;
use Mijora\Omniva\Shipment\Package\Measures;
use Mijora\Omniva\Shipment\Package\Cod;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Shipment;
use Mijora\Omniva\Shipment\ShipmentHeader;

// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

try {
    $shipment = new Shipment();
    $shipment
            ->setComment('Test comment')
            ->setShowReturnCodeEmail(true);
    $shipmentHeader = new ShipmentHeader();
    $shipmentHeader
            ->setSenderCd($username)
            ->setFileId(date('Ymdhis'));
    $shipment->setShipmentHeader($shipmentHeader);

    $package = new Package();
    $package
            ->setId('54155454')
            ->setService('QH');
    $additionalService = (new AdditionalService())->setServiceCode('SS');
    $package->setAdditionalServices([$additionalService]);
    $measures = new Measures();
    $measures
            ->setWeight(6.6)
            ->setVolume(0.9)
            ->setHeight(0.2)
            ->setWidth(0.3);
    $package->setMeasures($measures);

    //set COD
    $cod = new Cod();
    $cod
            ->setAmount(66.7)
            ->setBankAccount('GB33BUKB20201555555555')
            ->setReceiverName('Test Company')
            ->setReferenceNumber('23232323232323');
    $package->setCod($cod);

    // Receiver contact data
    $receiverContact = new Contact();
    $address = new Address();
    $address
            ->setCountry('LT')
            ->setPostcode('72201')
            ->setDeliverypoint('city')
        //     ->setOffloadPostcode('72203')
            ->setOffloadPostcode('55583')
            ->setStreet('Guobu g.');
    $receiverContact
            ->setAddress($address)
            ->setMobile('+37061111111')
            ->setEmail('onmiva@mgail.com')
            ->setPersonName('Moby Simpson');
    $package->setReceiverContact($receiverContact);

    // Sender contact data
    $senderContact = new Contact();
    $senderContact
            ->setAddress($address)
            ->setMobile('+37060000000')
            ->setEmail('sender@test.com')
            ->setPersonName('Stefan Dexter');
    $package->setSenderContact($senderContact);

    // Simulate multi-package request.
    $shipment->setPackages([$package, $package]);

    // Hide return code showing in customer SMS and email.
    $shipment->setShowReturnCodeSms(false);
    $shipment->setShowReturnCodeEmail(false);

    //set auth data
    $shipment->setAuth($username, $password);

    $result = $shipment->registerShipment();
    if (isset($result['barcodes'])) {
        echo "Received barcodes: " . implode(', ', $result['barcodes']);
    }
} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
    . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
    . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
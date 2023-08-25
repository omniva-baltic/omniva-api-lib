<?php

require '../vendor/autoload.php';
require 'config.php';

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;
use Mijora\Omniva\Shipment\AdditionalService\CodService;
use Mijora\Omniva\Shipment\Package\AdditionalService;
use Mijora\Omniva\Shipment\Package\Address;
use Mijora\Omniva\Shipment\Package\Contact;
use Mijora\Omniva\Shipment\Package\Measures;
use Mijora\Omniva\Shipment\Package\Cod;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Request\CustomOmxRequest;
use Mijora\Omniva\Shipment\Request\OmxRequestInterface;
use Mijora\Omniva\Shipment\Request\PackageLabelOmxRequest;
use Mijora\Omniva\Shipment\Shipment;
use Mijora\Omniva\Shipment\ShipmentHeader;

// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

try {
    // $request = new Request($username, $password);
    // $test = (new CustomOmxRequest())
    //     ->setEndpoint(PackageLabelOmxRequest::API_ENDPOINT)
    //     ->setRequestMethod(OmxRequestInterface::REQUEST_METHOD_GET)
    // ;
    // echo $request->callOmxApi($test) . PHP_EOL;
    // exit();

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
            // ->setService('PU');
    $additionalService = (new AdditionalService())->setServiceCode('SE');
    $package->setAdditionalServices([$additionalService, (new AdditionalService())->setServiceCode('SS')]);

    // exit();

    $measures = new Measures();
    $measures
            ->setWeight(3)
            ->setHeight(0.2)
            ->setWidth(0.3)
            ->setLength(0.1)
            ;
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
    
    $address = (new Address())
        ->setCountry('EE')
        ->setPostcode('51003')
        ->setDeliverypoint('Tartu')
        // ->setOffloadPostcode('72203')
        ->setOffloadPostcode('96091')
        ->setStreet('Ülikooli 2A')
    ;

    $sender_address = (new Address())
        ->setCountry('LT')
        ->setPostcode('51003')
        ->setDeliverypoint('Kaunas')
        ->setStreet('Test str. 69')
    ;

    $bad_delivery_address = (new Address())
        ->setCountry('EE')
        ->setPostcode('51003')
        ->setDeliverypoint('Tartu')
        ->setOffloadPostcode('72203') // incorrect parcel terminal id
        ->setStreet('Ülikooli 2A')
    ;

    $receiverContact
            ->setAddress($address)
            ->setMobile('+37255555555')
            ->setEmail('test@testomniva.com')
            ->setPersonName('receiver');
    $package->setReceiverContact($receiverContact);

    // Sender contact data
    $senderContact = new Contact();
    $senderContact
            ->setAddress($sender_address)
            ->setMobile('55555555')
            ->setEmail('test.sender@omniva.ee')
            ->setPersonName('Sender Name');
    $package->setSenderContact($senderContact);

    // Cloning package for ease of use
    $package2 = clone $package;
    // $package2->setId($package2->getId() . '-1');

    // $receiver_clone = (clone $package2->getReceiverContact())->setAddress($bad_delivery_address);
    // $package2->setReceiverContact(
    //     $receiver_clone
    // );
    // $omx_cod = (new CodService())
    //     ->setCodAmount(100)
    //     ->setCodIban('GB33BUKB20201555555555')
    //     ->setCodReceiver('Testas receiver')
    // ;
    // $package->setAdditionalServiceOmx($omx_cod);

    // Simulate multi-package request.
    $shipment->setPackages([$package, $package2]);

    // Hide return code showing in customer SMS and email.
    $shipment->setShowReturnCodeSms(false);
    $shipment->setShowReturnCodeEmail(false);

    //set auth data
    $shipment->setAuth($username, $password);

    echo json_encode($shipment->getOmxShipmentRequest(), JSON_PRETTY_PRINT) . PHP_EOL;

    $result = $shipment->registerShipment();
    
    if (!is_array($result)) {
        echo json_encode(json_decode($result, true), JSON_PRETTY_PRINT) . PHP_EOL;
    }

    if (is_array($result) && isset($result['barcodes'])) {
        echo "Received barcodes: " . implode(', ', $result['barcodes']);
    }
} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
    . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
    . str_replace("\n", "<br>\n", json_encode($e->getData()))
    . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
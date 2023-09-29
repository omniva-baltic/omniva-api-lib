<?php

require '../vendor/autoload.php';
require 'config.php';


use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\Manifest;
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
            ->setStreet('Guobu g.');
    
    // Sender contact data
    $senderContact = new Contact();
    $senderContact
            ->setAddress($address)
            ->setMobile('+37060000000')
            ->setPersonName('Stefan Dexter');
    
    
    $manifest = new Manifest();
    $manifest
        ->setSender($senderContact)
        ->showBarcode(false)
        ->setSignatureLineLength(40)
        ->setColumnLength('row_number', 20)
        ->setColumnLength('order_number', 40)
        ->setString('sender_address', 'Sender address')
        ->setString('row_number', 'No.')
        ->setString('shipment_number', 'Shipment number')
        ->setString('order_number', 'Order No.')
        ->setString('date', 'Date')
        ->setString('quantity', 'Quantity')
        ->setString('weight', 'Weight (kg)')
        ->setString('recipient_address', "Recipient's name and address")
        ->setString('courier_signature', 'Courier name, surname, signature')
        ->setString('sender_signature', 'Sender name, surname, signature');
    
    foreach ($barcodes as $barcode) {
        $order = new Order();
        $order->setTracking($barcode);
        $order->setQuantity('2');
        $order->setWeight('1');
        $order->setReceiver('Test receiver, City, 12345, LT');
        $manifest->addOrder($order);
    }
    
    $manifest->downloadManifest('I');
    
    /*
    $result = $label->getLabels($barcodes);
    if (isset($result['barcodes'])) {
        echo "Received barcodes: " . implode(', ', $result['barcodes']);
    }
     */
    

} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
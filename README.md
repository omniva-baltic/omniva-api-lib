
# Omniva Api library

Omniva API library, to help to integrate with other systems 

## Features

- Shipment creating
- Label printing. You can download, inline view or save to file. Combine labels or print in seperate pages.
- Manifest generation. You can download, inline view or save to file.
- Tracking by barcode
- Courier call, to ask to pickup parcels from shop.
- List parcel terminals.

## Requirements
- Minimum PHP 7.0, tested up to PHP 7.4

## Instalation

To install via composer:

```sh
composer require mijora/omniva-api
```

## How to use
All examples can be viewed in `example/` folder. 
Use `example/config.php` to enter your API username and password for testing the examples.

## Creating shipment
```php

    use Mijora\Omniva\OmnivaException;
    use Mijora\Omniva\Shipment\Package\AdditionalService;
    use Mijora\Omniva\Shipment\Package\Address;
    use Mijora\Omniva\Shipment\Package\Contact;
    use Mijora\Omniva\Shipment\Package\Measures;
    use Mijora\Omniva\Shipment\Package\Cod;
    use Mijora\Omniva\Shipment\Package\Package;
    use Mijora\Omniva\Shipment\Shipment;
    use Mijora\Omniva\Shipment\ShipmentHeader;

    //create new shipment object 
    $shipment = new Shipment();
    $shipment
            ->setComment('Test comment')  //set comment, optional
            ->setShowReturnCodeEmail(true) //return code in receiver email, optional
            ->setShowReturnCodeSms(true); //return code in receiver sms, optional

    //new shipment header object, required        
    $shipmentHeader = new ShipmentHeader();
    $shipmentHeader
            ->setSenderCd($username) //set username
            ->setFileId(date('Ymdhis')); //set date of shipment creation

    //assign header to shipment        
    $shipment->setShipmentHeader($shipmentHeader);

    //new shipment package object, required
    $package = new Package();
    $package
            ->setId('54155454') //id number, optional
            ->setService('QH'); //service code of package

    //create additional services and add to package, optional        
    $additionalService = (new AdditionalService())->setServiceCode('SS');
    $package->setAdditionalServices([$additionalService]);
    //for available service codes, you can view at https://www.omniva.lt/public/files/failid/omniva-service-codes-lt-eng.pdf

    //set package size and weight
    $measures = new Measures();
    $measures
            ->setWeight(6.6) //weight in kg, required
            ->setLength(9) //dimension in meter, optional
            ->setHeight(2) //dimension in meter, optional
            ->setWidth(3); //dimension in meter, optional
    $package->setMeasures($measures); //set package measurements

    //set COD, optional
    $cod = new Cod();
    $cod
            ->setAmount(66.7) //set cod amount
            ->setBankAccount('GB33BUKB20201555555555') //set bank account
            ->setReceiverName('Test Company') //set company name
            ->setReferenceNumber('23232323232323'); //set reference number of cod
    $package->setCod($cod); //assign cod to package

    //receiver contact object
    $receiverContact = new Contact();
    //receiver address object
    $address = new Address();
    $address
            ->setCountry('LT') //set country code
            ->setPostcode('72201') //set postcode
            ->setDeliverypoint('city') //set city
            ->setOffloadPostcode('72203') //set terminal post code if sending to parcel terminal
            ->setStreet('Guobu g.'); //set street
    $receiverContact
            ->setAddress($address) //assign address to receiver
            ->setEmail('test@test.lt') //set receiver email
            ->setMobile('+37060000000') //set receiver phone
            ->setPersonName('Moby Simpson'); //set receiver full name
    $package->setReceiverContact($receiverContact); //assign receiver to package

    $senderContact = new Contact(); //sender contact object
    $address = new Address(); //sender address object
    $s_address
            ->setCountry('LT') //set country code
            ->setPostcode('72201') //set postcode
            ->setDeliverypoint('city') //set city
            ->setStreet('Guobu g.'); //set street
    $senderContact
            ->setAddress($_address) //assign address to sender
            ->setMobile('+37060000000') //set sender phone
            ->setPersonName('Stefan Dexter'); //set sender full name
    $package->setSenderContact($senderContact); //assign sender to package

    //set packages to shipment, in this case we assign 2 same packeges for shipment
    $shipment->setPackages([$package, $package]);

    //hide return code from customer SMS and email
    $shipment->setShowReturnCodeSms(false);
    $shipment->setShowReturnCodeEmail(false);

    //set auth data
    $shipment->setAuth($username, $password);

    //register shipment to Omniva, on success, will return $result['barcodes'], else throw OmnivaException exception with error message
    $result = $shipment->registerShipment();

```

## Get shipment label

```php

    use Mijora\Omniva\OmnivaException;
    use Mijora\Omniva\Shipment\Label;

    $label = new Label(); //new label object
    $label->setAuth($username, $password); //set auth data
    
    //return labels pdf or thow OmnivaException on error
    //default function attributes downloadLabels($barcodes, $combine = true, $mode = 'I', $name = 'Omniva labels')
    //$barcodes - string or array of strings
    //$combine - if true, will add 4 labels per page, else 1 label per page
    //$mode - I: return directly to browser preview, S: return pdf as string data, D: force browser to download
    //$name - name of file
    $label->downloadLabels($barcodes);

```

## Get manifest

```php

    use Mijora\Omniva\OmnivaException;
    use Mijora\Omniva\Shipment\Manifest;
    use Mijora\Omniva\Shipment\Order;
    use Mijora\Omniva\Shipment\Package\Address;
    use Mijora\Omniva\Shipment\Package\Contact;

    $address = new Address(); //sender address object
    $address
            ->setCountry('LT') //set country code
            ->setPostcode('72201') //set post code
            ->setDeliverypoint('City') //set city
            ->setStreet('Test g.'); //set street
    
    $senderContact = new Contact(); //sender contact object
    $senderContact
            ->setAddress($address) //add address to to contact
            ->setMobile('+37060000000') //set phone
            ->setPersonName('Stefan Dexter'); //set sender full name
    
    $manifest = new Manifest(); //new manifest object
    $manifest->setSender($senderContact); //add sender contact
    
    
    $order = new Order(); //new order object
    $order->setTracking('BK000000000LT'); //set tracking number
    $order->setQuantity('2'); //set quanitty of packages
    $order->setWeight('1'); //set weight in kg
    $order->setReceiver('Test receiver, City, 12345, LT'); //set full receiver address
    $manifest->addOrder($order); //add order to manifest
    
    //get manifest pdf
    //first attribute - I: return directly to browser preview, S: return pdf as string data, D: force browser to download
    //second - pdf file name
    $manifest->downloadManifest('I', 'Manifest file name');

```

## Call courier for pickup

```php

    use Mijora\Omniva\OmnivaException;
    use Mijora\Omniva\Shipment\CallCourier;
    use Mijora\Omniva\Shipment\Package\Address;
    use Mijora\Omniva\Shipment\Package\Contact;

    
    $address = new Address(); //pickup address object
    $address
            ->setCountry('LT') //set country code
            ->setPostcode('72201') //set post code
            ->setDeliverypoint('City') //set city
            ->setStreet('Test g.'); //set street
    
    //pickup contact data
    $senderContact = new Contact();
    $senderContact
            ->setAddress($address) //assign pickup address object
            ->setMobile('+37060000000') //set phone
            ->setPersonName('Stefan Dexter'); //set full name of sender
    
    //call courier object
    $call = new CallCourier();
    $call->setAuth($username, $password, $api_url, true); //set auth info. Username (required), password (required), API url (optional), debug (optional)
    $call->setSender($senderContact); //assign pickup address
    $call->setEarliestPickupTime('08:00'); //set pickup start time
    $call->setLatestPickupTime('17:00'); //set picktup end time
    $call->setDestinationCountry('estonia'); //indicate which country's service to use. estonia - use CI, finland - use CE, any other - use QH
    $call->setParcelsNumber(3); //specify how many packages will be handed over to the courier
    
    $result = $call->callCourier(); //make a call, if returned true - courier called successfully
    $debug_data = $call->getDebugData(); //return debug data which contain URL, HTTP code, request and response

```

## Get list of parcel terminals

```php

    use Mijora\Omniva\Locations\PickupPoints;

    $omnivaPickupPointsObj = new PickupPoints(); //terminals object
    
    //returns array list of terminals or OmnivaException on error
    $terminals = $omnivaPickupPointsObj->getFilteredLocations('lt', 0, 'Kauno apskr.'); //can be optionally filtered by country code, type, and county

```

## Get tracking information

```php

    use Mijora\Omniva\Shipment\Tracking;

    $tracking = new Tracking(); //tracking object
    $tracking->setAuth($username, $password); //set auth data
    
    //returns array of data or OmnivaException on error
    $results = $tracking->getTracking($barcodes); //pass array of barcodes
    
    //results array key will be tracking number and value will be array with date, event and state keys
    if (is_array($results)) {
        foreach ($results as $barcode => $tracking_data) {
            echo '**************<br/>';
            echo $barcode . '<br/>';
            foreach ($tracking_data as $data) {
                echo $data['date']->format('Y-m-d H:i:s') . ' ' . ' ' . $data['event'] . ' ' . $data['state'] . '<br/>';
            }
        }
    }

```
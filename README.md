
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
- Minimum PHP 5.6, tested up to PHP 7.4

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
            ->setId('5454') //id number, optional. Use same ID for several Package if want use multiparcels service
            ->setService('QH'); //service code of package

    //create additional services and add to package, optional        
    $additionalService = (new AdditionalService())->setServiceCode('SS');
    $package->setAdditionalServices([$additionalService]);
    //for available service codes, you can view at https://www.omniva.lt/public/files/failid/omniva-service-codes-lt-eng.pdf

    //set package size and weight
    $measures = new Measures();
    $measures
            ->setWeight(6.689) //weight in kg, required
            ->setLength(0.9) //dimension in meter, optional
            ->setHeight(0.25) //dimension in meter, optional
            ->setWidth(1); //dimension in meter, optional
    $package->setMeasures($measures); //set package measurements

    //set COD, optional
    $cod = new Cod();
    $cod
            ->setAmount(66.72) //set cod amount
            ->setBankAccount('GB33BUKB20201555555555') //set bank account
            ->setReceiverName('Test Company') //set company name
            ->setReferenceNumber('2323'); //set reference number of COD. For Estonia the number is generated according to Method 7-3-1 (https://www.pangaliit.ee/arveldused/viitenumber/7-3-1meetod)
    $package->setCod($cod); //assign cod to package

    //set sender and reeiver address
    $receiverContact = new Contact(); //receiver contact object
    $receiverAddress = new Address(); //receiver address object
    $receiverAddress
            ->setCountry('LT') //set country code (2 letters)
            ->setPostcode('72201') //set postcode (LT, EE and FI are "00000", for LV the format is "LV-0000")
            ->setDeliverypoint('Kaunas') //set city and state (up to 80 chars)
            ->setOffloadPostcode('68594') //set terminal code if sending to parcel terminal
            ->setStreet('Guobu g. 5-266'); //set street, house and apartment number (up to 80 chars)
    $receiverContact
            ->setAddress($receiverAddress) //assign address to receiver
            ->setEmail('test@test.lt') //set receiver email
            ->setMobile('+37060000000') //set receiver phone (recommended in international format)
            ->setPersonName('Moby Simpson'); //set receiver full name
    $package->setReceiverContact($receiverContact); //assign receiver to package

    $senderContact = new Contact(); //sender contact object
    $senderAddress = new Address(); //sender address object
    $senderAddress
            ->setCountry('LV') //set country code (2 letters)
            ->setPostcode('LV-1234') //set postcode (LT, EE and FI are "00000", for LV the format is "LV-0000")
            ->setDeliverypoint('Riga') //set city and state (up to 80 chars)
            ->setStreet('Pils iela 3'); //set street, house and apartment number (up to 80 chars)
    $senderContact
            ->setAddress($senderAddress) //assign address to sender
            ->setMobile('+37125700000') //set sender phone
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
    $manifest
            ->setSender($senderContact) //add sender contact
            ->showBarcode(false) //disable barcode image display
            ->setSignatureLineLength(40) //change the length of the signature line
            ->setString('sender_address', 'Shop address') //change string in manifest. First value is string key. Available keys: sender_address, row_number, shipment_number, order_number, date, quantity, weight, recipient_address, courier_signature, sender_signature
            ->setColumnLength('row_number', 20); //change orders table column width. First value is column key. Available keys: row_number, shipment_number, order_number, date, quantity, weight, recipient_address
    
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

Anytime during setup or when calling api Exception can be thrown with errors.

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
    $call
        ->setAuthsetAuth($username, $password, $api_url, true); // set auth info. Username (required), password (required), API url (optional), debug (optional)
        ->setSender($senderContact) // assign pickup address
        ->setEarliestPickupTime('08:00') // set pickup start time, day will be chosen based on this time (either same day or next day)
        ->setLatestPickupTime('17:00') // set picktup end time
        ->setComment('Third door on he left') // set comment for courier. New with OMX
        ->setIsHeavyPackage(true) // set true if any of packages >30kg, default is false. New with OMX
        ->setIsTwoManPickup(false) // set true if pickup requires two people. Default is false. New with OMX
        ->setParcelsNumber(3); // specify how many packages will be handed over to the courier
    
    $pickup_call_id = $call->callCourier(); // make a call, returns call ID which can be used to cancel pickup call
    $debug_data = $call->getDebugData(); //return debug data which contain URL, HTTP code, request and response. only if debug = true

```

## Cancel courier for pickup. New in OMX

Anytime during setup or when calling api Exception can be thrown with errors. Requires to have Pickup Call ID

```php

    use Mijora\Omniva\OmnivaException;
    use Mijora\Omniva\Shipment\CallCourier;
    
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
    // set auth info. Username (required), password (required), API url (optional), debug (optional)
    $call->setAuthsetAuth($username, $password, $api_url, true);
    
    // $pickup_call_id is ID that was returned during courier pickup call
    $result = $call->cancelCourierOmx($pickup_call_id); // result = true if cancelation was successful
    $debug_data = $call->getDebugData(); //return debug data which contain URL, HTTP code, request and response. only if debug = true

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

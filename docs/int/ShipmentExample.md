## Example Shipment for non Baltic states

```php
use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\Package\Address;
use Mijora\Omniva\Shipment\Package\Contact;
use Mijora\Omniva\Shipment\Package\Measures;
use Mijora\Omniva\Shipment\Package\Notification;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Package\ServicePackage;
use Mijora\Omniva\Shipment\Shipment;
use Mijora\Omniva\Shipment\ShipmentHeader;

// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

$username = '0000000'; //API username
$password = '11AAaa2b'; //API password

try {
    // Prep shipment header
    $shipmentHeader = (new ShipmentHeader())
        ->setSenderCd($username)
        ->setFileId(date('Ymdhis'));
    //-------------------------

    // Seander and Receiver
    $senderAddress = (new Address())
        ->setCountry('LT')
        ->setPostcode('51003')
        ->setDeliverypoint('Kaunas')
        ->setStreet('Test str. 69');

    $senderContact = (new Contact())
        ->setAddress($senderAddress)
        ->setMobile('+37061234567')
        ->setEmail('test.sender@omniva.ee')
        ->setPersonName('Sender Name');

    $receiverAddress = (new Address())
        ->setCountry('PL')
        ->setPostcode('28-567')
        ->setDeliverypoint('Warsaw')
        ->setStreet('Ul. Bosmanska 28');

    $receiverContact = (new Contact())
        ->setAddress($receiverAddress)
        ->setMobile('+48512345678')
        ->setEmail('test@testomniva.com')
        ->setPersonName('receiver');
    //-------------------------

    // Service package - required for non Baltic states
    $servicePackage = new ServicePackage(ServicePackage::CODE_STANDARD);
    //-------------------------

    // Package measurements - one per package
    $measures = (new Measures())
        ->setWeight(3)
        ->setHeight(0.2)
        ->setWidth(0.3)
        ->setLength(0.1);
    //-------------------------

    // Package notifications to sender
    $notification = (new Notification())
        // inform by email about registration
        ->setChannel(Notification::CHANNEL_EMAIL)
        ->setType(Notification::TYPE_REGISTERED);
    //-------------------------

    // Shipment packages
    $package = (new Package())
        ->setId('54155454-1')
        ->setComment('Package comment')
        ->setService(Package::MAIN_SERVICE_PARCEL, Package::CHANNEL_COURIER)
        ->setNotification($notification)
        ->setMeasures($measures)
        ->setReceiverContact($receiverContact)
        ->setSenderContact($senderContact)
        ->setServicePackage($servicePackage);

    $package2 = (new Package())
        ->setId('54155454-2')
        ->setService(Package::MAIN_SERVICE_PARCEL, Package::CHANNEL_COURIER)
        ->setNotification($notification)
        ->setMeasures($measures)
        ->setReceiverContact($receiverContact)
        ->setSenderContact($senderContact)
        ->setServicePackage($servicePackage);
    //-------------------------

    // Shipment
    $shipment = (new Shipment())
        ->setComment('Shipment comment')
        ->setShipmentHeader($shipmentHeader)
        ->setPackages([$package, $package2]);
    //-------------------------

    // Set auth data
    $shipment->setAuth($username, $password);

    // Try registering shipment
    $result = $shipment->registerShipment();

    // Result should be array and have barcodes
    if (is_array($result) && isset($result['barcodes'])) {
        echo "Received barcodes: " . implode(', ', $result['barcodes']);
    }
} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", json_encode($e->getData()))
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
```
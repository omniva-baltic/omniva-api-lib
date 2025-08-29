<?php

namespace Mijora\Omniva\Shipment\Request;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\AdditionalService\AdditionalServiceInterface;
use Mijora\Omniva\Shipment\AdditionalService\CodService;
use Mijora\Omniva\Shipment\AdditionalService\DocumentReturnService;
use Mijora\Omniva\Shipment\Package\Measures;
use Mijora\Omniva\Shipment\Package\Notification;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Package\ServicePackage;
use Mijora\Omniva\Shipment\Shipment;

class ShipmentOmxRequest implements OmxRequestInterface
{
    const API_ENDPOINT = 'shipments/business-to-client';

    public $customerCode;

    public $fileId;

    private $shipments = [];

    /** Information of registered shipments and if they allow to have consolidated shipments inside. Stored by $package->getId() keys */
    private $shipment_allows_consolidation = [];

    /** @var Package[] */
    private $consolidation_packages = [];

    public function addShipment(Package $package)
    {
        $main_service = $package->getMainService();

        // id to match multiple packages as conoslidated shipment
        $package_id = $package->getId();

        if (!$package_id) {
            throw new OmnivaException("Package missing ID");
        }

        // if first package with given id create full shipment body
        if (!isset($this->shipments[$package_id])) {
            $shipment = [
                'mainService' => $main_service,
            ];

            if ($package->isDeliveryChannelRequired()) {
                $shipment['deliveryChannel'] = $package->getChannel();
            }

            // check if we need offloadPostcode
            $offloadPostcodeRequired = Package::isOffloadPostcodeRequired($package);

            $shipment = array_merge($shipment, [
                'shipmentComment' => $package->getComment(),
                'returnAllowed' => $package->getReturnAllowed(),
                'paidByReceiver' => $package->getPaidByReceiver(),
                'partnerShipmentId' => $package_id,
                'receiverAddressee' => $package->getReceiverContact()->getAddresseeForOmx($offloadPostcodeRequired),
                'senderAddressee' => $package->getSenderContact()->getAddresseeForOmx(),
            ]);

            if ($package->getMeasures() instanceof Measures) {
                $shipment['measurement'] = $this->formatMeasures($package->getMeasures());
            }

            $service_package = $package->getServicePackage();
            if ($service_package && ServicePackage::checkCode($service_package->getCode(), $main_service)) {
                $shipment['servicePackage'] = $service_package->getServicePackage();
            }

            $notifications = $package->getNotifications();
            if ($notifications) {
                $shipment['notifications'] = [];
                foreach ($notifications as $notification) {
                    if ($notification && ($notification instanceof Notification) && $notification->isValid()) {
                        $shipment['notifications'][] = $notification->getArrayForOmxRequest();
                    }
                }
            }

            $additional_services = $package->getAdditionalServicesOmx();

            if ($additional_services) {
                $shipment['addServices'] = [];
            }

            foreach ($additional_services as $additional_service) {
                $shipment['addServices'][] = $this->parseAdditionalService($additional_service);
            }

            if ($this->isContentDescriptionRequired($shipment)) {
                $shipment['contentDescription'] = $package->getContentDescription();
            }

            $this->shipments[$package_id] = $shipment;

            if ($this->isConsolidatedShipmentsAllowed($package)) {
                $this->shipment_allows_consolidation[$package_id] = true;
            }

            $this->verifyInternationalShipments();

            return $this;
        }

        // if exists in shipments means need to add as consolidated shipment
        if (!$this->canAddConsolidatedShipment($package_id)) {
            throw new OmnivaException('Consolidated shipments allowed only for main service PARCEL or PALLET and delivery channel COURIER with additional service COD or DOCUMENT_RETURN');
        }

        // check if packages additional services can be consolidated
        if (!$package->isAbleToConsolidate()) {
            throw new OmnivaException('Only [ '
                . implode(', ', $package->getConsolidationAllowedAdditionalServiceCodes()) 
                . ' ] service codes is allowed on consolidated package');
        }

        if (!isset($this->shipments[$package_id]['consolidatedShipments'])) {
            $this->shipments[$package_id]['consolidatedShipments'] = [];
        }

        $consolidate_data = [
            'barcode' => null,
            'partnerShipmentId' => $package_id . '::' . count($this->shipments[$package_id]['consolidatedShipments']),
            // 'weight' => $this->getWeightFromMeasure($package->getMeasures()),
            'addServices' => $package->getConsolidatedAdditionalServices(),
        ];

        $additional_services = $package->getAdditionalServicesOmx();

        $this->shipments[$package_id]['consolidatedShipments'][] = $consolidate_data;

        $this->verifyInternationalShipments();
    }

    public function isContentDescriptionRequired($shipment)
    {
        $description_not_required = array('LT', 'LV', 'EE', 'FI');

        if (!isset($shipment['receiverAddressee'])) {
            return true;
        }

        $receiver = $shipment['receiverAddressee'];

        if (!isset($receiver['address']['country']) || empty($receiver['address']['country'])) {
            return true;
        }

        if (in_array(strtoupper($receiver['address']['country']), $description_not_required)) {
            return false;
        }

        if (!isset($shipment['customs']['shipmentItems']['description']) || empty($shipment['customs']['shipmentItems']['description'])) {
            return true;
        }

        return false;
    }

    public function verifyInternationalShipments()
    {
        $verify_not_required = array('LT', 'LV', 'EE', 'FI');

        foreach ($this->shipments as $shipment) {
            if (!isset($shipment['receiverAddressee']) || !isset($shipment['senderAddressee'])) {
                throw new OmnivaException("Cannot verify international shipment data");
            }

            $receiver = $shipment['receiverAddressee'];
            $sender = $shipment['senderAddressee'];

            if (!isset($receiver['address']) || empty($receiver['address']['country'])) {
                throw new OmnivaException("Recipient country could not be determined during shipment verification");
            }

            if (in_array(strtoupper($receiver['address']['country']), $verify_not_required)) {
                continue;
            }

            if (empty($receiver['contactMobile']) && empty($receiver['contactPhone'])) {
                throw new OmnivaException("When registering an international shipment, the recipient's phone number is required");
            }

            if (empty($sender['contactMobile']) && empty($sender['contactPhone'])) {
                throw new OmnivaException("When registering an international shipment, the sender's phone number is required");
            }

            if (empty($shipment['contentDescription'])) {
                if (!isset($shipment['customs']['shipmentItems']['description']) || empty($shipment['customs']['shipmentItems']['description'])) {
                    throw new OmnivaException("When registering an international shipment, a description of the shipment is required");
                }
            }
        }

        return true;
    }

    public function canAddConsolidatedShipment($package_id)
    {
        return isset($this->shipment_allows_consolidation[$package_id]) && $this->shipment_allows_consolidation[$package_id];
    }

    public function getWeightFromMeasure(Measures $measures)
    {
        /* shipments/measurement/weight Number(9.3) Gross Weight */
        return round((float) $measures->getWeight(), 3);
    }

    public function formatMeasures(Measures $measures)
    {
        // we always expect weight
        $formated = [
            /* shipments/measurement/weight Number(9.3) Gross Weight */
            'weight' => (string) $this->getWeightFromMeasure($measures),
        ];

        if ($measures->getLength()) {
            /* shipments/measurement/length Number(5.3) parcel length in m */
            $formated['length'] = (string) round((float) $measures->getLength(), 3);
        }

        if ($measures->getWidth()) {
            /* shipments/measurement/width Number(5.3) parcel width in m */
            $formated['width'] = (string) round((float) $measures->getWidth(), 3);
        }

        if ($measures->getHeight()) {
            /* shipments/measurement/height Number(5.3) parcel height in m */
            $formated['height'] = (string) round((float) $measures->getHeight(), 3);
        }

        return $formated;
    }

    public function isConsolidatedShipmentsAllowed(Package $package)
    {
        $additional_services = $package->getAdditionalServicesOmx();

        $has_required_add_service = false;
        foreach ($additional_services as $add_service) {
            if (($add_service instanceof CodService) || ($add_service instanceof DocumentReturnService)) {
                $has_required_add_service = true;
                break;
            }
        }

        if (
            $has_required_add_service
            && ($package->getMainService() === Package::MAIN_SERVICE_PARCEL || $package->getMainService() === Package::MAIN_SERVICE_PALLET)
            && $package->getChannel() === Package::CHANNEL_COURIER
        ) {
            return true;
        }

        return false;
    }

    public function parseAdditionalService(AdditionalServiceInterface $add_service)
    {
        $add_service_body = [
            'code' => $add_service->getServiceCode(),
        ];

        if ($add_service->getServiceParams()) {
            $add_service_body['params'] = $add_service->getServiceParams();
        }

        return $add_service_body;
    }

    /**
     * {@inheritdoc}
     */
    public function getOmxApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return OmxRequestInterface::REQUEST_METHOD_POST;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $body = [
            'customerCode' => $this->customerCode,
            'shipments' => array_values($this->shipments), // remove array keys from request body
        ];

        if ($this->fileId) {
            $body['fileId'] = $this->fileId;
        }

        return $body;
    }
}

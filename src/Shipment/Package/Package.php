<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\AdditionalService\AdditionalServiceInterface;
use Mijora\Omniva\Shipment\AdditionalService\CodService;
use Mijora\Omniva\Shipment\AdditionalService\DeliveryToAnAdultService;
use Mijora\Omniva\Shipment\AdditionalService\DeliveryToSpecificPersonService;
use Mijora\Omniva\Shipment\AdditionalService\DocumentReturnService;
use Mijora\Omniva\Shipment\AdditionalService\FragileService;
use Mijora\Omniva\Shipment\AdditionalService\InsuranceService;
use Mijora\Omniva\Shipment\Shipment;

class Package
{
    const MAIN_SERVICE_PARCEL = 'PARCEL';
    const MAIN_SERVICE_LETTER = 'LETTER';
    const MAIN_SERVICE_PALLET = 'PALLET';

    const MAIN_SERVICE_ALL = [
        self::MAIN_SERVICE_LETTER,
        self::MAIN_SERVICE_PALLET,
        self::MAIN_SERVICE_PARCEL,
    ];

    const CHANNEL_PARCEL_MACHINE = 'PARCEL_MACHINE';
    const CHANNEL_POST_OFFICE = 'POST_OFFICE';
    const CHANNEL_COURIER = 'COURIER';
    const CHANNEL_PICK_UP_POINT = 'PICK_UP_POINT';

    const CHANNEL_ALL = [
        self::CHANNEL_COURIER,
        self::CHANNEL_PARCEL_MACHINE,
        self::CHANNEL_PICK_UP_POINT,
        self::CHANNEL_POST_OFFICE,
    ];

    const CHANNEL_REQUIRES_OFFLOAD_POSTCODE = [
        self::CHANNEL_PARCEL_MACHINE,
        self::CHANNEL_PICK_UP_POINT,
        self::CHANNEL_POST_OFFICE,
    ];

    /** Available channels by main service main_service => [channel, channel] */
    const MAIN_SERVICE_CHANNEL_AVAILABILITY = [
        self::MAIN_SERVICE_PARCEL => [
            self::CHANNEL_COURIER,
            self::CHANNEL_PARCEL_MACHINE,
            self::CHANNEL_POST_OFFICE
        ],

        self::MAIN_SERVICE_LETTER => [], // currently not used

        self::MAIN_SERVICE_PALLET => [
            self::CHANNEL_COURIER
        ],
    ];

    /**
     * Legacy codes for delivery by courier
     */
    const LEGACY_SERVICES_COURIER = [
        'QH', 'QL', 'PK',
        'LA', 'LE', 'LZ',
        'LG', 'LX', 'LH',
        'CI', 'QB', 'EA',
    ];

    /**
     * Legacy codes for delivery to parcel terminal
     */
    const LEGACY_SERVICES_TERMINAL = [
        'PA', 'PU', 'PP',
        'PV',
    ];

    /**
     * Legacy codes for delivery to Post Office
     */
    const LEGACY_SERVICES_POST_OFFICE = [
        'PO',
    ];

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $service;

    /**
     * One of following main service codes: (PARCEL, LETTER, PALLET). Consult with the Account manager about the range of services
     * @var string
     */
    private $main_service;

    /**
     * One of following channel codes: (COURIER, POST_OFFICE, PARCEL_MACHINE, PICK_UP_POINT)
     * deliveryChannel is mandatory if mainService=PARCEL and case delivery country is EE, LT, LV or FI.
     * @var string
     */
    private $channel;

    /**
     * Whether the return is allowed and return code showed or not. By default (if element does not exist) the value is: false
     * @var bool
     */
    private $returnAllowed = false;

    /**
     * Whether shipment is paid by sender or reciever can be true only if mainService=PARCEL default value=false
     * @var bool
     */
    private $paidByReceiver = false;

    /**
     * @var string
     */
    private $packetUnitIdentificator;

    /**
     * @var array
     */
    private $additionalServices;

    /**
     * @var AdditionalServiceInterface[]
     */
    private $additionalServicesOmx = [];
    
    /**
     * @var Notifications[]
     */
    private $notifications = [];

    /**
     * @var Measures
     */
    private $measures;

    /**
     * @var Cod
     */
    private $cod;

    /**
     * @var Contact
     */
    private $senderContact;

    /**
     * @var Contact
     */
    private $receiverContact;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Package
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Legacy service code
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Main service code for OMX API
     * @return string
     */
    public function getMainService()
    {
        return $this->main_service;
    }

    /**
     * Channel code for OMX API
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $main_service Main service code or legacy service code (eg. QA)
     * @param string $channel Channel code or null if using legace service code
     * @return Package
     */
    public function setService($main_service, $channel = null)
    {
        $main_service = mb_strtoupper($main_service);

        // legacy service code
        if (!$channel) {
            $this->service = $main_service;

            // try to determine main service and channel based on legacy code
            if (in_array($main_service, self::LEGACY_SERVICES_COURIER)) { // Parcel - Courier
                $main_service = self::MAIN_SERVICE_PARCEL;
                $channel = self::CHANNEL_COURIER;
            } elseif (in_array($main_service, self::LEGACY_SERVICES_TERMINAL)) { // Parcel - Parcel Machine
                $main_service = self::MAIN_SERVICE_PARCEL;
                $channel = self::CHANNEL_PARCEL_MACHINE;
            } elseif (in_array($main_service, self::LEGACY_SERVICES_POST_OFFICE)) { // Parcel - Post office
                $main_service = self::MAIN_SERVICE_PARCEL;
                $channel = self::CHANNEL_POST_OFFICE;
            }
        }

        if (
            !isset(self::MAIN_SERVICE_CHANNEL_AVAILABILITY[$main_service])
            || !in_array($channel, self::MAIN_SERVICE_CHANNEL_AVAILABILITY[$main_service])
        ) {
            throw new OmnivaException(
                $channel
                    ? "Given main service [ " . (string) $main_service . " ] or channel [ " . (string) $channel . " ] incorrect"
                    : "Could not determine main service and channel from legacy service code [ " . (string) $this->service . " ]"
            );
        }

        $this->main_service = $main_service;
        $this->channel = $channel;


        return $this;
    }

    /**
     * @return string
     */
    public function getPacketUnitIdentificator()
    {
        return $this->packetUnitIdentificator;
    }

    /**
     * @param string $packetUnitIdentificator
     * @return Package
     */
    public function setPacketUnitIdentificator($packetUnitIdentificator)
    {
        $this->packetUnitIdentificator = $packetUnitIdentificator;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalServices()
    {
        return $this->additionalServices;
    }

    /**
     * @return AdditionalServiceInterface[]
     */
    public function getAdditionalServicesOmx()
    {
        return $this->additionalServicesOmx;
    }

    public function setAdditionalServiceOmx(AdditionalServiceInterface $omx_add_service)
    {
        $this->additionalServicesOmx[$omx_add_service->getServiceCode()] = $omx_add_service;

        return $this;
    }

    /**
     * @param array $additionalServices
     * @return Package
     */
    public function setAdditionalServices($additionalServices)
    {
        $this->additionalServices = $additionalServices;

        foreach ($additionalServices as $additionalService) {
            $omx_add_service = $this->convertLegacyAddServiceToOmxService($additionalService);

            if ($omx_add_service instanceof AdditionalServiceInterface) {
                $this->setAdditionalServiceOmx($omx_add_service);
            }
        }

        return $this;
    }

    /**
     * @return Measures
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param Measures $measures
     * @return Package
     */
    public function setMeasures($measures)
    {
        if (!$measures->getWeight())
            throw new OmnivaException("Measures: weight is required.");
        $this->measures = $measures;
        return $this;
    }

    /**
     * @return Cod
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * @param Cod $monetary
     * @return Package
     */
    public function setCod(Cod $cod)
    {
        // this part should be used only if whole registration is done in legacy way
        // create additional service for OMX
        $cod_omx_service = (new CodService())
            ->setCodReceiver($cod->getReceiverName())
            ->setCodIban($cod->getBankAccount())
            ->setCodAmount($cod->getAmount())
            ->setCodReference($cod->getReferenceNumber());
        $this->setAdditionalServiceOmx($cod_omx_service);
        // OMX Registration done

        if (!$cod->getAmount() && $this->containsCodAdditionalService()) {
            throw new OmnivaException("Amount is required, when additional service COD is used.");
        }

        if (!$cod->getBankAccount() && $this->containsCodAdditionalService()) {
            throw new OmnivaException("Account is required, when additional service COD is used.");
        }

        $this->cod = $cod;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getSenderContact()
    {
        return $this->senderContact;
    }

    /**
     * @param Contact $senderContact
     * @return Package
     */
    public function setSenderContact($senderContact)
    {
        if (!$senderContact->getPersonName())
            throw new OmnivaException("Incorrect XML data provided in contact section: person_name is required.");
        $this->validateAddress($senderContact->getAddress(), true);
        $this->senderContact = $senderContact;
        return $this;
    }

    /**
     * @return Contact
     */
    public function getReceiverContact()
    {
        return $this->receiverContact;
    }

    /**
     * @param Contact $receiverContact
     * @return Package
     */
    public function setReceiverContact($receiverContact)
    {
        if (!$receiverContact->getPersonName())
            throw new OmnivaException("Incorrect XML data provided in contact section: person_name is required.");
        $this->validateAddress($receiverContact->getAddress(), false);
        $this->receiverContact = $receiverContact;
        return $this;
    }

    public function containsCodAdditionalService()
    {
        foreach ($this->additionalServices as $additionalService) {
            if ($additionalService->getServiceCode() == Shipment::ADDITIONAL_SERVICES['cod'])
                return true;
        }
        return false;
    }

    public function validateAddress($address, $sender = false)
    {
        $address_type = $sender ? 'sender' : 'receiver';
        $error_prefix = "Incorrect XML data provided in $address_type contact section: ";

        if (!$address->getCountry()) {
            throw new OmnivaException($error_prefix . "country is required.");
        }

        if ($sender || !self::isOffloadPostcodeRequired($this->channel) && !$address->getPostcode()) {
            if (!$address->getPostcode()) {
                throw new OmnivaException($error_prefix . "postcode is required.");
            }

            if (!$address->getDeliveryPoint()) {
                throw new OmnivaException($error_prefix . "delivery point is required.");
            }
        }

        if (!$sender && self::isOffloadPostcodeRequired($this->channel) && !$address->getOffloadPostcode()) {
            throw new OmnivaException(
                $error_prefix . "offloadPostcode is required, when using delivery channel "
                    . implode(', ', Package::CHANNEL_REQUIRES_OFFLOAD_POSTCODE) . "."
            );
        }
    }

    public function getReturnAllowed()
    {
        // only parcel service can have returnAllowed set to true
        return $this->returnAllowed;
    }

    public function setReturnAllowed($allowed = false)
    {
        $this->returnAllowed = (bool) $allowed;

        return $this;
    }

    /**
     * Checks if main service is PARCEL if not always return false. Otherwise returns what was set using setPaidByReceiver($pays_receiver = false)
     * 
     * @return bool
     */
    public function getPaidByReceiver()
    {
        // only parcel service can have returnAllowed set to true
        return $this->getMainService() === self::MAIN_SERVICE_PARCEL ? $this->paidByReceiver : false;
    }

    /**
     * Allows to set wether sender or receiver pays for shipment. When getPaidByReceiver() is used it will check if main service is PARCEL
     * if not paidByReceiver value will be ignored and false returned instead.
     * 
     * @param bool $pays_receiver Should receiver pay for shipment
     * @return Package
     */
    public function setPaidByReceiver($pays_receiver = false)
    {
        $this->paidByReceiver = (bool) $pays_receiver;

        return $this;
    }

    /**
     * Sets parameter if sender should get notifications about parcel
     * 
     * @param Notification $notification
     * 
     * @return Package
     */
    public function setNotification(Notification $notification)
    {
        if (!($notification instanceof Notification)) {
            throw new OmnivaException('Notification object must be instance of Notification class');
        }

        // using notification key so we always have only one combination
        $this->notifications[$notification->getTypeChannelString()] = $notification;

        return $this;
    }

    /**
     * @return Notifications[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param string $delivery_channel Delivery channel code to check for required offloadPostcode
     */
    public static function isOffloadPostcodeRequired($delivery_channel = null)
    {
        return in_array($delivery_channel, self::CHANNEL_REQUIRES_OFFLOAD_POSTCODE);
    }

    /**
     * Convert legacy additional service codes to OMX service or parameters
     * 
     * @param AdditionalService $legacy_service Legacy service object
     * 
     * @return AdditionalServiceInterface|null Returns OMX Service object
     */
    public function convertLegacyAddServiceToOmxService(AdditionalService $legacy_service)
    {
        $legacy_code = mb_strtoupper($legacy_service->getServiceCode());

        // COD -> COD - legacy used a separate class for this so probably no need to check here, instead create new service when adding legacy cod
        // $cod = ['BP'];
        // if (in_array($legacy_code, $cod)) {
        //     return (new CodService())
        // }

        // Fragile -> FRAGILE
        $fragile = ['BC'];
        if (in_array($legacy_code, $fragile)) {
            return (new FragileService());
        }

        // Insurance -> INSURANCE
        $insurance = ['BI'];
        if (in_array($legacy_code, $insurance)) {
            return (new InsuranceService());
        }

        // For now ignore this as legacy didnt have values here
        // Personal Delivery -> DELIVERY_TO_A_SPECIFIC_PERSON
        // $personal_delivery = ['BK'];
        // if (in_array($legacy_code, $personal_delivery)) {
        //     return (new DeliveryToSpecificPersonService());
        // }

        // Document return -> DOCUMENT_RETURN
        $document_return = ['XT'];
        if (in_array($legacy_code, $document_return)) {
            return (new DocumentReturnService());
        }

        // Issue to persons at the age of 18+ -> DELIVERY_TO_AN_ADULT
        $adult = ['PC'];
        if (in_array($legacy_code, $adult)) {
            return (new DeliveryToAnAdultService());
        }

        // Legacy notifications to sender was done using additional service, OMX API handles it as parameters
        // Delivery confirmation e-mail to sender
        if ($legacy_code === 'SE') {
            $this->setNotification(
                (new Notification)
                    ->setType(Notification::TYPE_DELIVERED)
                    ->setChannel(Notification::CHANNEL_EMAIL)
            );
            return null;
        }
        // Delivery confirmation SMS to sender
        if ($legacy_code === 'SS') {
            $this->setNotification(
                (new Notification)
                    ->setType(Notification::TYPE_DELIVERED)
                    ->setChannel(Notification::CHANNEL_SMS)
            );
            return null;
        }

        return null;
    }
}

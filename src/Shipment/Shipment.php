<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;
use Mijora\Omniva\Shipment\Package\Package;
use Mijora\Omniva\Shipment\Request\ShipmentOmxRequest;

class Shipment
{
    const CONTACT_TYPES = ['receiver' => 'receiverAddressee', 'sender' => 'returnAddressee'];

    const TERMINAL_SERVICES = ['PA', 'PU', 'PV', 'PP', 'CE', 'CD'];
    const MULTIPARCELS_SERVICES = ['PK', 'QH', 'DD', 'DE', 'CI', 'LX', 'LH', 'CN', 'CE'];

    const ADDITIONAL_SERVICES = ['cod' => 'BP'];

    const ADDITIONAL_SERVICES_MAP = [
        'code' => ['BA','BB','BC','BG','BI','BK','BL','BM','BP','BS','BT','CL','GN','GM','PC','SB','SE','SF','SG','SI','SL','SS','ST','QD','XT'],
        'CA'   => [   1,   1,   1,   1,   0,   0,   0,   0,   1,   0,   0,   0,   0,   0,   0,   0,   1,   1,   0,   0,   0,   1,   1,   0,   0],
        'CB'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   0,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   0,   1,   1,   0,   1],
        'CC'   => [   0,   1,   1,   1,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0],
        'CD'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   0,   0,   0,   1,   1,   1,   1,   1,   1,   1,   0,   0,   1,   1,   0,   1],
        'CE'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   0,   0,   0,   1,   1,   1,   1,   1,   1,   1,   0,   0,   1,   1,   0,   1],
        'CI'   => [   0,   0,   1,   0,   0,   0,   0,   0,   1,   0,   0,   1,   0,   0,   0,   0,   1,   0,   0,   0,   0,   1,   0,   0,   1],
        'EA'   => [   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0],
        'EP'   => [   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0],
        'LA'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   0,   1,   1,   1,   1,   0,   0,   1,   1,   0,   1],
        'LE'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   0,   1,   1,   1,   1,   0,   0,   1,   1,   0,   1],
        'LG'   => [   0,   0,   1,   0,   1,   0,   1,   1,   1,   1,   1,   0,   1,   1,   0,   1,   1,   1,   1,   0,   0,   1,   1,   1,   1],
        'LH'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   1,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   1,   1,   1,   1,   1],
        'LL'   => [   0,   0,   0,   0,   0,   0,   0,   0,   0,   1,   1,   0,   0,   0,   0,   1,   1,   1,   1,   0,   0,   1,   1,   1,   1],
        'LX'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   1,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   1,   1,   1,   1,   1],
        'LZ'   => [   0,   0,   1,   0,   1,   0,   1,   1,   1,   1,   1,   0,   1,   1,   0,   1,   1,   1,   1,   0,   0,   1,   1,   1,   1],
        'PA'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   1,   1,   1,   1,   1,   1,   0,   1,   1,   1,   0],
        'PK'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   1,   1,   1,   1,   1,   0,   0,   1,   1,   0,   0],
        'PO'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   1,   1,   1,   1,   1,   0,   0,   1,   1,   0,   0],
        'PP'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   1,   1,   1,   0],
        'PU'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   1,   1,   1,   1,   1,   1,   0,   1,   1,   1,   0],
        'PV'   => [   0,   0,   1,   0,   1,   0,   0,   0,   1,   1,   0,   0,   1,   1,   0,   1,   1,   1,   1,   1,   0,   1,   1,   0,   0],
        'QB'   => [   0,   0,   0,   0,   1,   0,   0,   0,   1,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0],
        'QH'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   0,   0,   1,   1,   1,   1,   0,   1,   1,   0,   0,   0,   1,   1,   0,   1],
        'QK'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   1,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   1,   1,   1,   1,   1],
        'QL'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   0,   0,   1,   0,   0,   0,   0,   1,   0,   0,   0,   0,   1,   0,   0,   1],
        'QP'   => [   0,   0,   1,   0,   1,   1,   0,   0,   1,   1,   0,   0,   0,   0,   1,   1,   1,   1,   1,   0,   1,   1,   1,   1,   1],
        'VC'   => [   0,   1,   1,   1,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0],
        'VS'   => [   1,   1,   1,   1,   0,   0,   0,   0,   1,   0,   0,   0,   0,   0,   0,   0,   1,   1,   0,   0,   0,   1,   1,   0,   0],
    ];

    const ADDITIONAL_SERVICES_CONDITIONS = [
        'QB' => [
            'BP' => [
                'only_countries' => ['FI'],
            ],
        ],
    ];

    /**
     * @var ShipmentHeader
     */
    private $header;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var boolean
     */
    private $showReturnCodeSms;

    /**
     * @var boolean
     */
    private $showReturnCodeEmail;

    /**
     * @var string
     */
    private $partnerId;

    /**
     * @var Package[]
     */
    private $packages = [];
    
    /**
     * @var Request
     */
    private $request;
    
    public function setAuth( $username, $password, $api_url = 'https://edixml.post.ee', $debug = false )
    {
        $this->request = new Request($username, $password, $api_url, $debug);
    }

    /**
     * @return ShipmentHeader
     */
    public function getShipmentHeader()
    {
        return $this->header;
    }

    /**
     * @param ShipmentHeader $header
     * @return Shipment
     */
    public function setShipmentHeader( $header )
    {
        if(!$header->getSenderCd()) {
            throw new OmnivaException("Incorrect XML data provided: Sender ID (sender_cd) is required.");
        }
        $this->header = $header;
        return $this;
    }

    /*
     * @return mixed
     */
    public function registerShipment($use_old_api = false)
    {
        if ( empty($this->request) ) {
            throw new OmnivaException("Please set username and password");
        }

        return $use_old_api ? $this->request->call($this->toXml()->asXML()) : $this->request->registerShipmentOmx($this->getOmxShipmentRequest());
    }

    /**
     * @return Package[]
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param Package[]|Package $packages Array of Package objects or just Package obj by itself
     * @param bool $replace Should given packages replace currently set ones. If true replaces whole array with new values, if false will add to array
     * 
     * @return Shipment
     * @throws OmnivaException
     */
    public function setPackages($packages, $replace = false)
    {
        if (!is_array($packages)) {
            $packages = [$packages];
        }

        if ($replace) {
            $this->packages = [];
        }

        foreach ($packages as $package) {
            if (Package::class !== get_class($package)) {
                throw new OmnivaException("Trying to add package that is not of class Package");
            }

            $this->packages[] = $package;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Shipment
     */
    public function setComment( $comment )
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowReturnCodeSms()
    {
        return (!is_null($this->showReturnCodeSms));
    }

    /**
     * @return string
     */
    public function getShowReturnCodeSms()
    {
        return $this->showReturnCodeSms ? 'true' : 'false';
    }

    /**
     * @param bool $showReturnCodeSms
     * @return Shipment
     */
    public function setShowReturnCodeSms( $showReturnCodeSms )
    {
        $this->showReturnCodeSms = $showReturnCodeSms;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowReturnCodeEmail()
    {
        return (!is_null($this->showReturnCodeEmail));
    }

    /**
     * @return string
     */
    public function getShowReturnCodeEmail()
    {
        return $this->showReturnCodeEmail ? 'true' : 'false';
    }

    /**
     * @param bool $showReturnCodeEmail
     * @return Shipment
     */
    public function setShowReturnCodeEmail( $showReturnCodeEmail )
    {
        $this->showReturnCodeEmail = $showReturnCodeEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param string $partnerId
     * @return Shipment
     */
    public function setPartnerId( $partnerId )
    {
        $this->partnerId = $partnerId;
        return $this;
    }

    public static function getAdditionalServicesForShipment( $shipmentServiceCode )
    {
        if ( ! isset(self::ADDITIONAL_SERVICES_MAP[$shipmentServiceCode]) ) {
            return array();
        }

        $services = array();
        foreach ( self::ADDITIONAL_SERVICES_MAP[$shipmentServiceCode] as $position => $value ) {
            if ( $value ) {
                $services[] = self::ADDITIONAL_SERVICES_MAP['code'][$position];
            }
        }

        return $services;
    }

    public static function getAdditionalServiceConditionsForShipment( $shipmentServiceCode, $additionServiceCode )
    {
        if ( ! isset(self::ADDITIONAL_SERVICES_CONDITIONS[$shipmentServiceCode]) ) {
            return (object) array();
        }
        if ( ! isset(self::ADDITIONAL_SERVICES_CONDITIONS[$shipmentServiceCode][$additionServiceCode]) ) {
            return (object) array();
        }

        return (object) self::ADDITIONAL_SERVICES_CONDITIONS[$shipmentServiceCode][$additionServiceCode];
    }

    /**
     * @return ShipmentOmxRequest
     */
    public function getOmxShipmentRequest()
    {
        $omxRequest = new ShipmentOmxRequest();

        $header = $this->getShipmentHeader();
        $omxRequest->customerCode = $header->getSenderCd();
        $omxRequest->fileId = $header->getFileId();
        
        $packages = $this->getPackages();
        foreach ($packages as $package) {
            // legacy comment was on main shipment
            $package->setComment($this->getComment());

            $omxRequest->addShipment($package);
        }

        return $omxRequest;
    }

    public function toXml()
    {
        $xml = new \SimpleXMLElement('<interchange/>');
        $xml->addAttribute('msg_type', 'elsinfov1');

        // Shipment header data
        $header = $this->getShipmentHeader();
        $head = $xml->addChild('header');
        $head->addAttribute('sender_cd', $header->getSenderCd());
        if($header->getFileId()) {
            $head->addAttribute('file_id', $header->getFileId());
        }
        if($header->getPrepDateTime()) {
            $head->addAttribute('prep_date_time', $header->getPrepDateTime());
        }

        $itemList = $xml->addChild('item_list');
        

        // Add all packaged to item list.
        $packages = $this->getPackages();
        $mpsPackages = $this->calcMpsPackages();
        foreach ($packages as $package) {
            $item = $itemList->addChild('item');
            if ($package->getService()) {
                $item->addAttribute('service', $package->getService());
            }
            if ($package->getId() && $mpsPackages[$package->getId()] > 1) {
                if (!in_array($package->getService(), self::MULTIPARCELS_SERVICES)) {
                    throw new OmnivaException("Multi-parcel shipment is not available for selected service");
                }
                $item->addAttribute('packetUnitIdentificator', $package->getId());
            }

            // Additional package services.
            $additionalServices = $package->getAdditionalServices();
            if(!empty($additionalServices))
            {
                $addService = $item->addChild('add_service');
                foreach ($additionalServices as $additionalService)
                {
                    $addService->addChild('option')->addAttribute('code', $additionalService->getServiceCode());
                }
            }

            // Package measurement data.
            $measures = $package->getMeasures();
            if ($measures) {
		$measuresNode = $item->addChild('measures');
		$measuresNode->addAttribute('weight', $measures->getWeight());
		if ($measures->getVolume()) {
                    $measuresNode->addAttribute('volume', $measures->getVolume());
		}
		if ($measures->getLength()) {
                    $measuresNode->addAttribute('length', $measures->getLength());
		}
		if ($measures->getWidth()) {
                    $measuresNode->addAttribute('width', $measures->getWidth());
		}
		if ($measures->getHeight()) {
                    $measuresNode->addAttribute('height', $measures->getHeight());
		}
            }
            // Monetary data.
            $cod = $package->getCod();
            if ($cod) {
                $monetaryNode = $item->addChild('monetary_values');
                if($cod->getReceiverName()) {
                    $monetaryNode->addChild('cod_receiver', $cod->getReceiverName());
                }
                if ($cod->getAmount())
                {
                    $value = $monetaryNode->addChild('values');
                    $value->addAttribute('code', 'item_value');
                    $value->addAttribute('amount', $cod->getAmount());
                }
                if($cod->getBankAccount()) {
                    $item->addChild('account', $cod->getBankAccount());
                }
                if($cod->getReferenceNumber()) {
                    $item->addChild('reference_number', $cod->getReferenceNumber());
                }
            }
            // Non-mandatory
            if($this->getComment()) {
                $item->addChild('comment', $this->getComment());
            }
            if($this->isShowReturnCodeSms()) {
                $item->addChild('show_return_code_sms', $this->getShowReturnCodeSms());
            }
            if($this->isShowReturnCodeEmail()) {
                $item->addChild('show_return_code_email', $this->getShowReturnCodeEmail());
            }
            if($this->getPartnerId()) {
                $item->addChild('partnerId', $this->getPartnerId());
            }
        
            // Receiver contact data.
            $receiverAddressee = $package->getReceiverContact();
            $receiverAddresseeNode = $item->addChild('receiverAddressee');
            $receiverAddresseeNode->addChild('person_name', $this->escapeValue($receiverAddressee->getPersonName()));
            if ($receiverAddressee->getPhone()) {
                $receiverAddresseeNode->addChild('phone', $this->escapeValue($receiverAddressee->getPhone()));
            }
            if ($receiverAddressee->getMobile()) {
                $receiverAddresseeNode->addChild('mobile', $this->escapeValue($receiverAddressee->getMobile()));
            }
            if ($receiverAddressee->getEmail()) {
                $receiverAddresseeNode->addChild('email', $this->escapeValue($receiverAddressee->getEmail(), 'email'));
            }
            $address = $receiverAddressee->getAddress();
            $addressNode = $receiverAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $this->escapeValue($address->getPostcode()));
            }
            if($address->getOffloadPostcode() && in_array($package->getService(), self::TERMINAL_SERVICES)) {
                $addressNode->addAttribute('offloadPostcode', $address->getOffloadPostcode());
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $this->escapeValue($address->getDeliverypoint()));
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $this->escapeValue($address->getStreet()));
            }
            $addressNode->addAttribute('country', $this->escapeValue($address->getCountry()));

            // Sender contact data.
            $senderAddressee = $package->getSenderContact();
            $senderAddresseeNode = $item->addChild('returnAddressee');
            $senderAddresseeNode->addChild('person_name', $this->escapeValue($senderAddressee->getPersonName()));
            if ($senderAddressee->getPhone()) {
                $senderAddresseeNode->addChild('phone', $this->escapeValue($senderAddressee->getPhone()));
            }
            if ($senderAddressee->getMobile()) {
                $senderAddresseeNode->addChild('mobile', $this->escapeValue($senderAddressee->getMobile()));
            }
            if ($senderAddressee->getEmail()) {
                $senderAddresseeNode->addChild('email', $this->escapeValue($senderAddressee->getEmail(), 'email'));
            }
            $address = $senderAddressee->getAddress();
            $addressNode = $senderAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $this->escapeValue($address->getPostcode()));
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $address->getDeliverypoint());
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $this->escapeValue($address->getStreet()));
            }
            $addressNode->addAttribute('country', $this->escapeValue($address->getCountry()));
        }
        return $xml;
    }

    /**
     * @return array
     */
    private function calcMpsPackages()
    {
        $mpsPackages = array();
        $packages = $this->getPackages();
        foreach ($packages as $package) {
            if ( ! isset($mpsPackages[$package->getId()]) ) {
                $mpsPackages[$package->getId()] = 0;
            }
            $mpsPackages[$package->getId()]++;
        }

        return $mpsPackages;
    }

    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    private function escapeValue( $value, $type = '' )
    {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
                break;
            default:
                return htmlspecialchars($value);
        }

        return $value;
    }
}

<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;
use Mijora\Omniva\Shipment\Package\Contact;
use Mijora\Omniva\Shipment\Request\CallCourierOmxRequest;
use Mijora\Omniva\Shipment\Request\CancelCourierOmxRequest;

class CallCourier
{

    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var Contact
     */
    private $sender;
    
    /**
     * destinationCountry
     *
     * @var string
     */
     private $destinationCountry;
    
    /** @var string Comment for courier. OMX Only */
    private $comment;
    
    /** @var bool Flag to mark a need of two man pick up. OMX Only */
    private $isTwoManPickup = false;
    
    /** @var bool Flag to mark there is packages heavier than 30Kg. OMX Only */
    private $isHeavyPackage = false;
    
    /*
     * @var string
     */
    private $earliestPickupTime = '8:00';
    
    /*
     * @var string
     */
    private $latestPickupTime = '17:00';

    private $parcelsNumber = 1;

    private $debugData = array();

    /* 
     * @param string $time
     * @return CallCourier
     */
    public function setEarliestPickupTime($time)
    {
        $this->earliestPickupTime = $time;
        return $this;
    }

    /* 
     * @param string $time
     * @return CallCourier
     */
    public function setLatestPickupTime($time)
    {
        $this->latestPickupTime = $time;
        return $this;
    }

    public function setParcelsNumber($number)
    {
        $this->parcelsNumber = ($number > 0) ? $number : 1;
        return $this;
    }

    public function getParcelsNumber()
    {
        return $this->parcelsNumber;
    }

    /* 
     * @param Contact $sender
     * @return CallCourier
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Adds comment to courier. OMX only
     * 
     * @param string $comment Comment for courier
     * 
     * @return CallCourier
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
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
     * OMX only
     * 
     * @param bool $isHeavyPackage Default FALSE
     * 
     * @return CallCourier
     */
    public function setIsHeavyPackage($isHeavyPackage = false)
    {
        $this->isHeavyPackage = (bool) $isHeavyPackage;

        return $this;
    }

    /**
     * OMX only
     * 
     * @param bool $isTwoManPickup Default FALSE
     * 
     * @return CallCourier
     */
    public function setIsTwoManPickup($isTwoManPickup = false)
    {
        $this->isTwoManPickup = (bool) $isTwoManPickup;

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $api_url
     * 
     * @return CallCourier
     */
    public function setAuth($username, $password, $api_url = 'https://edixml.post.ee', $debug = false) {
        $this->request = new Request($username, $password, $api_url, $debug);
        return $this;
    }

    /**
     * Creates OMX Request object from current CallCourer information
     * 
     * @return CallCourierOmxRequest
     */
    public function getCallCourierOmxRequest()
    {
        return (new CallCourierOmxRequest())
            ->setCustomerCode($this->request->getUsername())
            ->setPickupContact($this->sender)
            ->setStartTime($this->earliestPickupTime)
            ->setEndTime($this->latestPickupTime)
            ->setComment($this->getComment())
            ->setPackageCount($this->getParcelsNumber())
            ->setIsHeavyPackage($this->isHeavyPackage)
            ->setIsTwoManPickup($this->isTwoManPickup)
        ;
    }

    /**
     * @return string|bool Returns courier call order number, that can be used for cancelation. Returns false on failure.
     */
    public function callCourierOmx()
    {
        $request = $this->getCallCourierOmxRequest();

        $response = json_decode((string) $this->request->callOmxApi($request), true);

        /* RESPONSE EXAMPLE
            {
                "courierOrderNumber": "4141146",
                "startTime": "2023-03-30T20:18:00",
                "endTime": "2023-03-30T20:19:00"
            }
        */

        return $response['courierOrderNumber'] ?? false;
    }

    /**
     * @return bool Returns if courier order was succesfully canceled.
     */
    public function cancelCourierOmx($courier_order_number)
    {
        $request = (new CancelCourierOmxRequest())
            ->setCustomerCode($this->request->getUsername())
            ->setCourierOrderNumber($courier_order_number)
        ;

        $response = json_decode((string) $this->request->callOmxApi($request), true);

        /* RESPONSE EXAMPLE
            {
                "courierOrderNumber": "4141146",
                "resultCode": "OK"
            }
        */

        $result = $response['resultCode'] ?? '';

        return strtoupper($result) === 'OK';
    }

    /**
     * @var bool $use_legacy_api Default FALSE to use OMX API, using TRUE switches to old XML request
     * 
     * @return string|boolean
     */
    public function callCourier($use_legacy_api = false)
    {
        if (!$use_legacy_api) {
            return $this->callCourierOmx();
        }

        if (empty($this->request)) {
            throw new OmnivaException("Please set username and password");
        }
        $result = $this->request->call($this->buildXml());

        if (isset($result['debug'])) {
            $this->setDebugData($result['debug']);
        }
        if (isset($result['barcodes']) && !empty($result['barcodes'])) {
            return true;
        }
        return false;
    }

    /*
     * @return string
     */
    private function buildXml()
    {
        $pickStart = $this->earliestPickupTime;
        $pickFinish = $this->latestPickupTime;
        $pickDay = date('Y-m-d');
        if (time() > strtotime($pickDay . ' ' . $pickStart)) {
            $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
        }
        $shop_address = $this->sender->getAddress();
        $shop_phone = (! empty($this->sender->getPhone())) ? $this->sender->getPhone() : $this->sender->getMobile();
        $serviceCode = $this->getServiceCode();
        $xml = '
        <interchange msg_type="info11">
            <header file_id="' . \Date('YmdHms') . '" sender_cd="' . $this->request->getUsername() . '" >    
            </header>
            <item_list>';
        for ( $i = 0; $i < $this->getParcelsNumber(); $i++ ) {
            $xml .= '
                <item service="' . $serviceCode .'" >
                    <measures weight="1" />
                    <receiverAddressee >
                       <person_name>' . $this->sender->getPersonName() . '</person_name>
                       <phone>' . $shop_phone . '</phone>
                       <address postcode="' . $shop_address->getPostCode() . '" deliverypoint="' . $shop_address->getDeliveryPoint() . '" country="' .  $shop_address->getCountry() . '" street="' . $shop_address->getStreet() . '" />
                    </receiverAddressee>
                    <returnAddressee>
                       <person_name>' . $this->sender->getPersonName() . '</person_name>
                       <phone>' . $shop_phone . '</phone>
                       <address postcode="' . $shop_address->getPostCode() . '" deliverypoint="' . $shop_address->getDeliveryPoint() . '" country="' .  $shop_address->getCountry() . '" street="' . $shop_address->getStreet() . '" />
                    </returnAddressee>
                    <onloadAddressee>
                       <person_name>' . $this->sender->getPersonName() . '</person_name>
                       <phone>' . $shop_phone . '</phone>
                       <address postcode="' . $shop_address->getPostCode() . '" deliverypoint="' . $shop_address->getDeliveryPoint() . '" country="' .  $shop_address->getCountry(). '" street="' . $shop_address->getStreet() . '" />
                       <pick_up_time start="' . date("Y.m.d H:i", strtotime($pickDay . ' ' . $pickStart)) . '" finish="' . date("Y.m.d H:i", strtotime($pickDay . ' ' . $pickFinish)) . '"/>
                    </onloadAddressee>
                </item>';
        }
        $xml .= '
            </item_list>
        </interchange>';
        return $xml;
    }

    private function getServiceCode()
    {
        if($this->destinationCountry == 'estonia')
        {
            return 'CI';
        }
        elseif($this->destinationCountry == 'finland')
        {
            return 'CE';
        }
        
        return 'QH';
    }

    private function setDebugData( $debug_data )
    {
        $this->debugData = $debug_data;

        return $this;
    }

    public function getDebugData()
    {
        return $this->debugData;
    }

    /**
     * Get destinationCountry
     *
     * @return  string
     */ 
    public function getDestinationCountry()
    {
        return $this->destinationCountry;
    }

    /**
     * Set destinationCountry
     *
     * @param  string  $destinationCountry  destinationCountry
     *
     * @return  self
     */ 
    public function setDestinationCountry($destinationCountry)
    {
        $this->destinationCountry = $destinationCountry;

        return $this;
    }
}

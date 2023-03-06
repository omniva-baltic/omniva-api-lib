<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;

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

    /*
     * @param string $username
     * @param string $password
     * @param string $api_url
     */
    public function setAuth($username, $password, $api_url = 'https://edixml.post.ee', $debug = false) {
        $this->request = new Request($username, $password, $api_url, $debug);
    }

    /*
     * @return boolean
     */
    public function callCourier()
    {
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
                       <phone>' . ($this->sender->getPhone() ?? $this->sender->getMobile()) . '</phone>
                       <address postcode="' . $shop_address->getPostCode() . '" deliverypoint="' . $shop_address->getDeliveryPoint() . '" country="' .  $shop_address->getCountry() . '" street="' . $shop_address->getStreet() . '" />
                    </receiverAddressee>
                    <returnAddressee>
                       <person_name>' . $this->sender->getPersonName() . '</person_name>
                       <phone>' . ($this->sender->getPhone() ?? $this->sender->getMobile()) . '</phone>
                       <address postcode="' . $shop_address->getPostCode() . '" deliverypoint="' . $shop_address->getDeliveryPoint() . '" country="' .  $shop_address->getCountry() . '" street="' . $shop_address->getStreet() . '" />
                    </returnAddressee>
                    <onloadAddressee>
                       <person_name>' . $this->sender->getPersonName() . '</person_name>
                       <phone>' . ($this->sender->getPhone() ?? $this->sender->getMobile()) . '</phone>
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

<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;

class Shipment
{
    const CONTACT_TYPES = ['receiver' => 'receiverAddressee', 'sender' => 'returnAddressee'];

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
     * @var array
     */
    private $packages;
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var array
     */
    private $terminalServices = [
         'PU', 'PA', 'PK', 'PP'
     ];
    
    public function setAuth($username, $password)
    {
        $this->request = new Request($username, $password);
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
    public function setShipmentHeader($header)
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
    public function registerShipment()
    {
        if ( empty($this->request) ) {
            throw new OmnivaException("Please set username and password");
        }
        return $this->request->call($this->toXml()->asXML());
    }

    /**
     * @return array
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param array $packages
     * @return Shipment
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;
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
    public function setComment($comment)
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
    public function setShowReturnCodeSms($showReturnCodeSms)
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
    public function setShowReturnCodeEmail($showReturnCodeEmail)
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
    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
        return $this;
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
        foreach ($packages as $package) {
            $item = $itemList->addChild('item');
            if ($package->getService()) {
                $item->addAttribute('service', $package->getService());
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
            $receiverAddresseeNode->addChild('person_name', $this->escape_value($receiverAddressee->getPersonName()));
            if ($receiverAddressee->getPhone()) {
                $receiverAddresseeNode->addChild('phone', $this->escape_value($receiverAddressee->getPhone()));
            }
            if ($receiverAddressee->getMobile()) {
                $receiverAddresseeNode->addChild('mobile', $this->escape_value($receiverAddressee->getMobile()));
            }
            if ($receiverAddressee->getEmail()) {
                $receiverAddresseeNode->addChild('email', $this->escape_value($receiverAddressee->getEmail(), 'email'));
            }
            $address = $receiverAddressee->getAddress();
            $addressNode = $receiverAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $this->escape_value($address->getPostcode()));
            }
            if($address->getOffloadPostcode() && in_array($package->getService(), $this->terminalServices)) {
                $addressNode->addAttribute('offloadPostcode', $address->getOffloadPostcode());
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $this->escape_value($address->getDeliverypoint()));
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $this->escape_value($address->getStreet()));
            }
            $addressNode->addAttribute('country', $this->escape_value($address->getCountry()));

            // Sender contact data.
            $senderAddressee = $package->getSenderContact();
            $senderAddresseeNode = $item->addChild('returnAddressee');
            $senderAddresseeNode->addChild('person_name', $this->escape_value($senderAddressee->getPersonName()));
            if ($senderAddressee->getPhone()) {
                $senderAddresseeNode->addChild('phone', $this->escape_value($senderAddressee->getPhone()));
            }
            if ($senderAddressee->getMobile()) {
                $senderAddresseeNode->addChild('mobile', $this->escape_value($senderAddressee->getMobile()));
            }
            $address = $senderAddressee->getAddress();
            $addressNode = $senderAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $this->escape_value($address->getPostcode()));
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $address->getDeliverypoint());
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $this->escape_value($address->getStreet()));
            }
            $addressNode->addAttribute('country', $this->escape_value($address->getCountry()));
        }
        return $xml;
    }

    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    private function escape_value($value, $type = '')
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

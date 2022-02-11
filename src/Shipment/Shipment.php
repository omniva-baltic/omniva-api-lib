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
         'PU', 'PA'
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

    private function getLabelHeaderXml($headerData)
    {
        if(!isset($headerData['sender_cd'])) {
            throw new OmnivaException("Incorrect XML data provided: Sender ID (sender_cd) is required.");
        }
        $attributes = [
            'sender_cd' => $headerData['sender_cd'],
            'file_id' => isset($headerData['file_id']) ? $headerData['file_id'] : '',
            'prep_date_time' => isset($headerData['file_id']) ? $headerData['file_id'] : '',
        ];
        return $this->helper->extendTagWithAttributes('header', $attributes);
    }

    private function getItemListXml($itemListData)
    {
        $itemListAttributes = [
            'show_return_code_sms' => isset($itemListData['show_return_code_sms']) && $itemListData['show_return_code_sms'],
            'show_return_code_email' => isset($itemListData['show_return_code_email']) && $itemListData['show_return_code_email'],
            'partnerId' => isset($itemListData['partnerId']) && $itemListData['partnerId'],
        ];
        $xml = $this->helper->extendTagWithAttributes('item_list', $itemListAttributes, false);
        if(isset($itemListData['comment']))
            $xml .= $this->getCommentXml($itemListData['comment']);
        $xml .= $this->getItemsXml();

        $xml .= '</item_list>';
        return $xml;
    }

    private function getCommentXml($comment)
    {
        return "<comment>${comment}</comment>";
    }

    public function getItemsXml($itemsData)
    {
        if (!isset($itemsData['service']))
            throw new OmnivaException("Incorrect XML data provided in: Item service code is required.");
        $itemAttributes = [
            'id' => isset($itemsData['id']) ? $itemsData['id'] : '',
            'service' => $itemsData['service'],
            'packetUnitIdentificator' => isset($itemsData['packetUnitIdentificator']) ? $itemsData['packetUnitIdentificator'] : '',
        ];

        $itemTag = $this->helper->extendTagWithAttributes('item', $itemAttributes, false);

        $xml = '';
        foreach ($itemsData['packages'] as $package)
        {
            $xml .= $itemTag;
            $additional_services = [];
            if(isset($package['additional_services']))
            {
                $additional_services = $package['additional_services'];
                $this->getAdditionalServicesXml($additional_services);
            }
            if(!isset($package['measures_data']))
                throw new OmnivaException("Incorrect XML data provided: Measurement data is required.");

            $xml .= $this->getMeasuresXml($package['measures_data']);

            $xml .= $this->getMonetaryXml($additional_services, $package['monetary_data']);

            if(!isset($itemsData['receiver_contacts']))
                throw new OmnivaException("Incorrect XML data provided: receiver address data is required.");

            if(!isset($itemsData['sender_contacts']))
                throw new OmnivaException("Incorrect XML data provided: sender address data is required.");

            $xml .= $this->getContactXml('receiver', $itemsData['receiver_contacts']);
            $xml .= $this->getContactXml('sender', $itemsData['sender_contacts']);
            $xml .= '</item>';
        }
        return $xml;
    }

    private function getAdditionalServicesXml($serviceData)
    {
        $xml = '<add_service>';
        if(is_array($serviceData) && !empty($serviceData))
        {
            foreach ($serviceData as $serviceDatum)
            {
                if(isset($serviceDatum['code']))
                    $xml .= "<option =\"${$serviceDatum['code']}\">";
            }
        }
        $xml .= '</add_service>';
        return $xml;
    }

    private function getMeasuresXml($measuresData)
    {
        if(!isset($measuresData['weight']))
            throw new OmnivaException("Incorrect XML data provided in Measures section: weight is required.");
        $measuresAttributes = [
            'weight' => $measuresData['weight'],
            'length' => isset($measuresData['length']) ? $measuresData['length'] : 0,
            'width' => isset($measuresData['width']) ? $measuresData['width'] : 0,
            'height' => isset($measuresData['height']) ? $measuresData['height'] : 0,
        ];
        return $this->helper->extendTagWithAttributes('measures', $measuresAttributes);

    }

    private function getContactXml($type, $contactData)
    {
        if(!in_array($type, self::CONTACT_TYPES))
        {
            throw new OmnivaException("Incorrect contact type provided in Label::getContactXml. Allowed types: " . print_r(self::CONTACT_TYPES, true));
        }
        if(isset($contactData['person_name']))
            throw new OmnivaException("Incorrect XML data provided in ${$type} contact section: person_name is required.");
        // todo: Postal code; Usually required. Not required when the destination is the
        //parcel machine or Estonian post office (look at offloadPostcode).//NB!
        if(isset($contactData['postcode']))
            throw new OmnivaException("Incorrect XML data provided in ${$type} contact section: postcode is required.");
        if(isset($contactData['offloadPostcode']))
            throw new OmnivaException("Incorrect XML data provided in ${$type} contact section: offloadPostcode is required.");
        if(isset($contactData['deliverypoint']))
            throw new OmnivaException("Incorrect XML data provided in ${$type} contact section: deliverypoint is required.");
        if(isset($contactData['country']))
            throw new OmnivaException("Incorrect XML data provided in ${$type} contact section: country is required.");

        // Start building XML.
        $contactTag = self::CONTACT_TYPES[$type];
        $xml = "<${$contactTag}>";
        $xml .= "<person_name>${$contactData['person_name']}</person_name>";

        $address = $contactData['address'];
        $addressAttributes = [
            'postcode' => $address['postcode'],
            'offloadPostcode' => $address['offloadPostcode'],
            'deliverypoint' => $address['deliverypoint'],
            'street' => $address['street'],
            'country' => $address['country'],
        ];
        $addressTag = $this->helper->extendTagWithAttributes('address', $addressAttributes);
        $xml .= $addressTag;

        if(isset($contactData['phone']))
            $xml .= "<phone>${$contactData['phone']}</phone>";
        if(isset($contactData['mobile']))
            $xml .= "<mobile>${$contactData['mobile']}</mobile>";
        if(isset($contactData['email']))
            $xml .= "<email>${$contactData['email']}</email>";

        $xml .= "</${$contactTag}>";
        return $xml;
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
        return $this->showReturnCodeSms;
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
        return $this->showReturnCodeEmail;
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
        
        if($this->getComment()) {
            $head->addChild('comment', $this->getComment());
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
		if ($measures->getWidth()) {
                    $measuresNode->addAttribute('width', $measures->getWidth());
		}
		if ($measures->getVolume()) {
                    $measuresNode->addAttribute('volume', $measures->getVolume());
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
            if($this->isShowReturnCodeSms()) {
                $item->addChild('show_return_code_sms', true);
            }
            if($this->isShowReturnCodeEmail()) {
                $item->addChild('show_return_code_email', true);
            }
            if($this->getPartnerId()) {
                $item->addChild('partnerId', $this->getPartnerId());
            }
        
            // Receiver contact data.
            $receiverAddressee = $package->getReceiverContact();
            $receiverAddresseeNode = $item->addChild('receiverAddressee');
            $receiverAddresseeNode->addChild('person_name', $receiverAddressee->getPersonName());
            if ($receiverAddressee->getPhone()) {
                $receiverAddresseeNode->addChild('phone', $receiverAddressee->getPhone());
            }
            if ($receiverAddressee->getMobile()) {
                $receiverAddresseeNode->addChild('mobile', $receiverAddressee->getMobile());
            }
            $address = $receiverAddressee->getAddress();
            $addressNode = $receiverAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $address->getPostcode());
            }
            if($address->getOffloadPostcode() && in_array($package->getService(), $this->terminalServices)) {
                $addressNode->addAttribute('offloadPostcode', $address->getOffloadPostcode());
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $address->getDeliverypoint());
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $address->getStreet());
            }
            $addressNode->addAttribute('country', $address->getCountry());

            // Sender contact data.
            $senderAddressee = $package->getSenderContact();
            $senderAddresseeNode = $item->addChild('returnAddressee');
            $senderAddresseeNode->addChild('person_name', $senderAddressee->getPersonName());
            if ($senderAddressee->getPhone()) {
                $senderAddresseeNode->addChild('phone', $senderAddressee->getPhone());
            }
            if ($senderAddressee->getMobile()) {
                $senderAddresseeNode->addChild('mobile', $senderAddressee->getMobile());
            }
            $address = $senderAddressee->getAddress();
            $addressNode = $senderAddresseeNode->addChild('address');
            if($address->getPostcode()) {
                $addressNode->addAttribute('postcode', $address->getPostcode());
            }
            if($address->getDeliverypoint()) {
                $addressNode->addAttribute('deliverypoint', $address->getDeliverypoint());
            }
            if($address->getStreet()) {
                $addressNode->addAttribute('street', $address->getStreet());
            }
            $addressNode->addAttribute('country', $address->getCountry());
        }
        return $xml;
    }

    private function getMonetaryXml($monetaryData, $services)
    {
        if(array_search('BP', $services) !== false && !isset($monetaryData['amount']))
            throw new OmnivaException("Incorrect XML data provided in Monetary section: amount is required, when additional service BP is used.");
        if(array_search('BP', $services) !== false && !isset($monetaryData['account']))
            throw new OmnivaException("Incorrect XML data provided in Monetary section: account is required, when additional service BP is used.");
        $xml = '<monetary_values>';
        $xml .= '<values code="item_value" amount="' . $monetaryData['amount'] . '"/>
                <account>' . $monetaryData['account'] . '</account>';
        if(isset($monetaryData['reference_number']))
            $xml .= '<reference_number>' . $monetaryData['reference_number'] . '</reference_number>';
        $xml .= '</monetary_values>';

        return $xml;
    }
}
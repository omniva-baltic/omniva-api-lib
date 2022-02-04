<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;

class Package
{
    const COD_ADDITIONAL_SERVICE_CODE = 'BP';

    const ZIP_NOT_REQUIRED_SERVICES = ['PA', 'PU', 'PK', 'PV', 'PO', 'PP'];

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $packetUnitIdentificator;

    /**
     * @var array
     */
    private $additionalServices;

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
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $service
     * @return Package
     */
    public function setService($service)
    {
        $this->service = $service;
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
     * @param array $additionalServices
     * @return Package
     */
    public function setAdditionalServices($additionalServices)
    {
        $this->additionalServices = $additionalServices;
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
        if(!$measures->getWeight())
            throw new OmnivaException("Incorrect XML data provided in Measures section: weight is required.");
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
    public function setCod($cod)
    {
        if(!$cod->getAmount() && $this->containsCodAdditionalService())
            throw new OmnivaException("Incorrect XML data provided in Monetary section: amount is required, when additional service BP is used.");
        if(!$cod->getBankAccount() && $this->containsCodAdditionalService())
            throw new OmnivaException("Incorrect XML data provided in Monetary section: account is required, when additional service BP is used.");
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
        if(!$senderContact->getPersonName())
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
        if(!$receiverContact->getPersonName())
            throw new OmnivaException("Incorrect XML data provided in contact section: person_name is required.");
        $this->validateAddress($receiverContact->getAddress(), false);
        $this->receiverContact = $receiverContact;
        return $this;
    }

    public function containsCodAdditionalService()
    {
        foreach ($this->additionalServices as $additionalService)
        {
            if($additionalService->getServiceCode() == self::COD_ADDITIONAL_SERVICE_CODE)
                return true;
        }
        return false;
    }

    public function validateAddress($address, $sender = false)
    {
		$address_type = $sender ? 'sender' : 'receiver';
        if((!in_array($this->service, self::ZIP_NOT_REQUIRED_SERVICES) || $sender) && !$address->getPostcode())
            throw new OmnivaException("Incorrect XML data provided in $address_type contact section: postcode is required.");
        if(in_array($this->service, self::ZIP_NOT_REQUIRED_SERVICES) && !$sender && !$address->getOffloadPostcode())
            throw new OmnivaException("Incorrect XML data provided in $address_type contact section: offloadPostcode is required, when using services " . print_r(self::ZIP_NOT_REQUIRED_SERVICES, true) . ".");
        if((!in_array($this->service, self::ZIP_NOT_REQUIRED_SERVICES) || $sender) && !$address->getDeliveryPoint())
            throw new OmnivaException("Incorrect XML data provided in $address_type contact section: delivery point is required.");
        if(!$address->getCountry())
            throw new OmnivaException("Incorrect XML data provided in $address_type contact section: country is required.");
    }
}
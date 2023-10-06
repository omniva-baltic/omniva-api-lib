<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\Helper;
use Mijora\Omniva\OmnivaException;

class Address
{
    /**
     * @var string
     */
    private $postcode;

    /**
     * @var string
     */
    private $offloadPostcode;

    /**
     * @var string
     */
    private $deliverypoint;

    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $country;

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return Address
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getOffloadPostcode()
    {
        return $this->offloadPostcode;
    }

    /**
     * @param string $offloadPostcode
     * @return Address
     */
    public function setOffloadPostcode($offloadPostcode)
    {
        $this->offloadPostcode = $offloadPostcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliverypoint()
    {
        return $this->deliverypoint;
    }

    /**
     * @param string $deliverypoint
     * @return Address
     */
    public function setDeliverypoint($deliverypoint)
    {
        $this->deliverypoint = $deliverypoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Address
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getAddressForOmx($delivery_channel = null)
    {
        if ($delivery_channel && Package::isOffloadPostcodeRequired($delivery_channel)) {
            if (!$this->getOffloadPostcode()) {
                throw new OmnivaException($delivery_channel . " requires offloadPostcode to be set");
            }

            return [
                'country' => Helper::escapeForApi($this->getCountry()),
                'offloadPostcode' => (string) $this->getOffloadPostcode(),
            ];
        }

        // normal address
        return [
            'country' => Helper::escapeForApi($this->getCountry()),
            'deliverypoint' => Helper::escapeForApi($this->getDeliverypoint()),
            'postcode' => Helper::escapeForApi($this->getPostcode()),
            'street' => Helper::escapeForApi($this->getStreet()),
        ];
    }
}

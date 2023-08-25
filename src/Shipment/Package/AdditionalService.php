<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;

class AdditionalService
{
    /**
     * @var string
     */
    private $serviceCode;

    public function getServiceCode()
    {
        return $this->serviceCode;
    }

    /**
     * Set additional service code
     * 
     * @param string $serviceCode service code to set
     * 
     * @return AdditionalService
     */
    public function setServiceCode($serviceCode)
    {
        $this->serviceCode = $serviceCode;

        return $this;
    }
}

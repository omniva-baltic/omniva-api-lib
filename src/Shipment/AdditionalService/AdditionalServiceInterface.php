<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

interface AdditionalServiceInterface
{
    /**
     * Returns additional service code
     * 
     * @return string
     */
    public function getServiceCode();
    
    /**
     * Returns additional service params as array of key value if additional service has params. Otherwise returns null.
     * 
     * @return array|null
     */
    public function getServiceParams();

}
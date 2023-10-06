<?php

namespace Mijora\Omniva\Shipment\Request;

use JsonSerializable;

interface OmxRequestInterface extends JsonSerializable
{
    const REQUEST_METHOD_POST = 'POST';

    const REQUEST_METHOD_GET = 'GET';

    /**
     * Returns given request endpoint, eg. 'shipments/business-to-client'
     * @return string
     */
    public function getOmxApiEndpoint();
    
    /**
     * Returns request method to use with curl. Only methods from OmxRequestInterface::REQUEST_METHOD_* should be returned.
     * Otherwise there is a risk of error on API call
     * 
     * @return string
     */
    public function getRequestMethod();

    #[\ReturnTypeWillChange]
    public function jsonSerialize();
}
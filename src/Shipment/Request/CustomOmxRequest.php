<?php

namespace Mijora\Omniva\Shipment\Request;

class CustomOmxRequest implements OmxRequestInterface
{
    private $endpoint;

    private $method = OmxRequestInterface::REQUEST_METHOD_POST;

    private $params = [];

    /**
     * Set custom OMX api endpoint
     * 
     * @param string $endpoint
     * 
     * @return CustomOmxRequest
     */
    public function setEndpoint($endpoint = '')
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Set what method OMX API call should use. Eg. 'POST', 'GET'
     * 
     * @param string $method Request method, eg. 'POST', 'GET'
     * 
     * @return CustomOmxRequest
     */
    public function setRequestMethod($method = OmxRequestInterface::REQUEST_METHOD_POST)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set array of parameters that will be used as request body in API call
     * 
     * @param array $params
     * 
     * @return CustomOmxRequest
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOmxApiEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->params;
    }
}

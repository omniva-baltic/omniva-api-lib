<?php

namespace Mijora\Omniva\Shipment\Request;

class EventsOmxRequest implements OmxRequestInterface
{
    const API_ENDPOINT = 'shipments/'; // attach barcode at the end

    /** @var string Barcode for which to search events for */
    private $barcode;

    public function setBarcode($barcode)
    {
        $this->barcode = (string) $barcode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOmxApiEndpoint()
    {
        return self::API_ENDPOINT . $this->barcode;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return OmxRequestInterface::REQUEST_METHOD_GET;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return null;
    }
}

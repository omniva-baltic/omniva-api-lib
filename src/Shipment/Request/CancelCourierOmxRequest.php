<?php

namespace Mijora\Omniva\Shipment\Request;

class CancelCourierOmxRequest implements OmxRequestInterface
{
    const API_ENDPOINT = 'courierorders/cancel-pickup-order';

    /** @var string PARTNER_CODE (AXA Code) of the Customer, agreed upon previously. MANDATORY */
    private $customerCode;

    /** @var string Courier order number to be cancelled */
    private $courierOrderNumber;

    public function setCustomerCode($code)
    {
        $this->customerCode = (string) $code;

        return $this;
    }

    public function setCourierOrderNumber($order_number)
    {
        $this->courierOrderNumber = (string) $order_number;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOmxApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return OmxRequestInterface::REQUEST_METHOD_POST;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'customerCode' => $this->customerCode,
            'courierOrderNumber' => $this->courierOrderNumber,
        ];
    }

    /*  EXAMPLE REQUEST BODY
        {
            "customerCode": "12345",
            "courierOrderNumber": "4140982"
        }
    */
}

<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class DeliveryToAnAdultService implements AdditionalServiceInterface
{
    const CODE = 'DELIVERY_TO_AN_ADULT';

    const PARAMS_LIST = [];

    /**
     * {@inheritdoc}
     */
    public function getServiceCode()
    {
        return self::CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceParams()
    {
        return null;
    }
}

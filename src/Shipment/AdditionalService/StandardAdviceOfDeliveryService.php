<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class StandardAdviceOfDeliveryService implements AdditionalServiceInterface
{
    const CODE = 'STANDARD_ADVICE_OF_DELIVERY';

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

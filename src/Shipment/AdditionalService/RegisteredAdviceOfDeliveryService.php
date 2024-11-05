<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class RegisteredAdviceOfDeliveryService implements AdditionalServiceInterface
{
    const CODE = 'REGISTERED_ADVICE_OF_DELIVERY';

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

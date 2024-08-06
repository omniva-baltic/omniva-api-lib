<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class SameDayDeliveryService implements AdditionalServiceInterface
{
    const CODE = 'SAME_DAY_DELIVERY';

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

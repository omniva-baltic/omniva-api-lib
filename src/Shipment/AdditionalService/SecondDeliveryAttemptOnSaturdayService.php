<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class SecondDeliveryAttemptOnSaturdayService implements AdditionalServiceInterface
{
    const CODE = 'SECOND_DELIVERY_ATTEMPT_ON_SATURDAY';

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

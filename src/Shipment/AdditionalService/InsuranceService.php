<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class InsuranceService implements AdditionalServiceInterface
{
    const CODE = 'INSURANCE';

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

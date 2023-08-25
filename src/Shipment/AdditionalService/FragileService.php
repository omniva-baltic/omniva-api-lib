<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class FragileService implements AdditionalServiceInterface
{
    const CODE = 'FRAGILE';

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

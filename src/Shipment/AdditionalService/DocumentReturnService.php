<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class DocumentReturnService implements AdditionalServiceInterface
{
    const CODE = 'DOCUMENT_RETURN';

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

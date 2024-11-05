<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class LetterDeliveryToASpecificPersonService implements AdditionalServiceInterface
{
    const CODE = 'LETTER_DELIVERY_TO_A_SPECIFIC_PERSON';

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

<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

class DeliveryToSpecificPersonService implements AdditionalServiceInterface
{
    const CODE = 'DELIVERY_TO_A_SPECIFIC_PERSON';

    const PARAM_DELIVERY_TO_SPECIFIC_PERSON_PERSONAL_CODE = 'DELIVERY_TO_SPECIFIC_PERSON_PERSONAL_CODE';

    const PARAMS_LIST = [
        self::PARAM_DELIVERY_TO_SPECIFIC_PERSON_PERSONAL_CODE,
    ];

    private $params = [
        self::PARAM_DELIVERY_TO_SPECIFIC_PERSON_PERSONAL_CODE => null,
    ];

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
        return $this->params;
    }

    /**
     * Personal code. Estonian services only.
     * 
     * @param string $personal_code
     * 
     * @return DeliveryToSpecificPersonService
     */
    public function setPersonalCode($personal_code)
    {
        $this->params[self::PARAM_DELIVERY_TO_SPECIFIC_PERSON_PERSONAL_CODE] = $personal_code;

        return $this;
    }
}

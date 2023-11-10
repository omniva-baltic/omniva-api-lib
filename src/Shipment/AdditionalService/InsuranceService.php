<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

use Mijora\Omniva\OmnivaException;

class InsuranceService implements AdditionalServiceInterface
{
    const CODE = 'INSURANCE';

    const PARAM_INSURANCE_VALUE = "INSURANCE_VALUE";

    const PARAMS_LIST = [
        self::PARAM_INSURANCE_VALUE,
    ];

    private $params = [
        self::PARAM_INSURANCE_VALUE => null,
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

    public function setParam($key, $value)
    {
        if (!in_array($key, self::PARAMS_LIST)) {
            throw new OmnivaException("Invalid InsuranceService param key");
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Insurance value. The separator for the fraction is a decimal point.
     * 
     * @param float $amount
     * 
     * @return InsuranceService
     */
    public function setInsuranceValue($value)
    {
        return $this->setParam(self::PARAM_INSURANCE_VALUE, (float) $value);
    }
}

<?php

namespace Mijora\Omniva\Shipment\AdditionalService;

use Mijora\Omniva\OmnivaException;

class CodService implements AdditionalServiceInterface
{
    const CODE = 'COD';

    const PARAM_COD_RECEIVER = "COD_RECEIVER";
    const PARAM_COD_AMOUNT = "COD_AMOUNT";
    const PARAM_COD_BANK_ACCOUNT_NO = "COD_BANK_ACCOUNT_NO";
    const PARAM_COD_REFERENCE_NO = "COD_REFERENCE_NO";

    const PARAMS_LIST = [
        self::PARAM_COD_RECEIVER,
        self::PARAM_COD_AMOUNT,
        self::PARAM_COD_BANK_ACCOUNT_NO,
        self::PARAM_COD_REFERENCE_NO,
    ];

    private $params = [
        self::PARAM_COD_RECEIVER => null,
        self::PARAM_COD_AMOUNT => null,
        self::PARAM_COD_BANK_ACCOUNT_NO => null,
        self::PARAM_COD_REFERENCE_NO => null,
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
            throw new OmnivaException("Invalid CodService param key");
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     *  COD receiver name
     * 
     * @param string $receiver
     * 
     * @return CodService
     */
    public function setCodReceiver($receiver)
    {
        return $this->setParam(self::PARAM_COD_RECEIVER, $receiver);
    }

    /**
     * COD sum to transfer. The separator for the fraction is a decimal point.
     * 
     * @param float $amount
     * 
     * @return CodService
     */
    public function setCodAmount($amount)
    {
        return $this->setParam(self::PARAM_COD_AMOUNT, (float) $amount);
    }

    /**
     * Bank account to which COD sum must be transferred to. Must be IBAN
     * 
     * @param string $iban
     * 
     * @return CodService
     */
    public function setCodIban($iban)
    {
        return $this->setParam(self::PARAM_COD_BANK_ACCOUNT_NO, $iban);
    }

    /**
     * Reference number, validated if bank account country is EE
     * 
     * @param string $reference
     * 
     * @return CodService
     * 
     * @throws OmnivaException
     */
    public function setCodReference($reference)
    {
        if (!ctype_digit((string) $reference)) {
            throw new OmnivaException('Reference must contain only digits');
        }

        return $this->setParam(self::PARAM_COD_REFERENCE_NO, (string) $reference);
    }

    /**
     * Calculates Estonian bank invoice reference number.
     * See https://www.pangaliit.ee/settlements-and-standards/reference-number-of-the-invoice/check-digit-calculator-of-domestic-account-number
     * 
     * @param string $number_string A string of digits, e.g. order/invoice number or any other identifier consisting of digits.
     * 
     * @return string Returns initial digit string with attached check digit
     * 
     * @throws OmnivaException
     */
    public static function calculateReferenceNumber($number_string)
    {
        $number_string = (string) $number_string;
        if (!ctype_digit($number_string)) {
            throw new OmnivaException('Must contain only digits');
        }

        $kaal = array(7, 3, 1);
        $sl = $st = strlen($number_string);

        $total = 0;
        while ($sl > 0 and substr($number_string, --$sl, 1) >= '0') {
            $total += (int) substr($number_string, ($st - 1) - $sl, 1) * $kaal[($sl % 3)];
        }
        $check_digit = ((ceil(($total / 10)) * 10) - $total);

        return $number_string . (string) $check_digit;
    }
}

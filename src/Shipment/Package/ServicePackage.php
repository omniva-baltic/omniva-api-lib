<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;

class ServicePackage
{
    const CODE_ECONOMY = 'ECONOMY';
    const CODE_STANDARD = 'STANDARD';
    const CODE_PREMIUM = 'PREMIUM';
    const CODE_PROCEDURAL_DOCUMENT = 'PROCEDURAL_DOCUMENT';
    const CODE_REGISTERED_LETTER = 'REGISTERED_LETTER';
    const CODE_REGISTERED_MAXILETTER = 'REGISTERED_MAXILETTER';

    const CODE_ALL = [
        self::CODE_ECONOMY,
        self::CODE_STANDARD,
        self::CODE_PREMIUM,
        self::CODE_PROCEDURAL_DOCUMENT,
        self::CODE_REGISTERED_LETTER,
        self::CODE_REGISTERED_MAXILETTER,
    ];

    const CODE_AVAILABILITY = [
        Package::MAIN_SERVICE_PARCEL => [
            self::CODE_ECONOMY,
            self::CODE_STANDARD,
            self::CODE_PREMIUM,
        ],
        Package::MAIN_SERVICE_LETTER => [
            self::CODE_PROCEDURAL_DOCUMENT,
            self::CODE_REGISTERED_LETTER,
            self::CODE_REGISTERED_MAXILETTER,
        ],
    ];

    /**
     * Possible service package codes if main service is PARCEL: ECONOMY, STANDARD, PREMIUM,
     * Possible service package codes if main service is LETTER: PROCEDURAL_DOCUMENT, REGISTERED_LETTER, REGISTERED_MAXILETTER
     * Package availability info for parcels can be found https://www.omniva.ee/public/files/andmevahetus/International-services-availability.xlsx and for letters https://www.omniva.ee/public/files/failid/domestic-and-international-letter-services-available-1.xlsx
     * 
     * @var string
     */
    private $code;

    /**
     * Can be used and is mandatory only if servicePackage=PROCEDURAL_DOCUMENT
     * if delivery deliveryChannel = COURIER, allowed values: 0, 15, 30 (days)
     * if delivery deliveryChannel = POST_OFFICE, allowed values: 15, 30 (days)
     * 
     * @var int
     */
    private $allowedStoringPeriod;

    /**
     * @param string $code Service package code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set service package code
     * 
     * @param string $code service package code to set
     * 
     * @return ServicePackage
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAllowedStoringPeriod()
    {
        return $this->allowedStoringPeriod === null ? null : (int) $this->allowedStoringPeriod;
    }

    /**
     * Set allowed storing period. Mandatory only if servicePackage=PROCEDURAL_DOCUMENT
     * 
     * @param int $days Days to store package
     * 
     * @return ServicePackage
     */
    public function setAllowedStoringPeriod($days)
    {
        $this->allowedStoringPeriod = (int) $days;

        return $this;
    }

    /**
     * Returns array of values for ShipmentRequestOmx
     * 
     * @return array
     */
    public function getServicePackage()
    {
        $array = [
            'code' => $this->getCode(),
        ];

        if (
            self::CODE_PROCEDURAL_DOCUMENT === $this->getCode() 
            && null === $this->getAllowedStoringPeriod()
        ) {
            throw new OmnivaException("Service package code [ " . $this->getCode() . " ] requires AllowedStoringPeriod");
        }

        if (self::CODE_PROCEDURAL_DOCUMENT === $this->getCode()) {
            $array['allowedStoringPeriod'] = (int) $this->getAllowedStoringPeriod();
        }

        return $array;
    }

    /**
     * Checks code validity. If main service code is given also check if code is allowed for given main service
     * 
     * @param string $code Service package code to check
     * @param string|null $main_service Main service code to check $code availability in
     * 
     * @return bool Returns true if all checks are passed, otherwise throws OmnivaException
     * 
     * @throws OmnivaException Throws exception if any of validations does not pass
     */
    public static function checkCode($code, $main_service = null)
    {
        if (!$main_service && !in_array($code, self::CODE_ALL)) {
            throw new OmnivaException("servicePackage code must be one of [ " . implode(', ', self::CODE_ALL) . " ]");
        }

        if (!isset(self::CODE_AVAILABILITY[$main_service])) {
            throw new OmnivaException("Error Processing Request", 1);
        }

        if (!in_array($code, self::CODE_AVAILABILITY[$main_service])) {
            throw new OmnivaException("servicePackage code for main service " . $main_service
                . " must be one of [ " . implode(', ', self::CODE_AVAILABILITY[$main_service]) . " ]");
        }

        return true;
    }
}

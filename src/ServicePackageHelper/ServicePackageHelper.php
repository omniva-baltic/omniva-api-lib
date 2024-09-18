<?php

namespace Mijora\Omniva\ServicePackageHelper;

use Mijora\Omniva\Shipment\Package\ServicePackage;

class ServicePackageHelper
{
    const PATH_TO_SERVICE_JSON = __DIR__ . '/services.json';

    private static $services = null;

    public static function getServices()
    {
        if (!is_null(self::$services)) {
            return static::$services;
        }

        self::$services = json_decode(
            file_get_contents(static::PATH_TO_SERVICE_JSON),
            true
        );

        return self::$services;
    }

    public static function getCountryOptions($country)
    {
        $services = self::getServices();

        return isset($services[$country]) ? $services[$country] : [];
    }

    public static function getServicePackageCode($code)
    {
        $code = strtoupper($code);

        return ServicePackage::isCodeValid($code) ? $code : null;
    }

    /**
     * @param string $country Two letter country code
     * @param array $items Array of PackageItem
     * 
     * @return array available packages codes or empty array
     */
    public static function getAvailablePackages($country, $items)
    {
        $services = self::getCountryOptions($country);

        if (!$items || !is_array($items) || !$services) {
            return [];
        }

        $result = [];
        foreach ($services['package'] as $code => $limits) {
            // check if all fits
            $all_fits = true;
            foreach ($items as $item) {
                if (!($item instanceof PackageItem)) {
                    $all_fits = false;
                    continue;
                }

                if (
                    $item->getWeight() > (float) $limits['maxWeightKg']
                    || $item->getLongestSide() > (float) $limits['maxDimensionsM']['longestSide']
                    || $item->getPerimeterWithLongestSide() > (float) $limits['maxDimensionsM']['total']
                ) {
                    $all_fits = false;
                    break;
                }
            }

            if (!$all_fits) {
                continue;
            }

            $serviceCode = self::getServicePackageCode($code);
            if ($serviceCode) {
                $result[] = $serviceCode;
            }
        }

        return $result;
    }

    public static function getMaxInsuranceValue($country, $packageCode)
    {
        $service = self::getCountryOptions($country);
        $packageCode = strtolower($packageCode);

        return (float) (isset($service['package'][$packageCode]['insurance'])
            ? $service['package'][$packageCode]['insurance']
            : 0.0
        );
    }
}

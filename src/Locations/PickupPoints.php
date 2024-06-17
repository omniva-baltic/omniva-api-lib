<?php

namespace Mijora\Omniva\Locations;

use Mijora\Omniva\OmnivaException;

class PickupPoints
{
    const LOCATIONS_URL = 'https://www.omniva.ee/locations.json';

    /**
     * Save locations string to file
     *
     * @param string $filename
     * @param string $locations
     */
    public function saveLocationsToJSONFile($filename, $locations)
    {
        $fp = fopen($filename, 'w');
        fwrite($fp, $locations);
        fclose($fp);
    }

    /**
     * @return string
     */
    private function getLocations()
    {
        $data = file_get_contents(self::LOCATIONS_URL);
        if($data === false)
        {
            throw new OmnivaException("Could not get terminals data from Omniva.");
        }
        return $data;
    }

    /**
     * @param string $filename
     * @return mixed
     */
    public function loadLocationsFromJSONFile($filename)
    {
        $fp = fopen($filename, "r");
        $terminals = fread($fp, filesize($filename) + 10);
        fclose($fp);
        return json_decode($terminals, true);
    }

    /**
     * @param string $country
     * @param string $type
     * @param string $county
     * @return mixed
     */
    public function getFilteredLocations($country = '', $type = '', $county = '')
    {
        $filters = [];
        if($country)
            $filters['A0_NAME'] = $country;
        if($type !== '')
            $filters['TYPE'] = $type;
        if($county)
            $filters['A1_NAME'] = $county;

        $locations = json_decode($this->getLocations(), true);
        if(is_array($filters) && !empty($filters))
        {
            foreach ($locations as $key => $location)
            {
                // Check if location matches every filter provided.
                foreach ($filters as $filter_key => $filter)
                {
                    if(!isset($location[$filter_key]))
                    {
                        throw new OmnivaException('Incorrect filter key: ' . $filter_key);
                    }
                    elseif (strtolower($location[$filter_key]) != strtolower($filter))
                    {
                        unset($locations[$key]);
                        break;
                    }
                }
            }
        }
        return array_values($locations);
    }
}

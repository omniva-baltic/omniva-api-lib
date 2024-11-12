<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;

class Measures
{
    /**
     * @var float
     */
    private $weight;

    /**
     * @deprecated Not used with OMX
     * @var int
     */
    private $volume;

    /**
     * @var float
     */
    private $width;

    /**
     * @var float
     */
    private $height;

    /**
     * @var float
     */
    private $length;

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight Gross weight in kilograms
     * @return Measures
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @deprecated Not used with OMX
     * 
     * @return int
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @deprecated Not used with OMX
     * 
     * @param int $volume
     * @return Measures
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;
        return $this;
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param float $width Shipment width in m, the separator for the fraction is a decimal point
     * @return Measures
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $height Shipment height in m, the separator for the fraction is a decimal point
     * @return Measures
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return float 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param float $length Shipment length in m, the separator for the fraction is a decimal point
     * @return Measures
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }
}

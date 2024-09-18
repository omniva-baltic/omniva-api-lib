<?php

namespace Mijora\Omniva\ServicePackageHelper;

class PackageItem
{
    private $weight;
    private $length;
    private $width;
    private $height;

    /**
     * All passed parameters expected to be in meters and weight in Kg.
     */
    public function __construct($weight, $length, $width, $height = null)
    {
        $this->weight = round((float) $weight, 2);
        $this->length = round((float) $length, 2);
        $this->width = round((float) $width, 2);
        $this->height = round((float) $height, 2);
    }

    public function getLongestSide()
    {
        return max($this->length, $this->width, $this->height);
    }

    public function getPerimeter()
    {
        if ($this->length === $this->width && $this->length === $this->height) {
            return round($this->length * 4, 2);
        }

        $min = min($this->length, $this->width, $this->height);

        $perimeter = 0;
        $min_skipped = false;
        foreach ([$this->length, $this->width, $this->height] as $side) {
            if (!$min_skipped && $side === $min) {
                $min_skipped = true;
                continue;
            }

            $perimeter += $side * 2;
        }

        return round($perimeter, 2);
    }

    public function getPerimeterWithLongestSide()
    {
        return $this->getLongestSide() + $this->getPerimeter();
    }

    public function getWeight()
    {
        return $this->weight;
    }
}

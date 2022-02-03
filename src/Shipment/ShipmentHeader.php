<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;

class ShipmentHeader
{
    /**
     * @var string
     */
    private $senderCd;

    /**
     * @var string
     */
    private $fileId;

    /**
     * @var string
     */
    private $prepDateTime;

    /**
     * @return string
     */
    public function getSenderCd()
    {
        return $this->senderCd;
    }

    /**
     * @param string $senderCd
     * @return ShipmentHeader
     */
    public function setSenderCd($senderCd)
    {
        $this->senderCd = $senderCd;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * @param string $fileId
     * @return ShipmentHeader
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrepDateTime()
    {
        return $this->prepDateTime;
    }

    /**
     * @param string $prepDateTime
     * @return ShipmentHeader
     */
    public function setPrepDateTime($prepDateTime)
    {
        $this->prepDateTime = $prepDateTime;
        return $this;
    }
}
<?php

namespace Mijora\Omniva\Shipment\Request;

class PackageLabelOmxRequest implements OmxRequestInterface
{
    const API_ENDPOINT = 'shipments/package-labels';

    const SEND_TO_EMAIL = 'EMAIL';

    const SEND_TO_RESPONSE = 'RESPONSE';

    public $customerCode;

    private $sendAddressCardTo = self::SEND_TO_RESPONSE;

    private $cardReceiverEmail;

    private $barcodes = [];

    /**
     * If email is set label will be automatically sent to given email instead of returning base64 data.
     * If passed email is empty, label send to type will be changed to defaul RESPONSE type
     * 
     * @param string $email
     * 
     * @return PackageLabelOmxRequest
     */
    public function setEmail($email = '')
    {
        $this->cardReceiverEmail = $email ? $email : null;

        $this->sendAddressCardTo = $email ? self::SEND_TO_EMAIL : self::SEND_TO_RESPONSE;

        return $this;
    }

    /**
     * Add barcode or barcode array for wich label should be downloaded
     * 
     * @param array|string List of barcodes or single barcode as string
     * 
     * @return PackageLabelOmxRequest
     */
    public function addBarcode($barcodes)
    {
        if (!is_array($barcodes)) {
            $barcodes = [$barcodes];
        }

        $this->barcodes = array_merge($this->barcodes, $barcodes);

        return $this;
    }

    /**
     * Returns true if request is configured to send to email, false otherwise.
     * NOTE: if called before setting email will return false, as default is set to return PDF as base64
     * 
     * @return bool
     */
    public function isSentToEmail()
    {
        return $this->sendAddressCardTo === self::SEND_TO_EMAIL;
    }

    /**
     * {@inheritdoc}
     */
    public function getOmxApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return OmxRequestInterface::REQUEST_METHOD_POST;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $barcodes = array_map(function ($item) {
            return [
                'barcode' => $item,
            ];
        }, $this->barcodes);

        $body = [
            'customerCode' => $this->customerCode,
            'barcodes' => $barcodes,
            'sendAddressCardTo' => $this->sendAddressCardTo,
        ];

        if ($this->cardReceiverEmail) {
            $body['cardReceiverEmail'] = $this->cardReceiverEmail;
        }

        return $body;
    }
}

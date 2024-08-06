<?php

namespace Mijora\Omniva\Shipment\Request;

use Mijora\Omniva\Shipment\Package\Contact;

class CallCourierOmxRequest implements OmxRequestInterface
{
    const API_ENDPOINT = 'courierorders/create-pickup-order';

    const LIMIT_COMMENT_LENGTH = 120;

    /** @var string PARTNER_CODE (AXA Code) of the Customer, agreed upon previously. MANDATORY */
    private $customerCode;

    /** @var Contact */
    private $contact;

    /** @var string */
    private $comment;

    /** @var int */
    private $packageCount = 1;

    /** @var string */
    private $startTime = '8:00';

    /** @var string */
    private $endTime = '17:00';

    /** @var bool FALSE (default) or TRUE (If any of the packages are >30kg ) */
    private $isTwoManPickup = false;

    /** @var bool Default value is false*/
    private $isHeavyPackage = false;

    public function setCustomerCode($code)
    {
        $this->customerCode = (string) $code;

        return $this;
    }

    public function setPickupContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    public function setComment($comment = '')
    {
        $this->comment = mb_substr(
            strip_tags($comment),
            0,
            self::LIMIT_COMMENT_LENGTH
        );

        return $this;
    }

    public function setPackageCount($count = 1)
    {
        $this->packageCount = (int) $count;

        return $this;
    }

    public function setStartTime($time = '8:00')
    {
        $this->startTime = $time;

        return $this;
    }

    public function setEndTime($time = '17:00')
    {
        $this->endTime = $time;

        return $this;
    }

    public function setIsHeavyPackage($isHeavyPackage = false)
    {
        $this->isHeavyPackage = (bool) $isHeavyPackage;

        return $this;
    }

    public function setIsTwoManPickup($isTwoManPickup = false)
    {
        $this->isTwoManPickup = (bool) $isTwoManPickup;

        return $this;
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
        $address = $this->contact->getAddress();

        /* Rather ugly pickup time calculation. Posible rework to make it more sensible */
        $pickDay = date('Y-m-d');
        if (time() > strtotime($pickDay . ' ' . $this->startTime)) {
            $pickDay = date('Y-m-d', strtotime($pickDay . "+1 days"));
        }
        // "2023-03-30T20:18:00.000" - using gmdate to convert server time to UTC
        $start = gmdate("Y-m-d\TH:i:s", strtotime($pickDay . ' ' . $this->startTime)) . '.000';
        $finish = gmdate("Y-m-d\TH:i:s", strtotime($pickDay . ' ' . $this->endTime)) . '.000';
        /* Pickup time data calculations end */

        $body = [
            'customerCode' => (string) $this->customerCode,
            'contactPersonName' => $this->contact->getPersonName(),
            'contactPhone' => (string) $this->contact->getMobile(),
            'pickupAddress' => [
                'postcode' => (string) $address->getPostcode(),
                'deliverypoint' => $address->getDeliverypoint(),
                'country' => $address->getCountry(),
                'street' => $address->getStreet(),
            ],
            'startTime' => $start, //"2023-03-30T20:18:00.000",
            'endTime' => $finish, //"2023-03-30T20:19:00.000",
            'isTwoManPickup' => $this->isTwoManPickup,
            'isHeavyPackage' => $this->isHeavyPackage,
            'packageCount' => $this->packageCount > 1 ? $this->packageCount : 1,
        ];

        if ($this->comment) {
            $body['pickupComment'] = $this->comment;
        }

        return $body;
    }

    /*  EXAMPLE REQUEST BODY
        {
            "customerCode": "12345",
            "contactPersonName": "Peter Meter",
            "contactPhone": "37255555555",
            "pickupAddress": {
                "postcode": "11411",
                "deliverypoint": "Tallinn",
                "country": "EE",
                "street": "Peterburi tee",
                "houseNo": "11"
            },
            "pickupComment": "Come to the gate nr 3",
            "startTime": "2023-03-30T20:18:00.000",
            "endTime": "2023-03-30T20:19:00.000",
            "isTwoManPickup": false,
            "isHeavyPackage": false,
            "packageCount": 15,
            "palletCount": 1
        }
    */
}

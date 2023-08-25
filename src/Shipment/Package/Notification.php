<?php

namespace Mijora\Omniva\Shipment\Package;

use Mijora\Omniva\OmnivaException;

/**
 * Specification, if the notification to the sender must be sent when shipment is registered or delivered.
 */
class Notification
{
    const TYPE_REGISTERED = 'REGISTERED';
    const TYPE_DELIVERED = 'DELIVERED';

    const CHANNEL_SMS = 'sms';
    const CHANNEL_EMAIL = 'email';

    private $type;
    private $channel;

    public function setType($type)
    {
        if ($type !== self::TYPE_DELIVERED && $type !== self::TYPE_REGISTERED) {
            throw new OmnivaException('Invalid notification type. Must be Notifications::TYPE_REGISTERED or Notifications::TYPE_DELIVERED');
        }

        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setChannel($channel)
    {
        if ($channel !== self::CHANNEL_SMS && $channel !== self::CHANNEL_EMAIL) {
            throw new OmnivaException('Invalid notification channel. Must be Notifications::CHANNEL_SMS or Notifications::CHANNEL_EMAIL');
        }

        $this->channel = $channel;

        return $this;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function isValid()
    {
        return $this->type && $this->channel;
    }

    /**
     * Constructs a string key to be used to identify this Notification as unique
     * 
     * @return string Generated key
     */
    public function getTypeChannelString()
    {
        return $this->getType() . ':' . $this->getChannel();
    }

    public function getArrayForOmxRequest()
    {
        return [
            'type' => $this->getType(),
            'channel' => $this->getChannel(),
        ];
    }

}

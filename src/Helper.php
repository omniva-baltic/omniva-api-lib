<?php

namespace Mijora\Omniva;

class Helper
{
    const ESCAPE_FOR_API_TYPE_EMAIL = 1;
    /**
     * @var array
     */
    private $errorsMap = [
        'Vigane mahalaadimise postkontori sihtnumber' => 'Invalid unloading postcode',
        'Puudub teenuse PA registreerimiseks vajalik mahalaadimise sihtnumber' => 'offloadPostcode required to register PA service',
        'Vigane mahalaadimise postkontori sihtnumber' => 'Invalid offloadPostcode',
        'Saatja telefoninumber' => 'Check sender\'s phone number',
    ];
    
    /**
     * @var array
     */
    private $trackingMap = [
                'PACKET_EVENT_IPS_C' => "Shipment from country of departure",
                'PACKET_EVENT_FROM_CONTAINER' => "Arrival to post office",
                'PACKET_EVENT_IPS_D' => "Arrival to destination country",
                'PACKET_EVENT_SAVED' => "Saved",
                'PACKET_EVENT_DELIVERY_CANCELLED' => "Cancelling of delivery",
                'PACKET_EVENT_IN_POSTOFFICE' => "Arrival to Omniva",
                'PACKET_EVENT_IPS_E' => "Customs clearance",
                'PACKET_EVENT_DELIVERED' => "Delivery",
                'PACKET_EVENT_FROM_WAYBILL_LIST' => "Arrival to post office",
                'PACKET_EVENT_IPS_A' => "Acceptance of packet from client",
                'PACKET_EVENT_IPS_H' => "Delivery attempt",
                'PACKET_EVENT_DELIVERING_TRY' => "Delivery attempt",
                'PACKET_EVENT_DELIVERY_CALL' => "Preliminary calling",
                'PACKET_EVENT_IPS_G' => "Arrival to destination post office",
                'PACKET_EVENT_ON_ROUTE_LIST' => "Dispatching",
                'PACKET_EVENT_IN_CONTAINER' => "Dispatching",
                'PACKET_EVENT_PICKED_UP_WITH_SCAN' => "Acceptance of packet from client",
                'PACKET_EVENT_RETURN' => "Returning",
                'PACKET_EVENT_SEND_REC_SMS_NOTIF' => "SMS to receiver",
                'PACKET_EVENT_ARRIVED_EXCESS' => "Arrival to post office",
                'PACKET_EVENT_IPS_I' => "Delivery",
                'PACKET_EVENT_ON_DELIVERY_LIST' => "Handover to courier",
                'PACKET_EVENT_PICKED_UP_QUANTITATIVELY' => "Acceptance of packet from client",
                'PACKET_EVENT_SEND_REC_EMAIL_NOTIF' => "E-MAIL to receiver",
                'PACKET_EVENT_FROM_DELIVERY_LIST' => "Arrival to post office",
                'PACKET_EVENT_OPENING_CONTAINER' => "Arrival to post office",
                'PACKET_EVENT_REDIRECTION' => "Redirection",
                'PACKET_EVENT_IN_DEST_POSTOFFICE' => "Arrival to receiver's post office",
                'PACKET_EVENT_STORING' => "Storing",
                'PACKET_EVENT_IPS_EDD' => "Item into sorting centre",
                'PACKET_EVENT_IPS_EDC' => "Item returned from customs",
                'PACKET_EVENT_IPS_EDB' => "Item presented to customs",
                'PACKET_EVENT_IPS_EDA' => "Held at inward OE",
                'PACKET_STATE_BEING_TRANSPORTED' => "Being transported",
                'PACKET_STATE_CANCELLED' => "Cancelled",
                'PACKET_STATE_CONFIRMED' => "Confirmed",
                'PACKET_STATE_DELETED' => "Deleted",
                'PACKET_STATE_DELIVERED' => "Delivered",
                'PACKET_STATE_DELIVERED_POSTOFFICE' => "Arrived at post office",
                'PACKET_STATE_HANDED_OVER_TO_COURIER' => "Transmitted to courier",
                'PACKET_STATE_HANDED_OVER_TO_PO' => "Re-addressed to post office",
                'PACKET_STATE_IN_CONTAINER' => "In container",
                'PACKET_STATE_IN_WAREHOUSE' => "At warehouse",
                'PACKET_STATE_ON_COURIER' => "At delivery",
                'PACKET_STATE_ON_HANDOVER_LIST' => "In transition sheet",
                'PACKET_STATE_ON_HOLD' => "Waiting",
                'PACKET_STATE_REGISTERED' => "Registered",
                'PACKET_STATE_SAVED' => "Saved",
                'PACKET_STATE_SORTED' => "Sorted",
                'PACKET_STATE_UNCONFIRMED' => "Unconfirmed",
                'PACKET_STATE_UNCONFIRMED_NO_TARRIF' => "Unconfirmed (No tariff)",
                'PACKET_STATE_WAITING_COURIER' => "Awaiting collection",
                'PACKET_STATE_WAITING_TRANSPORT' => "In delivery list",
                'PACKET_STATE_WAITING_UNARRIVED' => "Waiting, hasn't arrived",
                'PACKET_STATE_WRITTEN_OFF' => "Written off",
            ];
    
    public function extendTagWithAttributes($tag, $attributes, $close = true)
    {
        $tag = "<" . $tag;
        if(is_array($attributes) && !empty($attributes))
        {
            foreach ($attributes as $attribute => $value)
            {
                if($value)
                    $tag .= " " . $attribute . "=\"" . $value . "\"";
            }
        }
        $tag .= "> ";
        if($close)
            $tag .= "</" . $tag . ">";
        return $tag;
    }
    
    /*
     * @param array $errors
     * @return string
     */
    public function translateErrors($errors) {
        foreach ($errors as $key => $error) {
            $errors[$key] = str_ireplace(array_keys($this->errorsMap), array_values($this->errorsMap), $error);
        }
        return $errors;
    }
    
    /*
     * @param string $msg
     * @return string
     */
    public function translateTracking($msg) {
        if (isset($this->trackingMap[$msg])) {
            return $this->trackingMap[$msg];
        }
        return $msg;
    }

    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    public static function escapeForApi($value, $type = null)
    {
        switch ($type) {
            case self::ESCAPE_FOR_API_TYPE_EMAIL:
                return filter_var($value, FILTER_SANITIZE_EMAIL);

            default:
                return htmlspecialchars($value);
        }

        return $value;
    }
}
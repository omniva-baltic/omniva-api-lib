<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;

class Order
{
     /**
     * @var string
     */
    private $tracking;

    /**
     * @var string
     */
    private $quantity;

    /**
     * @var string
     */
    private $weight;

    /**
     * @var string
     */
    private $receiver;


    /**
     * @return string
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    /**
     * @param string $tracking
     * @return Order
     */
    public function setTracking($tracking)
    {
        $this->tracking = $tracking;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param string $quantity
     * @return Order
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param string $weight
     * @return Order
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @param string $receiver
     * @return Order
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

}

<?php

namespace Mijora\Omniva;

class OmnivaException extends \Exception
{
    private $_data = '';

    public function __construct($message, $data = null) 
    {
        $this->_data = $data;
        parent::__construct($message);
    }

    public function getData()
    {
        return $this->_data;
    }
}

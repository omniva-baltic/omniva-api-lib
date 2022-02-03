<?php

require '../vendor/autoload.php';
require 'config.php';


use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\Label;


// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

try {
    $label = new Label();
    $label->setAuth($username, $password);
    
    $label->downloadLabels($barcodes);
    

} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
<?php

require '../vendor/autoload.php';
require 'config.php';


use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Shipment\Tracking;


// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);

try {
    $tracking = new Tracking();
    $tracking->setAuth($username, $password);
    
    $results = $tracking->getTracking($barcodes);
    
    if (is_array($results)) {
        foreach ($results as $barcode => $tracking_data) {
            echo '**************<br/>';
            echo $barcode . '<br/>';
            foreach ($tracking_data as $data) {
                echo $data['date']->format('Y-m-d H:i:s') . ' ' . ' ' . $data['event'] . ' ' . $data['state'] . '<br/>';
            }
        }
    }
    

} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
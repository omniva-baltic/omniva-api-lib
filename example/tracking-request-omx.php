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

    // check only first from the list as this function accepts single barcode only
    $results = $tracking->getTrackingOmx($barcodes[0]);

    if (php_sapi_name() !== 'cli') {
        echo '<pre>' . json_encode($results, JSON_PRETTY_PRINT) . '</pre>';
        exit;
    }

    echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (OmnivaException $e) {
    echo "\n<br>Exception:<br>\n"
        . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
        . str_replace("\n", "<br>\n", $e->getTraceAsString());
}

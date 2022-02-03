<?php

require '../vendor/autoload.php';

use Mijora\Omniva\Locations\PickupPoints;
// @todo remove error reporting
error_reporting(E_ALL);
ini_set('display_errors', true);
/**
 * PickupPoints Tests
 */
$start = microtime(true);
$omnivaPickupPointsObj = new PickupPoints();
$omnivaLoc = $omnivaPickupPointsObj->getFilteredLocations('lt', 0, 'Kauno apskr.');
$omnivaPickupPointsObj->saveLocationsToJSONFile('../temp/test.json', json_encode($omnivaLoc));
echo "Done. Runtime: " .  (microtime(true) - $start) . 's' . PHP_EOL;
echo print_r($omnivaLoc, true);

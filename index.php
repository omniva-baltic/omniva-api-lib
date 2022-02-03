<?php

require_once "vendor/autoload.php";
use Symfony\Component\VarDumper\VarDumper;
error_reporting(E_ALL);
ini_set('display_errors', true);

$dumper = new VarDumper();
//$data = json_decode(file_get_contents('https://www.omniva.ee/locations.json'), true);

$xml = new \SimpleXMLElement('<shipment/>');
$xml->addChild('Sender');
$name = $xml->addChild('name', 87565);
$name->addAttribute('onmymind', 666);
$name->addAttribute('test', 5);
$dom = dom_import_simplexml($xml);
$dumper->dump($dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));

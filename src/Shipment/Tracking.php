<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;
use Mijora\Omniva\Helper;
use Mijora\Omniva\Shipment\Request\EventsOmxRequest;

class Tracking
{
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @param string $username
     * @param string $password
     * @param string $api_url
     */
    public function setAuth($username, $password, $api_url = 'https://edixml.post.ee') {
        $this->request = new Request($username, $password, $api_url);
    }

    /**
     * @param array $barcodes
     * 
     * @return mixed
     * @throws OmnivaException
     */
    public function getTracking($barcodes)
    {
        
        $trackings = [];
        try {
            $helper = new Helper();
            if (empty($this->request)) {
                throw new OmnivaException("Please set username and password");
            }
            $all_trackings = $this->request->getTracking();

            if (isset($all_trackings->event)) {
                foreach ($all_trackings->event as $event) {
                    if (in_array((string) $event->packetCode, $barcodes)) {
                        if (!isset($trackings[(string)$event->packetCode] )) {
                            $trackings[(string) $event->packetCode] = array();
                        }
                        $barcode_event = array(
                            'event' => $helper->translateTracking((string) $event->eventCode),
                            'date' => new \DateTime((string) $event->eventDate),
                            'state' => $helper->translateTracking((string) $event->stateCode),
                        );
                        $trackings[(string) $event->packetCode][] = $barcode_event;
                    }
                }
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage());
        }
        return $trackings;
    }

    /**
     * @param string $barcode
     * 
     * @return array
     * @throws OmnivaException
     */
    public function getTrackingOmx($barcode)
    {
        if (empty($this->request)) {
            throw new OmnivaException("Please set username and password");
        }

        $response = $this->request->callOmxApi(
            (new EventsOmxRequest())->setBarcode($barcode)
        );

        $response = @json_decode($response, true);

        if (!$response) {
            throw new OmnivaException("Something went wrong with server response");
        }

        return (isset($response['events'])) ? $response['events'] : [];
    }
}

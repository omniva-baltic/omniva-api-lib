<?php

namespace Mijora\Omniva;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Helper;
use Mijora\Omniva\Shipment\Request\OmxRequestInterface;
use Mijora\Omniva\Shipment\Request\PackageLabelOmxRequest;

class Request
{
    const API_OMX_URL = "https://omx.omniva.eu/api/v01/omx/";
    const API_OMX_URL_TEST = "https://test-omx.omniva.eu/api/v01/omx/";

    /** @var Helper */
    private $helper = null;

    /** @var string */
    private $username = null;

    /** @var string */
    private $password = null;

    /** @var string */
    private $api_url = 'https://edixml.post.ee';

    /** @var bool */
    private $use_test_omx_api = false;

    /** @var int cURL timeout in seconds. Default is 30s */
    private $curl_timeout = 30;

    /*
     * Debuging
     */
    private $debug = false;
    private $debug_request = '';
    private $debug_response = '';
    private $debug_url = '';
    private $debug_http_code = 0;

    /**
     * @param string $username
     * @param string @password
     * @param string $api_url
     * @param bool $debug
     */
    public function __construct($username, $password, $api_url = 'https://edixml.post.ee', $debug = false)
    {
        $this->helper = new Helper();
        $this->username = $username;
        $this->password = $password;
        $this->api_url = $api_url;
        $this->debug = $debug;
    }

    /**
     * @param int $seconds
     * @return Request
     */
    public function setCurlTimeout($seconds = 30)
    {
        $this->curl_timeout = (int) $seconds;

        return $this;
    }

    /**
     * @return Request
     */
    public function setUseTestOmxApi($use_test_omx_api = false)
    {
        $this->use_test_omx_api = (bool) $use_test_omx_api;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTestOmxApi()
    {
        return (bool) $this->use_test_omx_api;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $url
     */
    public function setApiUrl($url)
    {
        $this->api_url = $url;
        return $this;
    }

    /**
     * @param string $endpoint endpoint to be added to API url
     * 
     * @return string URL to API with given endpoint
     */
    public function getOmxApiUrl($endpoint = '')
    {
        return ($this->isTestOmxApi() ? self::API_OMX_URL_TEST : self::API_OMX_URL) . $endpoint;
    }

    /**
     * @param OmxRequestInterface $request
     * 
     * @return array
     * @throws OmnivaException
     */
    public function registerShipmentOmx(OmxRequestInterface $request)
    {
        try {
            $barcodes = [];
            $barcodes_mapped = [];
            $errors = [];

            $response = $this->callOmxApi($request);

            $response = json_decode((string) $response, true);

            if (!$response) {
                throw new OmnivaException('Something went wrong. Bad response format from Omniva API');
            }

            $saved = $response['savedShipments'] ?? [];
            foreach ($saved as $data) {
                $clientItemId = $data['clientItemId'] ?? false;
                $barcode = (string) ($data['barcode'] ?? '');

                if ($clientItemId) {
                    if (!isset($barcodes_mapped[$clientItemId])) {
                        $barcodes_mapped[$clientItemId] = [];
                    }

                    $barcodes_mapped[$clientItemId][] = $barcode;
                }

                $barcodes[] = $barcode;

                $consolidated = $data['consolidatedShipments'] ?? [];
                foreach ($consolidated as $consolidated_data) {
                    $consolidated_barcode = (string) ($consolidated_data['barcode'] ?? '');

                    $barcodes[] = $consolidated_barcode;

                    $barcodes_mapped[$clientItemId][] = $consolidated_barcode;
                }
            }

            $failed = $response['failedShipments'] ?? [];
            $errors = array();
            foreach ($failed as $data) {
                $clientItemId = $data['clientItemId'] ?? false;
                $errors[] = ($clientItemId ? $clientItemId : 'No ClientID') . ' - ' . ($data['message'] ?? '');
            }

            if (!empty($errors)) {
                throw new OmnivaException(implode('. ', $errors), $this->get_debug_data());
            }

            if (empty($barcodes) && empty($errors)) {
                throw new OmnivaException('Something went wrong. Omniva API did not return barcodes nor errors.');
            }

            return array(
                'barcodes' => $barcodes,
                'barcodes_mapped' => $barcodes_mapped,
                'message' => '',
                'debug' => $this->get_debug_data()
            );
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->get_debug_data());
        }
    }

    /**
     * @param string $request
     * @return array
     */
    public function call($request)
    {
        $xml = $this->build_request_xml($request);
        $this->validate($xml);

        try {
            $barcodes = array();
            $errors = array();
            $url = $this->api_url . "/epmx/services/messagesService.wsdl";

            $xmlResponse = $this->make_call($xml, $url);
            $xml = $this->convert_response_to_xml($xmlResponse);

            foreach ($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo as $data) {
                $barcodes[] = (string) $data->barcode;
            }

            if (empty($barcodes)) {
                throw new OmnivaException('Error in XML request');
            }

            $message = (is_object($xml->Body->businessToClientMsgResponse->prompt)) ? $xml->Body->businessToClientMsgResponse->prompt->__toString() : '';

            return array(
                'barcodes' => $barcodes,
                'message' => $message,
                'debug' => $this->get_debug_data()
            );
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->get_debug_data());
        }
    }

    /**
     * @param string $xmlResponse
     * @return string
     */
    private function clear_xml_response($xmlResponse)
    {
        return str_ireplace(['SOAP-ENV:', 'SOAP:', 'ns3:'], '', $xmlResponse);
    }

    /**
     * @param string $xml
     * @param string $url
     * @return mixed
     */
    private function make_call($xml, $url)
    {
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($xml),
        );

        if (!$xml) {
            $headers = array(
                "Content-type: text/xml;charset=\"utf-8\""
            );
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        if ($xml) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->debug_url = $url;
        $this->debug_http_code = $http_code;
        $this->debug_request = $xml;
        $this->debug_response = $response;

        if ($http_code === 401) {
            throw new OmnivaException('Unauthorized! Check credentials', $this->get_debug_data());
        }

        if ($http_code === 404 || ($http_code >= 500 && $http_code < 600)) {
            throw new OmnivaException('Unexpected server error ' . $http_code, $this->get_debug_data());
        }

        if ($http_code === 0) {
            throw new OmnivaException('Bad API URL', $this->get_debug_data());
        }

        return $response;
    }

    /**
     * Makes cURL request to OMX API based on givent OmxRequest object
     * 
     * @param OmxRequestInterface $request Request object with data to build cURL request from
     * 
     * @return string Response body
     * 
     * @throws OmnivaException
     */
    public function callOmxApi(OmxRequestInterface $request)
    {
        if (!($request instanceof OmxRequestInterface)) {
            throw new OmnivaException('Invalid request object passed. Must implement OmxRequestInterface');
        }

        // Populate full API URL
        $api_url = $this->getOmxApiUrl($request->getOmxApiEndpoint());

        // Generate request body
        $body = json_encode($request);

        // Default header
        $headers = array(
            "Content-type: application/json;charset=\"utf-8\"",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        );

        if ($request->getRequestMethod() === OmxRequestInterface::REQUEST_METHOD_POST) {
            $headers[] = "Content-length: " . mb_strlen($body);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_CUSTOMREQUEST => $request->getRequestMethod(),
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_USERPWD => "$this->username:$this->password",
            CURLOPT_TIMEOUT => $this->curl_timeout,
        ]);

        if ($request->getRequestMethod() === OmxRequestInterface::REQUEST_METHOD_POST) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);

        $http_code = (int) (curl_getinfo($curl, CURLINFO_HTTP_CODE));

        curl_close($curl);

        $this->debug_url = $api_url;
        $this->debug_http_code = $http_code;
        $this->debug_request = $body;
        $this->debug_response = $response;

        if ($http_code === 401) {
            throw new OmnivaException('Unauthorized! Check credentials', $this->get_debug_data());
        }

        // OMX API on 400 (Bad Request) returns json body with erorrs
        if ($http_code === 400) {
            $error_msg = $this->parseOmx400Response($response);

            throw new OmnivaException('Bad Request ' . $http_code . ($error_msg ? ': ' . $error_msg : ''), $this->get_debug_data());
        }

        if ($http_code === 404 || ($http_code >= 500 && $http_code < 600)) {
            // OMX API on 500 and 404 returns json body with different structure for error
            $error_msg = $this->parseOmx500Response($response);

            throw new OmnivaException('Unexpected server error ' . $http_code . ($error_msg ? ': ' . $error_msg : ''), $this->get_debug_data());
        }

        if ($http_code === 0) {
            throw new OmnivaException('Bad API URL', $this->get_debug_data());
        }

        return $response;
    }

    /**
     * Parses 400 (Bad Request) OMX API Error response
     * 
     * @param string $response
     * 
     * @return string Generated error message
     */
    private function parseOmx400Response($response)
    {
        /**
         * example 400 error response:
         * 
         * {
         *      "title": "Validation Failed",
         *      "path": "/api/v01/omx/courierorders/create-pickup-order",
         *      "details": "Input validation failed",
         *      "code": "",
         *      "timestamp": 1698220666378,
         *      "developerMessage": "org.springframework.web.bind.MethodArgumentNotValidException",
         *      "errors": {
         *          "startTime": [
         *              {
         *                  "code": "Future",
         *                  "message": "must be a future date"
         *              }
         *          ]
         *      }
         *  }
         */
        $error_response = @json_decode($response, true);

        if (!$error_response) {
            return null;
        }

        $errors = $error_response['errors'] ?? [];
        $errors_parsed = [];
        foreach ($errors as $key => $array) {
            if (!$array) {
                continue;
            }

            $error_msgs = [];
            foreach ($array as $error) {
                if ($error['message'] ?? false) {
                    $error_msgs[] = $error['message'];
                }
            }

            $errors_parsed[] = $key . ': ' . implode(', ', $error_msgs);
        }

        return ($error_response['title'] ?? '') . ': ' . ($error_response['details'] ?? '') . ' - ' . implode(', ', $errors_parsed);
    }

    /**
     * Parses non standart OMX API Error response
     * 
     * @param string $response
     * 
     * @return string Generated error message
     */
    private function parseOmx500Response($response)
    {
        /**
         * example 500 error response:
         * 
         * {
         *   "title": "Resource not found",
         *   "path": "/api/v01/omx/shipments/package-labels",
         *   "details": "The 'Shipment' entity with id='package-labels' was not found!",
         *   "code": "RESOURCE_NOT_FOUND",
         *   "timestamp": 1692943557511,
         *   "developerMessage": "com.omniva.phoenix.core.exception.PhoenixResourceNotFoundException",
         *   "errors": {}
         * }
         */
        $error_response = @json_decode($response, true);

        if (!$error_response) {
            return null;
        }

        return ($error_response['title'] ?? '') . ': ' . ($error_response['details'] ?? '');
    }

    /**
     * @param string $request
     * @return string
     */
    private function build_request_xml($request)
    {
        $xml = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
            <soapenv:Header/>
            <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                    <partner>' . $this->username . '</partner>';
        $xml .= preg_replace("/<\\?xml.*\\?>/", '', $request, 1);
        $xml .= '
                </xsd:businessToClientMsgRequest>
            </soapenv:Body>
        </soapenv:Envelope>';
        return $xml;
    }

    /**
     * @param string $xml
     */
    private function validate($xml)
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        $this->validateSchema($dom);
    }

    /**
     * @param string $xmlResponse
     * @return \SimpleXMLElement|false
     */
    private function convert_response_to_xml($xmlResponse)
    {
        if ($xmlResponse === false || strlen(trim($xmlResponse)) <= 0) {
            throw new OmnivaException('Error in XML request', $this->get_debug_data());
        }

        $xmlResponse = $this->clear_xml_response($xmlResponse);
        $xml = @simplexml_load_string($xmlResponse);

        if (!is_object($xml)) {
            $this->get_response_not_object_error($xmlResponse);
        }

        if (is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo)) {
            $errors = array();
            foreach ($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data) {
                $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
            }
            if (!empty($errors)) {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)), $this->get_debug_data());
            }
        }

        if (!is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo)) {
            if (is_object($xml->Body->businessToClientMsgResponse->prompt)) {
                throw new OmnivaException((string) $xml->Body->businessToClientMsgResponse->prompt, $this->get_debug_data());
            }
            throw new OmnivaException('No barcodes received', $this->get_debug_data());
        }

        return $xml;
    }

    /**
     * @param string $xmlResponse
     */
    private function get_response_not_object_error($xmlResponse)
    {
        if (
            strpos($xmlResponse, 'HTTP Status 401') !== false
            && strpos($xmlResponse, 'This request requires HTTP authentication.') !== false
        ) {
            throw new OmnivaException('Bad API logins', $this->get_debug_data());
        }

        throw new OmnivaException('Response is in the wrong format', $this->get_debug_data());
    }

    /**
     * @param array $barcodes
     * @param string|null $send_to_email
     * 
     * @return mixed
     */
    public function getLabelsOmx($barcodes, $send_to_email = null)
    {
        $labels = [];
        $errors = [];

        try {
            $omx_request = (new PackageLabelOmxRequest())
                ->addBarcode($barcodes);

            $omx_request->customerCode = $this->username;

            if ($send_to_email) {
                $omx_request->setEmail($send_to_email);
            }

            $response = $this->callOmxApi($omx_request);

            $response = json_decode((string) $response, true);

            if (!$response) {
                throw new OmnivaException('Something went wrong. Bad response format from Omniva API');
            }

            $success = $response['successAddressCards'] ?? [];
            foreach ($success as $data) {
                $barcode = $data['barcode'] ?? null;
                $fileData = $data['fileData'] ?? null;

                // if sending to email success gives back barcode
                $labels[$barcode] = $omx_request->isSentToEmail() ? $barcode : $fileData;
            }

            $failed = $response['failedAddressCards'] ?? [];
            $errors = array();
            foreach ($failed as $data) {
                $barcode = $data['barcode'] ?? null;
                $errors[] = ($barcode ? $barcode : 'No barcode') . ' - ' . ($data['messageCode'] ?? '');
            }

            if (!empty($errors)) {
                throw new OmnivaException(implode('. ', $errors), $this->get_debug_data());
            }

            if (empty($labels) && empty($errors)) {
                throw new OmnivaException('Something went wrong. Omniva API did not return barcodes nor errors.');
            }

            return [
                'labels' => $labels
            ];
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->get_debug_data());
        }
    }

    /**
     * @param array $barcodes
     * @return mixed
     */
    public function get_labels($barcodes)
    {
        $labels = [];
        $barcodeXML = '';
        foreach ($barcodes as $barcode) {
            $barcodeXML .= '<barcode>' . $barcode . '</barcode>';
        }
        $xml = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
           <soapenv:Header/>
           <soapenv:Body>
              <xsd:addrcardMsgRequest>
                 <partner>' . $this->username . '</partner>
                 <sendAddressCardTo>response</sendAddressCardTo>
                 <barcodes>
                    ' . $barcodeXML . '
                 </barcodes>
              </xsd:addrcardMsgRequest>
           </soapenv:Body>
        </soapenv:Envelope>';

        try {
            $errors = array();
            $url = $this->api_url . "/epmx/services/messagesService.wsdl";

            $xmlResponse = $this->make_call($xml, $url);

            if ($xmlResponse === false) {
                $errors[] = "Error in xml request";
            }
            if (strlen(trim($xmlResponse)) > 0) {
                $xmlResponse = $this->clear_xml_response($xmlResponse);
                $xml = @simplexml_load_string($xmlResponse);
                if (!is_object($xml)) {
                    $errors[] = 'Response is in the wrong format';
                }
                if (empty($errors)) {
                    if (is_object($xml) && is_object($xml->Body->addrcardMsgResponse->successAddressCards)) {
                        foreach ($xml->Body->addrcardMsgResponse->successAddressCards->addressCardData as $data) {
                            $labels[(string) $data->barcode] = (string) $data->fileData;
                        }
                    }
                    if (empty($labels)) {
                        $errors[] = 'No labels received';
                    }
                }
            }
            if (!empty($labels)) {
                return array('labels' => $labels);
            } else {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)));
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->get_debug_data());
        }
    }

    /**
     * @return \SimpleXMLElement|false
     */
    public function getTracking()
    {
        $url = $this->api_url . '/epteavitus/events/from/' . date("c", strtotime("-1 week +1 day")) . '/for-client-code/' . $this->username;

        $xmlResponse = $this->make_call(false, $url);

        $return = $this->clear_xml_response($xmlResponse);
        try {
            $xml = @simplexml_load_string($return);
            if (!is_object($xml)) {
                throw new \Exception('Wrong response received');
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->get_debug_data());
        }
        return $xml;
    }

    /**
     * @param \DOMDocument $dom
     * @return bool
     */
    private function validateSchema($dom)
    {
        libxml_use_internal_errors(true);
        if (!$dom->schemaValidate(dirname(__FILE__) . '/Xsd/soap.xsd')) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            libxml_clear_errors();
            throw new OmnivaException(implode(' ', $errors));
        }
        return true;
    }

    /**
     * @param string $url
     */
    private function set_debug_url($url)
    {
        $this->debug_url = $url;
    }

    /**
     * @param string|int $http_code
     */
    private function set_debug_http_code($http_code)
    {
        $this->debug_http_code = $http_code;
    }

    /**
     * @param string $request
     */
    private function set_debug_request($request)
    {
        $this->debug_request = $request;
    }

    /**
     * @param string $response
     */
    private function set_debug_response($response)
    {
        $this->debug_response = $response;
    }

    /**
     * @return array
     */
    public function get_debug_data()
    {
        if (!$this->debug) return array();

        return array(
            'url' => $this->debug_url,
            'code' => $this->debug_http_code,
            'request' => $this->debug_request,
            'response' => $this->debug_response,
        );
    }
}

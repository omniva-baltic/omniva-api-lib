<?php

namespace Mijora\Omniva;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Helper;

class Request
{

    /*
     * @var Helper
     */
    private $helper = null;
    
    /*
     * @var string
     */
    private $username = null;
    
    /*
     * @var string
     */
    private $password = null;
    
    /*
     * @var string
     */
    private $api_url = 'https://edixml.post.ee';

    /*
     * Debuging
     */
    private $debug = false;
    private $debug_request = '';
    private $debug_response = '';
    private $debug_url = '';
    private $debug_http_code = 0;

    /*
     * @param string $username
     * @param string @password
     * @param string $api_url
     */
    public function __construct($username, $password, $api_url = 'https://edixml.post.ee', $debug = false)
    {
        $this->helper = new Helper();
        $this->username = $username;
        $this->password = $password;
        $this->api_url = $api_url;
        $this->debug = $debug;
    }

    /*
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /*
     * @param string $url
     */
    public function setApiUrl($url)
    {
        $this->api_url = $url;
        return $this;
    }

    /*
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

            if ( empty($barcodes) ) {
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

    /*
     * @param string $xmlResponse
     * @return string
     */
    private function clear_xml_response($xmlResponse)
    {
        return str_ireplace(['SOAP-ENV:', 'SOAP:', 'ns3:'], '', $xmlResponse);
    }

    /*
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->debug_url = $url;
        $this->debug_http_code = $http_code;
        $this->debug_request = $xml;
        $this->debug_response = $response;

        if ( $http_code == '0' ) {
            throw new OmnivaException('Bad API URL', $this->get_debug_data());
        }
        return $response;
    }

    /*
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

    /*
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

    /*
     * @param string $xmlResponse
     * @return object
     */
    private function convert_response_to_xml( $xmlResponse )
    {
        if ( $xmlResponse === false || strlen(trim($xmlResponse)) <= 0 ) {
            throw new OmnivaException('Error in XML request', $this->get_debug_data());
        }

        $xmlResponse = $this->clear_xml_response($xmlResponse);
        $xml = @simplexml_load_string($xmlResponse);

        if ( ! is_object($xml) ) {
            $this->get_response_not_object_error($xmlResponse);
        }

        if ( is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo) ) {
            $errors = array();
            foreach ( $xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data ) {
                $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
            }
            if ( ! empty($errors) ) {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)), $this->get_debug_data());
            }
        }

        if ( ! is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo) ) {
            throw new OmnivaException('No barcodes received', $this->get_debug_data());
        }

        return $xml;
    }

    /*
     * @param string $xmlResponse
     */
    private function get_response_not_object_error( $xmlResponse )
    {
        if ( strpos($xmlResponse, 'HTTP Status 401') !== false
            && strpos($xmlResponse, 'This request requires HTTP authentication.') !== false
        ) {
            throw new OmnivaException('Bad API logins', $this->get_debug_data());
        }
        
        throw new OmnivaException('Response is in the wrong format', $this->get_debug_data());
    }

    /*
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

    /*
     * @return mixed
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

    /*
     * @param DOMDocument $dom
     * @return mixed
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

    /*
     * @param string $url
     */
    private function set_debug_url( $url )
    {
        $this->debug_url = $url;
    }

    /*
     * @param string|integer $http_code
     */
    private function set_debug_http_code( $http_code )
    {
        $this->debug_http_code = $http_code;
    }

    /*
     * @param string $request
     */
    private function set_debug_request( $request )
    {
        $this->debug_request = $request;
    }

    /*
     * @param string $response
     */
    private function set_debug_response( $response )
    {
        $this->debug_response = $response;
    }

    /*
     * @return array
     */
    public function get_debug_data()
    {
        if ( ! $this->debug ) return array();

        return array(
            'url' => $this->debug_url,
            'code' => $this->debug_http_code,
            'request' => $this->debug_request,
            'response' => $this->debug_response,
        );
    }
}

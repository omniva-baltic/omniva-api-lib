<?php

namespace Mijora\Omniva;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Helper;

class Request
{

    /**
     * @var Helper
     */
    private $helper = null;
    
    /**
     * @var string
     */
    private $username = null;
    
    /**
     * @var string
     */
    private $password = null;
    
    /**
     * @var string
     */
    private $api_url_domain = 'https://edixml.post.ee';

    /**
     * @var string
     */
    private $api_url_path = '/epmx/services/messagesService.wsdl';

    /**
     * Debuging
     */
    private $debug = false;
    private $debug_request = '';
    private $debug_response = '';
    private $debug_url = '';
    private $debug_http_code = 0;

    /**
     * @param (string) $username
     * @param (string) $password
     * @param (string) $api_url
     */
    public function __construct($username = '', $password = '', $api_url_domain = '', $debug = false)
    {
        $this->helper = new Helper();

        if ( ! empty($username) ) $this->setUsername($username);
        if ( ! empty($password) ) $this->setPassword($password);
        if ( ! empty($api_url_domain) ) $this->setApiUrlDomain($api_url_domain);
        $this->enableDebug($debug);
    }

    /**
     * @param (string) $username
     * @return object
     */
    public function setUsername( $username )
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param (string) $password
     * @return object
     */
    public function setPassword( $password )
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @param (string) $url_domain
     * @return object
     */
    public function setApiUrlDomain( $url_domain )
    {
        $this->api_url_domain = $url_domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrlDomain()
    {
        return $this->api_url_domain;
    }

    /**
     * @param (string) $url_path
     * @return object
     */
    public function setApiUrlPath( $url_path )
    {
        $this->api_url_path = $url_path;
        return $this;
    }

    /**
     * @param (string) $url
     * @return object
     */
    public function setApiUrl($url)
    {
        $parsed_url = parse_url($url);

        $this->setApiUrlDomain($parsed_url['scheme'] . '://' . $parsed_url['host']);
        $this->setApiUrlPath($parsed_url['path']);

        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->api_url_domain . $this->api_url_path;
    }

    /**
     * @param (string) $request
     * @return array
     */
    public function call( $request )
    {
        $xml = $this->buildRequestXml($request);
        $this->validate($xml);

        try {
            $barcodes = array();
            $errors = array();
            $url = $this->getApiUrl();

            $xmlResponse = $this->makeCall($xml, $url);
            $xml = $this->convertResponseToXml($xmlResponse);

            foreach ($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo as $data) {
                $barcodes[] = (string) $data->barcode;
            }

            if ( empty($barcodes) ) {
                throw new OmnivaException('Error in XML request', $this->getDebugData());
            }

            $message = (is_object($xml->Body->businessToClientMsgResponse->prompt)) ? $xml->Body->businessToClientMsgResponse->prompt->__toString() : '';

            return array(
                'barcodes' => $barcodes,
                'message' => $message,
                'debug' => $this->getDebugData()
            );
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->getDebugData());
        }
    }

    /**
     * @param (string) $xmlResponse
     * @return string
     */
    private function clearXmlResponse( $xmlResponse )
    {
        return str_ireplace(['SOAP-ENV:', 'SOAP:', 'ns3:'], '', $xmlResponse);
    }

    /**
     * @param string $xml
     * @param string $url
     * @return mixed
     */
    private function makeCall( $xml, $url )
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

        $this->setDebugUrl($url);
        $this->setDebugHttpCode($http_code);
        $this->setDebugRequest($xml);
        $this->setDebugResponse($response);

        if ( $http_code == '0' ) {
            throw new OmnivaException('Bad API URL', $this->getDebugData());
        }
        if ( $http_code == '401' ) {
            throw new OmnivaException('Unauthorized - Problem with API logins', $this->getDebugData());
        }
        return $response;
    }

    /**
     * @param (string) $request
     * @return string
     */
    private function buildRequestXml( $request )
    {
        $xml = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://service.core.epmx.application.eestipost.ee/xsd">
            <soapenv:Header/>
            <soapenv:Body>
                <xsd:businessToClientMsgRequest>
                    <partner>' . $this->getUsername() . '</partner>';
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
    private function validate( $xml )
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        $this->validateSchema($dom);
    }

    /**
     * @param (string) $xmlResponse
     * @return object
     */
    private function convertResponseToXml( $xmlResponse )
    {
        if ( $xmlResponse === false || strlen(trim($xmlResponse)) <= 0 ) {
            throw new OmnivaException('Error in XML request', $this->getDebugData());
        }

        $xmlResponse = $this->clearXmlResponse($xmlResponse);
        $xml = @simplexml_load_string($xmlResponse);

        if ( ! is_object($xml) ) {
            $this->getResponseNotObjectError($xmlResponse);
        }

        if ( is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo) ) {
            $errors = array();
            foreach ( $xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data ) {
                $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
            }
            if ( ! empty($errors) ) {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)), $this->getDebugData());
            }
        }

        if ( ! is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo) ) {
            if ( is_object($xml->Body->businessToClientMsgResponse->prompt) ) {
                throw new OmnivaException((string) $xml->Body->businessToClientMsgResponse->prompt, $this->getDebugData());
            }
            throw new OmnivaException('No barcodes received', $this->getDebugData());
        }

        return $xml;
    }

    /**
     * @param (string) $xmlResponse
     */
    private function getResponseNotObjectError( $xmlResponse )
    {
        if ( strpos($xmlResponse, 'HTTP Status 401') !== false
            && strpos($xmlResponse, 'This request requires HTTP authentication.') !== false
        ) {
            throw new OmnivaException('Bad API logins', $this->getDebugData());
        }
        
        throw new OmnivaException('Response is in the wrong format', $this->getDebugData());
    }

    /**
     * @param (array) $barcodes
     * @return mixed
     */
    public function getLabels( $barcodes )
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
                 <partner>' . $this->getUsername() . '</partner>
                 <sendAddressCardTo>response</sendAddressCardTo>
                 <barcodes>
                    ' . $barcodeXML . '
                 </barcodes>
              </xsd:addrcardMsgRequest>
           </soapenv:Body>
        </soapenv:Envelope>';

        try {
            $errors = array();
            $url = $this->getApiUrl();

            $xmlResponse = $this->makeCall($xml, $url);
            
            if ($xmlResponse === false) {
                $errors[] = "Error in xml request";
            }
            if (strlen(trim($xmlResponse)) > 0) {
                $xmlResponse = $this->clearXmlResponse($xmlResponse);
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
                return array('labels' => $labels, 'debug' => $this->getDebugData());
            } else {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)), $this->getDebugData());
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->getDebugData());
        }
    }

    /**
     * @return mixed
     */
    public function getTracking()
    {
        $url = $this->getApiUrlDomain() . '/epteavitus/events/from/' . date("c", strtotime("-1 week +1 day")) . '/for-client-code/' . $this->getUsername();
        
        $xmlResponse = $this->makeCall(false, $url);
        
        $return = $this->clearXmlResponse($xmlResponse);
        try {
            $xml = @simplexml_load_string($return);
            if (!is_object($xml)) {
                throw new \Exception('Wrong response received', $this->getDebugData());
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage(), $this->getDebugData());
        }
        return array('tracking' => $xml, 'debug' => $this->getDebugData());
    }

    /**
     * @param (DOMDocument) $dom
     * @return mixed
     */
    private function validateSchema( $dom )
    {
        libxml_use_internal_errors(true);
        if (!$dom->schemaValidate(dirname(__FILE__) . '/Xsd/soap.xsd')) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            libxml_clear_errors();
            throw new OmnivaException(implode(' ', $errors), $this->getDebugData());
        }
        return true;
    }

    /**
     * @param (bool) $enable
     * @return object
     */
    public function enableDebug( $enable )
    {
        $this->debug = $enable;
        return $this;
    }

    /**
     * @param (string) $url
     * @return object
     */
    private function setDebugUrl( $url )
    {
        $this->debug_url = $url;
        return $this;
    }

    /**
     * @param (string|integer) $http_code
     * @return object
     */
    private function setDebugHttpCode( $http_code )
    {
        $this->debug_http_code = $http_code;
        return $this;
    }

    /**
     * @param (string) $request
     * @return object
     */
    private function setDebugRequest( $request )
    {
        $this->debug_request = $request;
        return $this;
    }

    /**
     * @param (string) $response
     * @return object
     */
    private function setDebugResponse( $response )
    {
        $this->debug_response = $response;
        return $this;
    }

    /**
     * @return array
     */
    public function getDebugData()
    {
        if ( ! $this->debug ) return array();

        return array(
            'url' => $this->debug_url,
            'code' => $this->debug_http_code,
            'request' => $this->debug_request,
            'response' => $this->debug_response,
        );
    }

    /*** Deprecated functions ***/
    public function get_labels( $barcodes )
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        return $this->getLabels($barcodes);
    }

    public function get_debug_data()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        return $this->getDebugData();
    }
}

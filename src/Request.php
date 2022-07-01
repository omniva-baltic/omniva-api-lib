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
     * @param string $username
     * @param string @password
     * @param string $api_url
     */
    public function __construct($username, $password, $api_url = 'https://edixml.post.ee')
    {
        $this->helper = new Helper();
        $this->username = $username;
        $this->password = $password;
        $this->api_url = $api_url;
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
            
            if ($xmlResponse === false) {
                $errors[] = "Error in xml request";
            } else {
                $errorTitle = '';
                if (strlen(trim($xmlResponse)) > 0) {
                    /*
                      echo "<pre>";
                      echo htmlentities($xmlResponse);
                      echo "</pre>"; */
                    //exit;
                    $xmlResponse = str_ireplace(['SOAP-ENV:', 'SOAP:', 'ns3:'], '', $xmlResponse);
                    $xml = @simplexml_load_string($xmlResponse);
                    if (!is_object($xml)) {
                        $errors[] = 'Response is in the wrong format';
                    }
                    if (is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo)) {
                        foreach ($xml->Body->businessToClientMsgResponse->faultyPacketInfo->barcodeInfo as $data) {
                            $errors[] = $data->clientItemId . ' - ' . $data->barcode . ' - ' . $data->message;
                        }
                    }
                    if (empty($errors)) {
                        if (is_object($xml) && is_object($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo)) {
                            foreach ($xml->Body->businessToClientMsgResponse->savedPacketInfo->barcodeInfo as $data) {
                                $barcodes[] = (string) $data->barcode;
                            }
                        }
                        if (empty($barcodes)) {
                            $errors[] = 'No barcodes received';
                        }
                    }
                } else {
                    $errors[] = 'Response is in the wrong format';
                }
            }

            if (!empty($barcodes)) {
                return array('barcodes' => $barcodes);
            } else {
                throw new OmnivaException(implode('. ', $this->helper->translateErrors($errors)));
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage());
        }
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
        curl_close($ch);
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
                $xmlResponse = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xmlResponse);
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
            throw new OmnivaException($e->getMessage());
        }
    }

    /*
     * @return mixed
     */
    public function getTracking()
    {
        $url = $this->api_url . '/epteavitus/events/from/' . date("c", strtotime("-1 week +1 day")) . '/for-client-code/' . $this->username;
        
        $xmlResponse = $this->make_call(false, $url);
        
        $return = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xmlResponse);
        try {
            $xml = @simplexml_load_string($return);
            if (!is_object($xml)) {
                throw new \Exception('Wrong response received');
            }
        } catch (\Exception $e) {
            throw new OmnivaException($e->getMessage());
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

}

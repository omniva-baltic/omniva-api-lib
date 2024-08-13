<?php

namespace Mijora\Omniva\PowerBi;

class OmnivaPowerBi
{
    const ENDPOINT = '';

    const DEFAULT_TIMESTAMP = '0000-00-00 00:00:00';

    const DEFAULT_PRICE_COUNTRY = 'Default';

    private $username;
    private $password;
    private $plugin_version;
    private $platform;
    private $sender_name;
    private $sender_country;
    private $order_count_courier = 0;
    private $order_count_terminal = 0;
    private $order_count_timestamp = self::DEFAULT_TIMESTAMP;
    private $prices = [];

    public function __construct($username, $password)
    {
        $this->username = (string) $username;
        $this->password = (string) $password;
    }

    /**
     * Set Omniva module plugin version. Eg.: v2.0.0
     * 
     * @param string $plugin_version
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setPluginVersion($plugin_version)
    {
        $this->plugin_version = (string) $plugin_version;

        return $this;
    }

    /**
     * Set eCommerce platform and version. Eg.: Opencart v3.0.0
     * 
     * @param string $platform
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setPlatform($platform)
    {
        $this->platform = (string) $platform;

        return $this;
    }

    /**
     * Set sender name. Usualy from module setting.
     * 
     * @param string $sender_name
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setSenderName($sender_name)
    {
        $this->sender_name = (string) $sender_name;

        return $this;
    }

    /**
     * Set sender country. Usualy from module settings. Best to set as ISO 3166-1 alpha-2, eg.: LT
     * 
     * @param string $sender_country
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setSenderCountry($sender_country)
    {
        $this->sender_country = (string) $sender_country;

        return $this;
    }

    /**
     * Set how many orders placed with courier option from last time data was sent (usualy last month)
     * 
     * @param int $order_count_courier
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setOrderCountCourier($order_count_courier)
    {
        $this->order_count_courier = (int) $order_count_courier;

        return $this;
    }

    /**
     * Set how many orders placed with terminal option from last time data was sent (usualy last month)
     * 
     * @param int $order_count_terminal
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setOrderCountTerminal($order_count_terminal)
    {
        $this->order_count_terminal = (int) $order_count_terminal;

        return $this;
    }

    /**
     * Set time stamp from which data was collected.
     * 
     * @param string|null $timestamp If empty string or null will be set as 0000-00-00 00:00:00
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setDateTimeStamp($timestamp)
    {

        $this->order_count_timestamp = $timestamp ? (string) $timestamp : self::DEFAULT_TIMESTAMP;

        return $this;
    }

    /**
     * Set courier and terminal price for given country.
     * 
     * @param string|null $country Best ISO 3166-1 alpha-2, eg.: LT, if null or empty string will set as Default
     * @param string|float $courier_price Courier price, can be float or string if modules uses ranges format etc.
     * @param string|float $terminal_price Terminal price, can be float or string if modules uses ranges format etc.
     * 
     * @return \Mijora\Omniva\PowerBi\OmnivaPowerBi
     */
    public function setPrice($country, $courier_price, $terminal_price)
    {
        $country = $country ? (string) $country : self::DEFAULT_PRICE_COUNTRY;

        $this->prices[$country] = [
            'country' => $country,
            'courier' => (string) $courier_price,
            'terminal' => (string) $terminal_price,
        ];

        return $this;
    }

    /**
     * Generates data array for sending to PowerBi
     * 
     * @return array
     */
    public function getBody()
    {
        return [
            "pluginVersion" => $this->plugin_version,
            "eCommPlatform" => $this->platform,
            "omnivaApiKey" => $this->username,
            "senderName" => $this->sender_name,
            "senderCountryCode" => $this->sender_country,
            "ordersCount" => [
                "courier" => $this->order_count_courier,
                "terminal" => $this->order_count_terminal,
            ],
            "ordersCountSince" => $this->order_count_timestamp,
            "setPricing" => $this->prices,
            "sendingTimestamp" => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Send data to PowerBi
     * 
     * @return bool Was data sent succesfully
     */
    public function send()
    {
        if (!self::ENDPOINT) {
            return false;
        }

        try {
            return $this->sendToApi($this->getBody());
        } catch (\Throwable $th) {
            // silence is golden
        }

        return false;
    }

    private function sendToApi($body)
    {
        // Generate request body
        $body = json_encode($body);

        // Default header
        $headers = array(
            "Content-type: application/json;charset=\"utf-8\"",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . mb_strlen($body),
        );

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::ENDPOINT,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $body,
        ]);

        curl_exec($curl);

        $http_code = (int) (curl_getinfo($curl, CURLINFO_HTTP_CODE));

        curl_close($curl);

        // assume succes on 2xx HTTP code
        return 200 <= $http_code && 300 > $http_code;
    }
}
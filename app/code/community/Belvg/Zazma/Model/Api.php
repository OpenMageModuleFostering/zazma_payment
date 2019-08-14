<?php

class Belvg_Zazma_Model_Api
{

    /**
     * API version number (api number - ext number)
     *
     * @var stirng
     */
    private $_version = NULL;

    /**
     * API request user angent string
     * @var string
     */
    private $_userAgent = NULL;
    private $_headers = array();

    public function __construct()
    {
        $this->_version = Mage::helper('zazma')->getExtensionVersion();
        $this->_userAgent = $this->setUserAgent();
        $this->_headers = array('Content-type: application/json');
    }

    /**
     * Get API version (api number - ext number)
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Get module User-Agent to use on API requests
     *
     * @return string
     */
    protected function getUserAgent()
    {
        return $this->_userAgent;
    }

    /**
     * Get headers for the request
     *
     * @return string
     */
    protected function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Create module User-Agent line
     *
     * @return string
     */
    protected function setUserAgent()
    {
        $edition = Mage::helper('zazma')->getEdition();
        $ua = sprintf('Zazma/%s (Mage-%s-%s)', $this->getVersion(), $edition, Mage::getVersion());
        return $ua;
    }

    public function sendRequest($url, $data, $method)
    {
        $response = NULL;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        } else {
            $query = http_build_query($data);
            $url .= '?' . $query;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $serverOutput = curl_exec($ch);

        if ($serverOutput) {
            $response = (array) json_decode($serverOutput);
        } else {
            throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, curl_error($ch));
        }

        curl_close($ch);
        return $response;
    }

}

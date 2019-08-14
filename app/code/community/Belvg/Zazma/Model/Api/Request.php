<?php

class Belvg_Zazma_Model_Api_Request extends Belvg_Zazma_Model_Api
{

    private $_apiUserName = NULL;
    private $_apiPassword = NULL;

    const ZAZMA_URL_LIVE = 'https://api.zazma.com/';
    const ZAZMA_URL_SANDBOX = 'https://api.demo.dev.zazma.com/';
    const ZAZMA_URL_SET_CHECKOUT = 'checkout/SetCheckOut/';
    const ZAZMA_URL_VERIFY = 'checkout/IsCheckoutSupported/';
    const ZAZMA_URL_GET_CHECKOUT = 'checkout/GetCheckoutDetails/';
    const ZAZMA_URL_DO_CHECKOUT = 'checkout/DoCheckoutPayment/';

    public function __construct()
    {
        parent::__construct();

        $_helper = Mage::helper('zazma');
        /* @var $_helper Belvg_Zazma_Helper_Data */

        $this->_apiUserName = $_helper->getApiUsername();
        $this->_apiPassword = $_helper->getApiPassword();
    }

    /**
     * Prepare url for the request
     * @param string $urlKey
     * @return string
     */
    public function getUrl($urlKey)
    {
        $url = self::ZAZMA_URL_LIVE;

        if (Mage::helper('zazma')->isSandboxEnabled()) {
            $url = self::ZAZMA_URL_SANDBOX;
        }

        return $url . $urlKey;
    }

    /**
     * Send API request
     *
     * @param string $url
     * @param array $data
     * @param string $method
     * @return array
     * @throws Belvg_Zazma_Exception
     */
    public function sendRequest($url, $data, $method = 'POST')
    {
        try {
            $data['user'] = $this->_apiUserName;
            $data['pwd'] = $this->_apiPassword;

            $result = parent::sendRequest($url, $data, $method);

            $status = NULL;

            // some zazma requests return "Status", others - "status"
            if (isset($result['status'])) {
                $status = $result['status'];
            }

            if (isset($result['Status'])) {
                $status = $result['Status'];
            }

            if ($status == 'Success') {
                $result['Status'] = $status;
                return $result;
            }

            if ($status == 'Error') {
                if (isset($result['moreInfo'])) {
                    $errors = array();

                    foreach ($result['moreInfo'] as $info) {
                        foreach ($info as $error) {
                            $errors[] = $error;
                        }
                    }

                    if (!empty($errors)) {
                        throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, implode('<br />', $errors));
                    }
                }
            }

            if ($status == 'Failure') {
                if (isset($result['Error'])) {
                    $errors = array();
                    foreach ($result['Error'] as $info) {
                        foreach ($info as $error) {
                            $errors[] = $error;
                        }
                    }
                }
                if (!empty($errors)) {
                    throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, implode('<br />', $errors));
                }
            }
        } catch (Belvg_Zazma_Exception $ze) {
            Mage::log($ze->getMessage(), NULL, Belvg_Zazma_Helper_Data::ZAZMA_MODULE_LOG_NAME);
            $result['error'] = $ze->getMessage();
            return $result;
        }
    }

    /**
     * Send order details to zazma.com, prepare redirect url
     *
     * @param array $data
     * @return array
     */
    public function setCheckOut($data)
    {
        $url = $this->getUrl(self::ZAZMA_URL_SET_CHECKOUT);
        return $this->sendRequest($url, $data);
    }

    /**
     * Validate customer's details
     *
     * @param array $data
     * @return boolean
     */
    public function isCheckoutSupported($data)
    {
        $url = $this->getUrl(self::ZAZMA_URL_VERIFY);
        $result = $this->sendRequest($url, $data, 'GET');

        if (!isset($result['Status'])) {
            return FALSE;
        }

        if ($result['Status'] == 'Failure') {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check order status and fees
     *
     * @param array $data
     * @return string
     */
    public function getCheckoutDetails($data)
    {
        $url = $this->getUrl(self::ZAZMA_URL_GET_CHECKOUT);
        $result = $this->sendRequest($url, $data, 'GET');

        return $result;
    }

    /**
     * Finalize order request
     *
     * @param type $data
     * @return type
     * @TODO CHECK REQUEST METHOD
     */
    public function doCheckoutPayment($data)
    {
        $url = $this->getUrl(self::ZAZMA_URL_DO_CHECKOUT);
        $result = $this->sendRequest($url, $data, 'GET');

        return $result;
    }

}

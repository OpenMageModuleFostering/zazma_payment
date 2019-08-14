<?php

class Belvg_Zazma_Helper_Config extends Mage_Core_Helper_Data
{

    const ZAZMA_MODULE_KEY = 'Belvg_Zazma';
    const ZAZMA_MODULE_LOG_NAME = 'zazma-payments.log';

    const ZAZMA_LOGO_SRC = 'https://www.zazma.com/images/magento_logo.png';
    const ZAZMA_POPUP_HREF = 'https://www.zazma.com/site/magento_popup';

    const XML_PATH_ZAZMA_USERNAME = 'payment/zazma/api_user';
    const XML_PATH_ZAZMA_PASSWORD = 'payment/zazma/api_password';
    const XML_PATH_ZAZMA_VERIFICATION = 'payment/zazma/verification';
    const XML_PATH_ZAZMA_SENDBOX = 'payment/zazma/sandbox';

    /**
     * Get Extension version
     *
     * @see Belvg_Zazma_Model_Api
     * @return string
     */
    public function getExtensionVersion()
    {
        return Mage::getConfig()->getModuleConfig(self::ZAZMA_MODULE_KEY)->version;
    }

    /**
     * Get Magento Edition
     *
     * @return string
     */
    public function getEdition()
    {
        if (method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
        } else if (class_exists('Enterprise_Cms_Helper_Data')) {
            $edition = 'Enterprise';
        } else {
            $edition = 'Community';
        }

        return $edition;
    }

    /**
     * Get supplier API username
     *
     * @param nixed $store
     * @return string
     */
    public function getApiUsername($store = '')
    {
        return Mage::getStoreConfig(self::XML_PATH_ZAZMA_USERNAME, $store);
    }

    /**
     * Get supplier API password
     *
     * @param nixed $store
     * @return string
     */
    public function getApiPassword($store = '')
    {
        return Mage::getStoreConfig(self::XML_PATH_ZAZMA_PASSWORD, $store);
    }

    /**
     * Check if method should send user verification request
     *
     * @param nixed $store
     * @return boolean
     */
    public function getNeedVerification($store = '')
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ZAZMA_VERIFICATION, $store);
    }

    /**
     * Check if method should send user verification request
     *
     * @param nixed $store
     * @return boolean
     */
    public function isSandboxEnabled($store = '')
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ZAZMA_SENDBOX, $store);
    }
}

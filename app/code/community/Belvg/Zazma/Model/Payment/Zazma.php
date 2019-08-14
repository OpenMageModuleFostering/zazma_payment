<?php

class Belvg_Zazma_Model_Payment_Zazma extends Mage_Payment_Model_Method_Abstract
{

    /**
     * unique internal payment method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code = 'zazma';

    /**
     * Here are examples of flags that will determine functionality availability
     * of this module to be used by frontend and backend.
     *
     * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
     *
     * It is possible to have a custom dynamic logic by overloading
     * public function can* for each flag respectively
     */

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway = TRUE;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize = TRUE;

    /**
     * Can capture funds online?
     */
    protected $_canCapture = TRUE;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial = FALSE;

    /**
     * Can refund online?
     */
    protected $_canRefund = FALSE;

    /**
     * Can void transactions online?
     */
    protected $_canVoid = TRUE;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = TRUE;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = TRUE;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping = TRUE;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = FALSE;

    protected $_formBlockType = 'zazma/payment_form_zazma';

    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('zazma/checkout/place');
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (Mage::helper('zazma')->getNeedVerification() && $quote->getBillingAddress()->getEmail()) {
            $data = array();

            $data['firstName'] = $quote->getCustomerFirstname();
            $data['lastName'] = $quote->getCustomerLastname();
            $data['email'] = $quote->getCustomerEmail();
            $data['subTotal'] = $quote->getSubtotalWithDiscount();
            $data['address'] = implode(',', $quote->getBillingAddress()->getStreet());
            $data['city'] = $quote->getBillingAddress()->getCity();
            $data['state'] = 'n/a';

            if ($regionId = $quote->getBillingAddress()->getRegionId()) {
                $regionModel = Mage::getModel('directory/region')->load($regionId);
                $data['state'] = $regionModel->getCode();
            }

            $data['zip'] = $quote->getBillingAddress()->getPostcode();
            $data['companyName'] = $quote->getBillingAddress()->getCompany();
            $data['telephone'] = $quote->getBillingAddress()->getTelephone();
            
            $api = Mage::getModel('zazma/api_request');
            /* @var $api Belvg_Zazma_Model_Api_Request */

            if (!$api->isCheckoutSupported($data)) {
                return FALSE;
            }
        }

        return parent::isAvailable($quote);
    }

    public function getComment()
    {
        return $this->getConfigData('comment');
    }

}

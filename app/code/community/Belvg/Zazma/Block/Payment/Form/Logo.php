<?php

class Belvg_Zazma_Block_Payment_Form_Logo extends Mage_Payment_Block_Form
{
    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('belvg/zazma/payment/form/logo.phtml');
    }

    public function getPaymentLogoSrc()
    {
        return Belvg_Zazma_Helper_Data::ZAZMA_LOGO_SRC;
    }

    public function getPaymentPopupHref()
    {
        return Belvg_Zazma_Helper_Data::ZAZMA_POPUP_HREF;
    }

}

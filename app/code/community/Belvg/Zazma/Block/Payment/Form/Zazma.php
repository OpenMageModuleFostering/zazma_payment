<?php

class Belvg_Zazma_Block_Payment_Form_Zazma extends Mage_Payment_Block_Form
{

    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();
        $mark = Mage::app()->getLayout()->addBlock('zazma/payment_form_logo', 'logo');
        $this->setTemplate('belvg/zazma/payment/form/zazma.phtml')
                ->setMethodTitle('')->setMethodLabelAfterHtml($mark->toHtml());
    }

    public function getComment()
    {
        return $this->getMethod()->getComment();
    }

}

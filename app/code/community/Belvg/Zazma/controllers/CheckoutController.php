<?php

class Belvg_Zazma_CheckoutController extends Mage_Core_Controller_Front_Action
{

    /**
     * Find last Customer's Order,
     * redirect to Zazma Checkout URL
     *
     * @return void
     */
    public function placeAction()
    {
        try {
            // load order
            $orderIncrementId = $this->_getCheckoutSession()
                    ->getLastRealOrderId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            /* @var $order Mage_Sales_Model_Order */

            if (!$order->getId()) {
                Mage::throwException();
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Order not found'));
            }
            Mage::register('current_order', $order);


            $data = array();
            $items = array();
            $qty = 0;

            foreach ($order->getAllItems() as $item) {
                $items[] = $item->getName();
                $qty += $item->getQtyOrdered();
            }
            $uniqueItems = array_unique($items);
            $data['item'] = implode(', ', $uniqueItems);
            $data['orderNumber'] = $orderIncrementId;
            $data['description'] = $this->__('Order number %s', $orderIncrementId);
            $data['price'] = $order->getGrandTotal() - $order->getShippingAmount();
            $data['qty'] = $qty;
            $data['shipping'] = $order->getShippingAmount();
            $data['totalRetail'] = $order->getGrandTotal();
            $data['subTotal'] = $order->getGrandTotal();
            $data['returnUrl'] = Mage::getUrl('zazma/checkout/success');
            $data['cancelUrl'] = Mage::getUrl('zazma/checkout/cancel');

            $api = Mage::getModel('zazma/api_request');
            /* @var $api Belvg_Zazma_Model_Api_Request */

            $result = $api->setCheckOut($data);

            if (isset($result['error'])) {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $result['error']);
            }

            if (isset($result['token'])) {
                $this->_getCheckoutSession()->setZazmaToken($result['token']);
            } else {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Token sting is corrupted, please try later'));
            }

            if (isset($result['REDIRECT_URL'])) {
                $this->_redirectUrl($result['REDIRECT_URL']);
            } else {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Service is unavailable, please try later'));
            }
        } catch (Belvg_Zazma_Exception $exc) {
            $this->_getCheckoutSession()->addError($exc->getMessage());
            $this->reorderAction();
            $this->_redirect('checkout/cart');
        } catch (Exception $exc) {
            $this->_getCheckoutSession()->addError($this->__('Unable to check out.'));
            Mage::logException($exc);
            $this->_redirect('checkout/cart');
        }
    }

    public function cancelAction()
    {
        try {
            // load order
            $orderIncrementId = $this->_getCheckoutSession()
                    ->getLastRealOrderId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            /* @var $order Mage_Sales_Model_Order */

            if (!$order->getId()) {
                Mage::throwException();
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Order not found'));
            }
            Mage::register('current_order', $order);

            if ($order->canCancel()) {
                $order->cancel()->save();
                $this->_getCheckoutSession()->setLastRealOrderId(NULL);
                $this->_getCheckoutSession()->clear();
            }
            $this->_getCheckoutSession()->addError($this->__('Your order was canceled'));
            $this->reorderAction();
            $this->_redirect('checkout/cart');
        } catch (Belvg_Zazma_Exception $exc) {
            $this->_getCheckoutSession()->addError($exc->getMessage());
            $this->_redirect('checkout/cart');
        } catch (Exception $exc) {
            $this->_getCheckoutSession()->addError($this->__('Order was not found'));
            Mage::logException($exc);
            $this->_redirect('checkout/cart');
        }
    }

    public function successAction()
    {
        try {
            // load order
            $orderIncrementId = $this->_getCheckoutSession()
                    ->getLastRealOrderId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            /* @var $order Mage_Sales_Model_Order */

            if (!$order->getId()) {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Order not found'));
            }

            $token = $this->getRequest()->getParam('token');

            if ($token !== $this->_getCheckoutSession()->getZazmaToken()) {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Order not found. Invalid token'));
            }

            $api = Mage::getModel('zazma/api_request');
            /* @var $api Belvg_Zazma_Model_Api_Request */

            $data = array('token' => $token);
            $result = $api->getCheckoutDetails($data);

            if (isset($result['Status']) && $result['Status']) {

                if (isset($result['orderNumber'])) {
                    $zazmaOrderId = $result['orderNumber'];

                    if ($zazmaOrderId != $orderIncrementId) {
                        throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Can not retreive order number'));
                    }

                    $result = $api->doCheckoutPayment($data);

                    if (isset($result['Status']) && $result['Status']) {
                        $this->completeOrder($order);
                    } else {
                        throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Can not complete order'));
                    }
                } else {
                    throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Can not retreive order number'));
                }
            } else {
                throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Payment was not approver by Zazma.com'));
            }
        } catch (Belvg_Zazma_Exception $e) {
            $this->_getCheckoutSession()->addError($this->__($e->getMessage()));
            Mage::logException($e);
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Generate invoice for the order, redirect to the success page
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function completeOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order->canInvoice()) {
            throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Cannot create an invoice'));
        }

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        if (!$invoice->getTotalQty()) {
            throw Mage::exception(Belvg_Zazma_Helper_Data::ZAZMA_MODULE_KEY, $this->__('Cannot create an invoice without products'));
        }
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
        $transactionSave->save();

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Get Customer's Checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Action for reorder
     */
    public function reorderAction()
    {
        if ($order = Mage::registry('current_order')) {

            $cart = Mage::getSingleton('checkout/cart');
            $cartTruncated = false;
            /* @var $cart Mage_Checkout_Model_Cart */

            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                try {
                    $cart->addOrderItem($item);
                } catch (Mage_Core_Exception $e) {
                    if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                        Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                    } else {
                        Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('checkout/session')->addException($e, Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
                    );
                }
            }

            $cart->save();
        }
    }

}

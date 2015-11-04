<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Order_OrderController extends Mage_Sales_Controller_Abstract
{
    const CANCEL_FAIL_MESSAGE = 'EbayEnterprise_Order_Cancel_Fail_Message';
    const CANCEL_SUCCESS_MESSAGE = 'EbayEnterprise_Order_Cancel_Success_Message';
    const LOGGED_IN_ORDER_HISTORY_PATH = '*/*/history';
    const GUEST_ORDER_FORM_PATH = 'sales/guest/form';

    /** @var EbayEnterprise_Order_Helper_Data */
    protected $_orderHelper;
    /** @var EbayEnterprise_Order_Model_Detail */
    protected $_orderFactory;

    protected function _construct()
    {
        parent::_construct();
        $this->_orderHelper = Mage::helper('ebayenterprise_order');
        $this->_orderFactory = Mage::helper('ebayenterprise_order/factory');
    }

    /**
     * load the order with the order id from the request and
     * bypass loading a shipment instance from the db.
     */
    public function printShipmentAction()
    {
        if ($this->_loadValidOrder()) {
            $this->loadLayout('print');
            $this->renderLayout();
        } else {
            if ($this->_orderFactory->getCustomerSession()->isLoggedIn()) {
                $this->_redirect('*/*/history');
            } else {
                $this->_redirect('sales/guest/form');
            }
        }
    }

    /**
     * load the order with the order id from the request and
     * bypass loading a shipment instance from the db.
     */
    public function printOrderShipmentAction()
    {
        if ($this->_loadValidOrder() && $this->getRequest()->getParam('shipment_id')) {
            $this->loadLayout('print');
            $this->renderLayout();
        } else {
            if ($this->_orderFactory->getCustomerSession()->isLoggedIn()) {
                $this->_redirect('*/*/history');
            } else {
                $this->_redirect('sales/guest/form');
            }
        }
    }

    protected function _loadValidOrder($orderId = null)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        if (null === $orderId) {
            $orderId = (string) $request->getParam('order_id');
        }
        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        Mage::unregister('rom_order');
        $detailApi = $this->_orderFactory->getNewRomOrderDetailModel($orderId);
        try {
            $romOrderObject = $detailApi->process();
        } catch (EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception $e) {
            $this->_handleOrderDetailException($e);
            return false;
        }
        Mage::register('rom_order', $romOrderObject);
        return true;
    }

    /**
     * Request details on the order you are given.
     */
    public function _viewAction()
    {
        if ($this->_loadValidOrder()) {
            $this->_displayPage();
        }
    }

    /**
     * Handle exception thrown from making order detail request.
     *
     * @param  EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception
     * @return self
     */
    protected function _handleOrderDetailException(EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception $e)
    {
        // Set the error message
        $coreSession = $this->_orderFactory->getCoreSessionModel();
        $coreSession->addError($e->getMessage());

        //Redirect to the rom return path
        $customerSession = $this->_orderFactory->getCustomerSession();
        $returnPath = $this->_getRomReturnPath($customerSession);
        $this->_redirect($returnPath);

        return $this;
    }

    /**
     * Display the page.
     */
    protected function _displayPage()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
        if ($this->_orderFactory->getCustomerSession()->isLoggedIn()) {
            $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
            if ($navigationBlock) {
                $navigationBlock->setActive('sales/order/history');
            }
        }
        $this->renderLayout();
    }

    /**
     * Determine the path to redirect to based on customer logging status.
     *
     * @param  Mage_Customer_Model_Session
     * @return string
     */
    protected function _getRomReturnPath(Mage_Customer_Model_Session $session)
    {
        return $session->isLoggedIn()
            ? static::LOGGED_IN_ORDER_HISTORY_PATH
            : static::GUEST_ORDER_FORM_PATH;
    }

    public function romViewAction()
    {
        $this->_viewAction();
    }

    public function romShipmentAction()
    {
        $this->_viewAction();
    }

    public function romGuestShipmentAction()
    {
        $this->_viewAction();
    }

    public function romGuestViewAction()
    {
        $this->_viewAction();
    }

    /**
     * Handle canceling ROM order via ROM order cancel service.
     */
    public function romCancelAction()
    {
        if ($this->_isValidOperation()) {
            if ($this->_canShowOrderCancelForm()) {
                $this->_setRefererUrlInSession()
                    ->_showOrderCancelPage();
                return;
            }
            $this->_processOrderCancelAction();
            return;
        }
        if ($this->_orderFactory->getCustomerSession()->isLoggedIn()) {
            $this->_redirect('*/*/history');
        } else {
            $this->_redirect('sales/guest/form');
        }
    }

    /**
     * Handle canceling ROM order via ROM order cancel service for a guest customer.
     */
    public function romGuestCancelAction()
    {
        $this->romCancelAction();
    }

    /**
     * Stash the referer URL in the customer session in order
     * to redirect them after they have finished cancelling their
     * order.
     *
     * @return self
     */
    protected function _setRefererUrlInSession()
    {
        $this->_getRomCancelSession()
            ->setCancelActionRefererUrl($this->_getRefererUrl());
        return $this;
    }

    /**
     * Process the order cancel action.
     *
     * @return self
     */
    protected function _processOrderCancelAction()
    {
        /** @var Mage_Sales_Model_Order */
        $order = $this->_getRomOrderToBeCanceled();
        /** @var EbayEnterprise_Order_Model_ICancel */
        $cancel = $this->_getRomOrderCancelModel($order);
        /** @var Mage_Core_Model_Session */
        $session = $this->_getRomCancelSession();
        /** @var string */
        $redirectUrl = $session->getCancelActionRefererUrl() ?: $this->_getRefererUrl();
        try {
            $cancel->process();
        } catch (Exception $e) {
            return $this->_handleRomCancelException($e, $session, $redirectUrl);
        }
        return $this->_handleRomCancelResponse($order, $session, $redirectUrl);
    }

    /**
     * Show the order cancel page.
     *
     * @return self
     */
    protected function _showOrderCancelPage()
    {
        $this->_viewAction();
        return $this;
    }

    /**
     * Determine if we can show the order cancel form.
     *
     * @return bool
     */
    protected function _canShowOrderCancelForm()
    {
        return ($this->_orderHelper->hasOrderCancelReason() && !$this->_isOrderCancelPostAction());
    }

    /**
     * Determine if the order cancel form was submitted.
     *
     * @return bool
     */
    protected function _isOrderCancelPostAction()
    {
        $request = $this->getRequest();
        return $request->isPost() && $request->getPost('cancel_action');
    }

    /**
     * Load the sales/order to be canceled.
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getRomOrderToBeCanceled()
    {
        $request = $this->getRequest();
        $orderId = $request->getParam('order_id');
        return Mage::getModel('sales/order')
            ->loadByIncrementId($orderId)
            ->setCancelReasonCode($request->getParam('cancel_reason'))
            ->setIncrementId($orderId);
    }

    /**
     * Get the ROM order cancel model.
     *
     * @param  Mage_Sales_Model_Order
     * @return EbayEnterprise_Order_Model_ICancel
     */
    protected function _getRomOrderCancelModel(Mage_Sales_Model_Order $order)
    {
        return Mage::getModel('ebayenterprise_order/cancel', ['order' => $order]);
    }

    /**
     * Get core session for ROM cancel action.
     *
     * @return Mage_Core_Model_Session
     */
    protected function _getRomCancelSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Redirect and notify the customer of a successful or a fail
     * cancel order action.
     *
     * @param  Mage_Sales_Model_Order
     * @param  Mage_Core_Model_Session
     * @param  string
     * @return self
     */
    protected function _handleRomCancelResponse(
        Mage_Sales_Model_Order $order,
        Mage_Core_Model_Session $session,
        $redirectUrl
    ) {
        $incrementId = $order->getIncrementId();
        if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            $session->addSuccess(sprintf($this->_orderHelper->__(static::CANCEL_SUCCESS_MESSAGE), $incrementId));
        } else {
            $session->addError(sprintf($this->_orderHelper->__(static::CANCEL_FAIL_MESSAGE), $incrementId));
        }
        $this->_redirectUrl($redirectUrl);
        return $this;
    }

    /**
     * Redirect and notify the customer of an order could not be canceled.
     *
     * @param  Exception
     * @param  Mage_Core_Model_Session
     * @param  string
     * @return self
     */
    protected function _handleRomCancelException(
        Exception $e,
        Mage_Core_Model_Session $session,
        $redirectUrl
    ) {
        $session->addError($this->_orderHelper->__($e->getMessage()));
        $this->_redirectUrl($redirectUrl);
        return $this;
    }

    /**
     * Ensure the proper data exist in before proceeding to show the current page.
     *
     * @return self
     */
    protected function _isValidOperation()
    {
        $orderId = trim($this->getRequest()->getParam('order_id'));
        return !empty($orderId);
    }

    /**
     * controller action for displaying shipment tracking information.
     */
    public function romtrackingpopupAction()
    {
        $this->_loadValidOrder();
        Mage::unregister('rom_order_shipment_tracking');
        /** @var Mage_Core_Controller_Request_Http */
        $request = $this->getRequest();
        /** @var EbayEnterprise_Order_Model_Tracking */
        $tracking = $this->_orderFactory->getNewTrackingModel(
            Mage::registry('rom_order'),
            $request->getParam('shipment_id'),
            $request->getParam('tracking_number')
        );
        Mage::register('rom_order_shipment_tracking', $tracking);
        $this->loadLayout();
        $this->renderLayout();
    }
}

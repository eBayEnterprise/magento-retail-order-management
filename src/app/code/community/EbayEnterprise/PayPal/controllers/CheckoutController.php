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

/**
 * Checkout Controller for Ebay Enterprise PayPal
 */
class EbayEnterprise_PayPal_CheckoutController
	extends Mage_Core_Controller_Front_Action
{
	/** @var EbayEnterprise_Paypal_Model_Express_Checkout */
	protected $_checkout = null;

	/** @var EbayEnterprise_PayPal_Helper_Data */
	protected $_helper = null;
	/** @var EbayEnterprise_PayPal_Model_Config */
	protected $_config = null;
	/** @var Mage_Sales_Model_Quote */
	protected $_quote = false;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * prepare instances of the config model and the helper.
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->_helper = Mage::helper('ebayenterprise_paypal');
		$this->_config = $this->_helper->getConfigModel();
		$this->_logger = Mage::helper('ebayenterprise_magelog');
	}

	/**
	 * prevent dispatching controller actions when not enabled.
	 *
	 * @return self
	 */
	public function preDispatch()
	{
		parent::preDispatch();
		if (!$this->_config->isEnabledFlag) {
			// when the payment method is disabled, prevent dispatching actions from
			// this controller.
			$this->setFlag('', static::FLAG_NO_DISPATCH, true)->_forward(
				'/noroute'
			);
		}
		return $this;
	}

	/**
	 * return true if a guest is allowed to checkout without registering
	 * @return boolean
	 */
	protected function _isGuestAllowedWithoutRegistering($quoteCheckoutMethod, Mage_Sales_Model_Quote $quote)
	{
		return (!$quoteCheckoutMethod || $quoteCheckoutMethod != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) &&
			!Mage::helper('checkout')->isAllowedGuestCheckout($quote, $quote->getStoreId());
	}

	/**
	 * Start Express Checkout by requesting initial token and dispatching customer to PayPal
	 */
	public function startAction()
	{
		try {
			$this->_initCheckout();

			if ($this->_getQuote()->getIsMultiShipping()
			) { // Multi-shipping is not supported
				$this->_getQuote()->setIsMultiShipping(false);
				$this->_getQuote()->removeAllAddresses();
			}

			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$quoteCheckoutMethod = $this->_getQuote()->getCheckoutMethod();
			if ($customer && $customer->getId()) {
				$this->_checkout->setCustomerWithAddressChange(
					$customer, $this->_getQuote()->getBillingAddress(),
					$this->_getQuote()->getShippingAddress()
				);
			} elseif ($this->_isGuestAllowedWithoutRegistering($quoteCheckoutMethod, $this->_getQuote())) {
				Mage::getSingleton('core/session')->addNotice(
					$this->_helper->__(
						'To proceed to Checkout, please log in using your email address.'
					)
				);
				$this->redirectLogin();
				Mage::getSingleton('customer/session')
					->setBeforeAuthUrl(
						Mage::getUrl('*/*/*', array('_current' => true))
					);
				return;
			}

			try {
				$buttonKey = EbayEnterprise_Paypal_Model_Express_Checkout::PAYMENT_INFO_BUTTON;
				$startReply = $this->_checkout->start(
					Mage::getUrl('*/*/return'),
					Mage::getUrl('*/*/cancel'),
					$this->getRequest()->getParam($buttonKey)
				);
				$this->_initToken($startReply['token']);
				$this->_redirectToPayPalSite($startReply);
				return;
			} catch (EbayEnterprise_Paypal_Exception $e) {
				$this->_getCheckoutSession()->addError($e->getMessage());
				$this->_redirect('checkout/cart');
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getCheckoutSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getCheckoutSession()->addError(
				$this->__('Unable to start Express Checkout.')
			);
			$this->_logger->logException($e);
		}

		$this->_redirect('checkout/cart');
	}

	/**
	 * Return shipping options items for shipping address from request
	 */
	public function shippingOptionsCallbackAction()
	{
		try {
			$quoteId = $this->getRequest()->getParam('quote_id');
			$this->_quote = Mage::getModel('sales/quote')->load($quoteId);
			$this->_initCheckout();
			$response = $this->_checkout->getShippingOptionsCallbackResponse(
				$this->getRequest()->getParams()
			);
			$this->getResponse()->setBody($response);
		} catch (Exception $e) {
			$this->_logger->logException($e);
		}
	}

	/**
	 * Cancel Express Checkout
	 */
	public function cancelAction()
	{
		try {
			$this->_initToken(false);
			// TODO verify if this logic of order cancelation is deprecated
			// if there is an order - cancel it
			$orderId = $this->_getCheckoutSession()->getLastOrderId();
			$order = ($orderId) ? Mage::getModel('sales/order')->load($orderId)
				: false;
			if ($order && $order->getId()
				&& $order->getQuoteId() == $this->_getCheckoutSession()
					->getQuoteId()
			) {
				$order->cancel()->save();
				$this->_getCheckoutSession()
					->unsLastQuoteId()
					->unsLastSuccessQuoteId()
					->unsLastOrderId()
					->unsLastRealOrderId()
					->addSuccess(
						$this->__(
							'Express Checkout and Order have been canceled.'
						)
					);
			} else {
				$this->_getCheckoutSession()->addSuccess(
					$this->__('Express Checkout has been canceled.')
				);
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getCheckoutSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getCheckoutSession()->addError(
				$this->__('Unable to cancel Express Checkout.')
			);
			$this->_logger->logException($e);
		}

		$this->_redirect('checkout/cart');
	}

	/**
	 * Return from PayPal and dispatch customer to order review page
	 */
	public function returnAction()
	{
		if ($this->getRequest()->getParam('retry_authorization') == 'true'
			&& is_array(
				$this->_getCheckoutSession()->getPaypalTransactionData()
			)
		) {
			$this->_forward('placeOrder');
			return;
		}
		try {
			$this->_getCheckoutSession()->unsPaypalTransactionData();
			$this->_checkout = $this->_initCheckout();
			$this->_checkout->returnFromPaypal($this->_initToken());
			$this->_redirect('*/*/review');
			return;
		} catch (Mage_Core_Exception $e) {
			Mage::getSingleton('checkout/session')->addError($e->getMessage());
		} catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError(
				$this->__('Unable to process Express Checkout approval.')
			);
			$this->_logger->logException($e);
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * Review order after returning from PayPal
	 */
	public function reviewAction()
	{
		try {
			$this->_initCheckout();
			$this->_checkout->prepareOrderReview($this->_initToken());
			$this->loadLayout();
			$this->_initLayoutMessages('ebayenterprise_paypal/session');
			$reviewBlock = $this->getLayout()->getBlock(
				'ebayenterprise_paypal.express.review'
			);
			$reviewBlock->setQuote($this->_getQuote());
			$reviewBlock->getChild('details')->setQuote($this->_getQuote());
			if ($reviewBlock->getChild('shipping_method')) {
				$reviewBlock->getChild('shipping_method')->setQuote(
					$this->_getQuote()
				);
			}
			$this->renderLayout();
			return;
		} catch (Mage_Core_Exception $e) {
			Mage::getSingleton('checkout/session')->addError($e->getMessage());
		} catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError(
				$this->__('Unable to initialize Express Checkout review.')
			);
			$this->_logger->logException($e);
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * Redirect back to PayPal to all editing payment information
	 */
	public function editAction()
	{
		try {
			$this->_redirectToPayPalSite(
				array(
					'useraction' => 'continue',
					'token'      => $this->_initToken(),
				)
			);
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
			$this->_redirect('*/*/review');
		}
	}

	/**
	 * Update shipping method (combined action for ajax and regular request)
	 */
	public function saveShippingMethodAction()
	{
		try {
			$isAjax = $this->getRequest()->getParam('isAjax');
			$this->_initCheckout();
			$this->_checkout->updateShippingMethod(
				$this->getRequest()->getParam('shipping_method')
			);
			if ($isAjax) {
				$this->loadLayout('paypal_express_review_details');
				$this->getResponse()->setBody(
					$this->getLayout()->getBlock('root')
						->setQuote($this->_getQuote())
						->toHtml()
				);
				return;
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addError(
				$this->__('Unable to update shipping method.')
			);
			$this->_logger->logException($e);
		}
		if ($isAjax) {
			$this->getResponse()->setBody(
				'<script type="text/javascript">window.location.href = '
				. Mage::getUrl('*/*/review') . ';</script>'
			);
		} else {
			$this->_redirect('*/*/review');
		}
	}

	/**
	 * Submit the order
	 */
	public function placeOrderAction()
	{
		try {
			$requiredAgreements = Mage::helper('checkout')
				->getRequiredAgreementIds();
			if ($requiredAgreements) {
				$postedAgreements = array_keys(
					$this->getRequest()->getPost('agreement', array())
				);
				if (array_diff($requiredAgreements, $postedAgreements)) {
					Mage::throwException(
						$this->_helper->__(
							'Please agree to all the terms and conditions before placing the order.'
						)
					);
				}
			}

			$this->_initCheckout();
			$this->_checkout->place($this->_initToken());

			// prepare session to success or cancellation page
			$session = $this->_getCheckoutSession();
			$session->clearHelperData();

			// last successful quote
			$quoteId = $this->_getQuote()->getId();
			$session->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

			// an order may be created
			$order = $this->_checkout->getOrder();
			if ($order) {
				$session->setLastOrderId($order->getId())
					->setLastRealOrderId($order->getIncrementId());
			}
			$this->_initToken(false); // no need in token anymore
			$this->_redirect('checkout/onepage/success');
			return;
		} catch (Mage_Core_Exception $e) {
			Mage::helper('checkout')->sendPaymentFailedEmail(
				$this->_getQuote(), $e->getMessage()
			);
			$this->_getSession()->addError($e->getMessage());
			$this->_redirect('*/*/review');
		} catch (Exception $e) {
			Mage::helper('checkout')->sendPaymentFailedEmail(
				$this->_getQuote(),
				$this->__('Unable to place the order.')
			);
			$this->_getSession()->addError(
				$this->__('Unable to place the order.')
			);
			$this->_logger->logException($e);
			$this->_redirect('*/*/review');
		}
	}

	/**
	 * Redirect customer back to PayPal with the same token
	 */
	protected function _redirectSameToken()
	{
		$token = $this->_initToken();
		$this->getResponse()->setRedirect(
			$this->_config->getExpressCheckoutStartUrl($token)
		);
	}

	/**
	 * Redirect customer to shopping cart and show error message
	 *
	 * @param string $errorMessage
	 */
	protected function _redirectToCartAndShowError($errorMessage)
	{
		$cart = Mage::getSingleton('checkout/cart');
		$cart->getCheckoutSession()->addError($errorMessage);
		$this->_redirect('checkout/cart');
	}

	/**
	 * Instantiate quote and checkout
	 *
	 * @return EbayEnterprise_PayPal_CheckoutController
	 * @throws Mage_Core_Exception
	 */
	protected function _initCheckout()
	{
		$quote = $this->_getQuote();
		if (!$quote->hasItems() || $quote->getHasError()) {
			$this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
			Mage::throwException(
				$this->_helper->__('Unable to initialize Express Checkout.')
			);
		}
		$this->_checkout = Mage::getSingleton(
			'ebayenterprise_paypal/express_checkout', array(
				'helper' => $this->_helper,
				'logger' => null,
				'config' => $this->_config,
				'quote'  => $quote
			)
		);
		$this->_checkout->setCustomerSession(
			Mage::getSingleton('customer/session')
		);
		return $this->_checkout;
	}

	/**
	 * Search for proper checkout token in request or session or (un)set specified one
	 *
	 * @param string $setToken
	 *
	 * @return EbayEnterprise_PayPal_CheckoutController |string
	 */
	protected function _initToken($setToken = null)
	{
		if (null !== $setToken) {
			if (false === $setToken) {
				// security measure for avoid unsetting token twice
				if (!$this->_getSession()->getExpressCheckoutToken()) {
					Mage::throwException(
						$this->_helper->__(
							'PayPal Express Checkout Token does not exist.'
						)
					);
				}
				$this->_getSession()->unsExpressCheckoutToken();
			} else {
				$this->_getSession()->setExpressCheckoutToken($setToken);
			}
			return $this;
		}
		if ($setToken = $this->getRequest()->getParam('token')) {
			if ($setToken !== $this->_getSession()->getExpressCheckoutToken()) {
				Mage::throwException(
					$this->_helper->__(
						'Wrong PayPal Express Checkout Token specified.'
					)
				);
			}
		} else {
			$setToken = $this->_getSession()->getExpressCheckoutToken();
		}
		return $setToken;
	}

	/**
	 * PayPal session instance getter
	 *
	 * @return EbayEnterprise_PayPal_Model_Session
	 */
	private function _getSession()
	{
		return Mage::getSingleton('ebayenterprise_paypal/session');
	}

	/**
	 * Return checkout session object
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Return checkout quote object
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	private function _getQuote()
	{
		if (!$this->_quote) {
			$this->_quote = $this->_getCheckoutSession()->getQuote();
		}
		return $this->_quote;
	}

	/**
	 * Redirect to login page
	 *
	 */
	public function redirectLogin()
	{
		$this->setFlag('', 'no-dispatch', true);
		$this->getResponse()->setRedirect(
			Mage::helper('core/url')->addRequestParam(
				Mage::helper('customer')->getLoginUrl(),
				array('context' => 'checkout')
			)
		);
	}

	/**
	 * redirect to the paypal site so the user can login.
	 *
	 * @param  array $data
	 * - requires 'token' => token string from the set express reply
	 *
	 * @return self
	 */
	protected function _redirectToPayPalSite(array $data)
	{
		$data['cmd'] = '_express-checkout';
		$mode = $this->_config->isSandboxedFlag ? 'sandbox.' : '';
		$queryString = http_build_query($data);
		$this->getResponse()->setRedirect(str_replace(
			array('{mode}', '{query_string}'),
			array($mode, $queryString),
			'https://www.{mode}paypal.com/cgi-bin/webscr?{query_string}'
		));
		return $this;
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

class TrueAction_Eb2cOrder_Overrides_Model_Paypal_Express_Checkout
	extends Mage_Paypal_Model_Express_Checkout
{
	/**
	 * override this method to dispatch the checkout_type_onepage_save_order_after event
	 * in order to send eb2c order create request.
	 * Place the order and recurring payment profiles when customer returned from paypal
	 * Until this moment all quote data must be valid
	 * @param string $token
	 * @param string $shippingMethodCode
	 */
	public function place($token, $shippingMethodCode=null)
	{
		parent::place($token, $shippingMethodCode);

		Mage::log(sprintf('[%s::%s]: dispatching event for paypal express checkout', __CLASS__, __METHOD__), Zend_Log::DEBUG);

		Mage::dispatchEvent(
			'checkout_type_onepage_save_order_after',
			array('order' => $this->_order, 'quote' => $this->_quote)
		);
	}
}

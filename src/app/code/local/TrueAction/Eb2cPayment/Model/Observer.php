<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Observer
{
	/**
	 * hold enterprise giftcardaccount instantiated object
	 *
	 * @var TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount
	 */
	protected $_giftCardAccount;

	/**
	 * hold quote instantiated object
	 *
	 * @var Mage_Sales_Model_Quote
	 */
	protected $_quote;

	/**
	 * @see $_giftCardAccount
	 * @return TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount
	 */
	protected function _getGiftCardAccount()
	{
		if (!$this->_giftCardAccount) {
			$this->_giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
		}

		return $this->_giftCardAccount;
	}

	/**
	 * @see $_quote
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _getQuote()
	{
		if (!$this->_quote) {
			$this->_quote = Mage::getModel('sales/quote');
		}

		return $this->_quote;
	}

	/**
	 * redeem any gift card when 'sales_order_payment_place_end' event is dispatched
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function redeemGiftCard($observer)
	{
		$payment = $observer->getEvent()->getPayment();
		$order = $payment->getOrder();
		$quote = $this->_getQuote()->load($order->getQuoteId());
		$giftCard = unserialize($quote->getGiftCards());

		if ($giftCard) {
			foreach ($giftCard as $card) {
				if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
					// We have a valid record, let's redeem gift card in eb2c.
					// TODO: once eb2c stored value redeem is implemented we'll redeem gift card
					$pan = $card['pan'];
					$pin = $card['pin'];
					$balance = $card['ba'];
					$incrementId = $order->getIncrementId();
				}
			}
		}
	}
}

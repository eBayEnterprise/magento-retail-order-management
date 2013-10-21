<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Observer
{
	/**
	 * redeem any gift card when 'eb2c_event_dispatch_after_inventory_allocation' event is dispatched
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function redeemGiftCard($observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$giftCard = unserialize($quote->getGiftCards());

		if ($giftCard) {
			foreach ($giftCard as $card) {
				if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
					// We have a valid record, let's redeem gift card in eb2c.
					$storeValueRedeemReply = Mage::getModel('eb2cpayment/stored_value_redeem')->getRedeem($card['pan'], $card['pin'], $quote->getId(), $card['ba']);
					if ($storeValueRedeemReply) {
						$redeemData = Mage::getModel('eb2cpayment/stored_value_redeem')->parseResponse($storeValueRedeemReply);
						if ($redeemData) {
							// making sure we have the right data
							if (isset($redeemData['responseCode']) && strtoupper(trim($redeemData['responseCode'])) === 'FAIL') {
								// removed gift card from the shopping cart
								Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByPanPin($card['pan'], $card['pin'])
									->removeFromCart();
								Mage::getSingleton('checkout/session')->addSuccess(
									Mage::helper('enterprise_giftcardaccount')->__('Gift Card "%s" was removed.', Mage::helper('core')->escapeHtml($card['pan']))
								);

								Mage::throwException(
									Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card account.')
								);
								// @codeCoverageIgnoreStart
							}
							// @codeCoverageIgnoreEnd
						}
					}
				}
			}
		}
	}

	/**
	 * RedeemVoid any gift card when 'eb2c_event_dispatch_after_inventory_allocation' event is dispatched
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function redeemVoidGiftCard($observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$giftCard = unserialize($quote->getGiftCards());

		if ($giftCard) {
			foreach ($giftCard as $card) {
				if (isset($card['ba']) && isset($card['pan']) && isset($card['pin'])) {
					// We have a valid record, let's RedeemVoid gift card in eb2c.
					$storeValueRedeemVoidReply = Mage::getModel('eb2cpayment/stored_value_redeem_void')
						->getRedeemVoid($card['pan'], $card['pin'], $quote->getId(), $card['ba']);
					if ($storeValueRedeemVoidReply) {
						$redeemVoidData = Mage::getModel('eb2cpayment/stored_value_redeem_void')->parseResponse($storeValueRedeemVoidReply);
						if ($redeemVoidData) {
							// making sure we have the right data
							if (isset($redeemVoidData['responseCode']) && strtoupper(trim($redeemVoidData['responseCode'])) === 'SUCCESS') {
								// removed gift card from the shopping cart
								Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByPanPin($card['pan'], $card['pin'])
									->removeFromCart();
								Mage::getSingleton('checkout/session')->addSuccess(
									Mage::helper('enterprise_giftcardaccount')->__('Gift Card "%s" was removed.', Mage::helper('core')->escapeHtml($card['pan']))
								);

								Mage::throwException(
									Mage::helper('enterprise_giftcardaccount')->__('Gift card account is not redeemable.')
								);
								// @codeCoverageIgnoreStart
							}
							// @codeCoverageIgnoreEnd
						}
					}
				}
			}
		}
	}

	/**
	 * Suppressing all non-eb2c payment modules or payment methods if eb2cpayment is turn on.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function suppressPaymentModule($observer)
	{
		$knownPaymentModule = array('Mage_Authorizenet', 'Mage_Paygate', 'Mage_PaypalUk', 'Mage_Payment', 'Mage_Paypal');

		foreach ($knownPaymentModule as $paymentModule) {
			Mage::log(sprintf("Inside [%s::%s]\n\r%s: %s", __CLASS__, __METHOD__, 'paymentModule', $paymentModule), Zend_Log::DEBUG);
			Mage::getModel('eb2cpayment/suppression')->disableModule($paymentModule);
		}

		return $this;
	}
}

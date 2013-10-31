<?php
class TrueAction_Eb2cPayment_Model_Observer
{
	const TRUEACTION_EB2CPAYMENT_GIFTCARD_REMOVED = 'TrueAction_Eb2cPayment_GiftCard_Removed';
	const TRUEACTION_EB2CPAYMENT_GIFTCARD_WRONG_ACCOUNT = 'TrueAction_Eb2cPayment_GiftCard_Wrong_Account';
	const TRUEACTION_EB2CPAYMENT_GIFTCARD_NOT_REDEEMABLE = 'TrueAction_Eb2cPayment_GiftCard_Not_Redeemable';
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
									Mage::helper('enterprise_giftcardaccount')->__(self::TRUEACTION_EB2CPAYMENT_GIFTCARD_REMOVED, Mage::helper('core')->escapeHtml($card['pan']))
								);

								Mage::throwException(
									Mage::helper('enterprise_giftcardaccount')->__(self::TRUEACTION_EB2CPAYMENT_GIFTCARD_WRONG_ACCOUNT)
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
									Mage::helper('enterprise_giftcardaccount')->__(self::TRUEACTION_EB2CPAYMENT_GIFTCARD_REMOVED, Mage::helper('core')->escapeHtml($card['pan']))
								);

								Mage::throwException(
									Mage::helper('enterprise_giftcardaccount')->__(self::TRUEACTION_EB2CPAYMENT_GIFTCARD_NOT_REDEEMABLE)
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
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			Mage::log(sprintf("[%s::%s] Enabling eBay Enterprise Payment Methods", __CLASS__, __METHOD__), Zend_Log::DEBUG);

			// first let's disable any none eBay Enterprise payment method
			Mage::getModel('eb2cpayment/suppression')->disableNoneEb2CPaymentMethods();

			// let's enable only payment bridge and not paypal express it can be enabled manually
			// via Exchange platform config section
			Mage::getModel('eb2cpayment/suppression')->saveEb2CPaymentMethods(1);
		} else {
			Mage::log(sprintf("[%s::%s] disabling eBay Enterprise Payment Methods", __CLASS__, __METHOD__), Zend_Log::DEBUG);
			// let's disabled payment bridge ebay Enterprise Payment method.
			Mage::getModel('eb2cpayment/suppression')->saveEb2CPaymentMethods(0);
		}

		return $this;
	}
}

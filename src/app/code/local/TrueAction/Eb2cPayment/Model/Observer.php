<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Observer
{
	/**
	 * eb2c stored value redeem object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Stored_Value_Redeem
	 */
	protected $_storedValueRedeem;

	/**
	 * eb2c stored value redeem void object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void
	 */
	protected $_storedValueRedeemVoid;

	/**
	 * hold enterprise giftcardaccount instantiated object
	 *
	 * @var TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount
	 */
	protected $_giftCardAccount;

	protected function _getStoredValueRedeem()
	{
		if (!$this->_storedValueRedeem) {
			$this->_storedValueRedeem = Mage::getModel('eb2cpayment/stored_value_redeem');
		}

		return $this->_storedValueRedeem;
	}

	protected function _getStoredValueRedeemVoid()
	{
		if (!$this->_storedValueRedeemVoid) {
			$this->_storedValueRedeemVoid = Mage::getModel('eb2cpayment/stored_value_redeem_void');
		}

		return $this->_storedValueRedeemVoid;
	}

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
					if ($storeValueRedeemReply = $this->_getStoredValueRedeem()->getRedeem($card['pan'], $card['pin'], $quote->getId(), $card['ba'])) {
						if ($redeemData = $this->_getStoredValueRedeem()->parseResponse($storeValueRedeemReply)) {
							// making sure we have the right data
							if (isset($redeemData['responseCode']) && strtoupper(trim($redeemData['responseCode'])) === 'FAIL') {
								// removed gift card from the shopping cart
								$this->_getGiftCardAccount()->loadByPanPin($card['pan'], $card['pin'])
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
					if ($storeValueRedeemVoidReply = $this->_getStoredValueRedeemVoid()->getRedeemVoid($card['pan'], $card['pin'], $quote->getId(), $card['ba'])) {
						if ($redeemVoidData = $this->_getStoredValueRedeemVoid()->parseResponse($storeValueRedeemVoidReply)) {
							// making sure we have the right data
							if (isset($redeemVoidData['responseCode']) && strtoupper(trim($redeemVoidData['responseCode'])) === 'SUCCESS') {
								// removed gift card from the shopping cart
								$this->_getGiftCardAccount()->loadByPanPin($card['pan'], $card['pin'])
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
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

require_once('Enterprise/GiftCardAccount/controllers/CartController.php');
class TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController extends Enterprise_GiftCardAccount_CartController
{
	/**
	 * Overriding Enterprise add gift card to cart controller
	 * Add Gift Card to current quote
	 *
	 */
	public function addAction()
	{
		$data = $this->getRequest()->getPost();
		if (isset($data['giftcard_code']) && isset($data['giftcard_pin'])) {
			$code = $data['giftcard_code']; // interpreting code as eb2c pan
			$pin = $data['giftcard_pin']; // getting pin data from user input
			try {
				if (strlen($code) > TrueAction_Eb2cPayment_Overrides_Helper_Data::GIFT_CARD_PAN_MAX_LENGTH) {
					Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Invalid gift card payment account numbers.'));
					// @codeCoverageIgnoreStart
				}
				// @codeCoverageIgnoreEnd

				if (strlen($pin) > TrueAction_Eb2cPayment_Overrides_Helper_Data::GIFT_CARD_PIN_MAX_LENGTH) {
					Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Invalid gift card personal identification numbers.'));
					// @codeCoverageIgnoreStart
				}
				// @codeCoverageIgnoreEnd

				// override this method to make eb2c stored value balance check request for actual valid gift card
				Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByPanPin($code, $pin)
					->addToCart();
				Mage::getSingleton('checkout/session')->addSuccess(
					$this->__('Gift Card "%s" was added.', Mage::helper('core')->escapeHtml($code))
				);
			} catch (Mage_Core_Exception $e) {
				Mage::dispatchEvent('enterprise_giftcardaccount_add', array('status' => 'fail', 'code' => $code));
				Mage::getSingleton('checkout/session')->addError(
					$e->getMessage()
				);
			} catch (Exception $e) {
				Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot apply gift card.'));
			}
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * Overriding gift card quick check
	 * Check a gift card account availability
	 *
	 */
	public function quickCheckAction()
	{
		$card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByPanPin(
			$this->getRequest()->getParam('giftcard_code', ''),
			$this->getRequest()->getParam('giftcard_pin', '')
		);
		Mage::register('current_giftcardaccount', $card);
		try {
			$card->isValid(true, true, true, false);
		} catch (Mage_Core_Exception $e) {
			$card->unsetData();
		}

		$this->loadLayout();
		$this->renderLayout();
	}
}

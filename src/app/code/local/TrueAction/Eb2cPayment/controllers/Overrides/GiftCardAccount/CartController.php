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
	 * No index action, forward to 404
	 *
	 */
	public function indexAction()
	{
		$this->_forward('noRoute');
	}

	/**
	 * Add Gift Card to current quote
	 *
	 */
	public function addAction()
	{
		echo '<br />This is not a test, it\'s difficulty';
		exit(-1);
		$data = $this->getRequest()->getPost();
		if (isset($data['giftcard_code']) && isset($data['giftcard_pin'])) {
			$code = $data['giftcard_code']; // interpreting code as eb2c pan
			$pin = $data['giftcard_pin']; // getting pin data from user input
			try {
				if (strlen($code) > Enterprise_GiftCardAccount_Helper_Data::GIFT_CARD_CODE_MAX_LENGTH) {
					Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card payment account numbers.'));
				}
				Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
					->loadByCode($code)
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

	public function removeAction()
	{
		if ($code = $this->getRequest()->getParam('code')) {
			try {
				Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
					->loadByCode($code)
					->removeFromCart();
				Mage::getSingleton('checkout/session')->addSuccess(
					$this->__('Gift Card "%s" was removed.', Mage::helper('core')->escapeHtml($code))
				);
			} catch (Mage_Core_Exception $e) {
				Mage::getSingleton('checkout/session')->addError(
					$e->getMessage()
				);
			} catch (Exception $e) {
				Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot remove gift card.'));
			}
			$this->_redirect('checkout/cart');
		} else {
			$this->_forward('noRoute');
		}
	}

	/**
	 * Check a gift card account availability
	 *
	 */
	public function checkAction()
	{
		return $this->quickCheckAction();
	}

	/**
	 * Check a gift card account availability
	 *
	 */
	public function quickCheckAction()
	{
		/* @var $card Enterprise_GiftCardAccount_Model_Giftcardaccount */
		$card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
			->loadByCode($this->getRequest()->getParam('giftcard_code', ''));
		Mage::register('current_giftcardaccount', $card);
		try {
			$card->isValid(true, true, true, false);
		}
		catch (Mage_Core_Exception $e) {
			$card->unsetData();
		}

		$this->loadLayout();
		$this->renderLayout();
	}
}

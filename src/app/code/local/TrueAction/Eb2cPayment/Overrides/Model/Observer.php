<?php
class TrueAction_Eb2cPayment_Overrides_Model_Observer extends Enterprise_GiftCardAccount_Model_Observer
{
	/**
	 * @see Enterprise_GiftCardAccount_Model_Observer::processOrderCreationData
	 * overriding the observer method in order to successfully add giftcard order created in the admin
	 * @return self
	 */
	public function processOrderCreationData(Varien_Event_Observer $observer)
	{
		$request = $observer->getEvent()->getRequest();
		$this->_processGiftcardAdd($observer, $request)
			->_processGiftcardRemove($observer, $request);
		return $this;
	}

	/**
	 * invoked the parent method the Enterprise_GiftCardAccount_Model_Observer::processOrderCreationData when
	 * the request key 'giftcard_remove' exist
	 * @param Varien_Event_Observer $observer
	 * @param array $request array contain 'giftcard_remove' key
	 * @return self
	 */
	protected function _processGiftcardRemove(Varien_Event_Observer $observer, array $request)
	{
		if (isset($request['giftcard_remove'])) {
			parent::processOrderCreationData($observer);
		}

		return $this;
	}

	/**
	 * processing giftcard add to cart action
	 * @param Varien_Event_Observer $observer
	 * @param array $request array contain 'giftcard_remove' key
	 * @return self
	 */
	protected function _processGiftcardAdd(Varien_Event_Observer $observer, array $request)
	{
		if (isset($request['giftcard_add'])) {
			$quote = $observer->getEvent()->getOrderCreateModel()->getQuote();
			$code = $request['giftcard_add'];
			$pin = $request['giftcard_pin'];
			$websiteId = $this->_getApp()->getStore($quote->getStoreId())->getWebsite()->getId();

			$giftcardaccount = $this->_getModel('enterprise_giftcardaccount/giftcardaccount')
				->loadByPanPin($code, $pin)
				->setWebsiteId($websiteId);

			try {
				$giftcardaccount->addToCart(true, $quote);
			} catch (Mage_Core_Exception $e) {
				Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session_quote')->addException(
					$e, Mage::helper('enterprise_giftcardaccount')->__('Cannot apply Gift Card')
				);
			}
		}

		return $this;
	}

	/**
	 * @see Enterprise_GiftCardAccount_Model_Observer::paymentDataImport
	 * overriding this method to ensure the proper website id is set in the gifcardaccount model
	 * @return self
	 */
	public function paymentDataImport(Varien_Event_Observer $observer)
	{
		// Varien_Event
		$event = $observer->getEvent();

		// Mage_Sales_Model_Quote
		$quote = $event->getPayment()->getQuote();

		if ($quote && $quote->getCustomerId()) {
			// Gift cards validation
			$this->_validateGiftcardsInQuote($quote)
				->_applyGiftCardDataToQuote($quote, $event);
		}

		return $this;
	}

	/**
	 * given a quote object loop through and validate all giftcard that's been added to it
	 * @param Mage_Sales_Model_Quote $quote
	 * @return self
	 */
	protected function _validateGiftcardsInQuote(Mage_Sales_Model_Quote $quote)
	{
		$cards = $this->_getHelper('enterprise_giftcardaccount')->getCards($quote);
		$website = $this->_getApp()->getStore($quote->getStoreId())->getWebsite();
		$websiteId = $website->getId();
		foreach ($cards as $one) {
			$this->_getModel('enterprise_giftcardaccount/giftcardaccount')
				->loadByPanPin($one['pan'], $one['pin'])
				->setWebsiteId($websiteId)
				->isValid(true, true, $website);
		}

		return $this;
	}

	/**
	 * given a quote applied giftcard data
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Varien_Event $event
	 * @return self
	 */
	protected function _applyGiftCardDataToQuote(Mage_Sales_Model_Quote $quote, Varien_Event $event)
	{
		if ((float) $quote->getBaseGiftCardsAmountUsed()) {
			$quote->setGiftCardAccountApplied(true);
			$input = $event->getInput();
			if (!$input->getMethod()) {
				$input->setMethod('free');
			}
		}

		return $this;
	}
}

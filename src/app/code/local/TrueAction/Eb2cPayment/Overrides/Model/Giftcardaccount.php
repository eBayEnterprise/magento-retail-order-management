<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount extends Enterprise_GiftCardAccount_Model_Giftcardaccount
{
	/**
	 * Giftcard pan that was requested for load
	 *
	 * @var bool|string
	 */
	protected $_requestedPan = false;

	/**
	 * Giftcard pin that was requested for load
	 *
	 * @var bool|string
	 */
	protected $_requestedPin = false;

	/**
	 * eb2c stored value balance object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Stored_Value_Balance
	 */
	protected $_storedValueBalance;

	protected function _getStoredValueBalance()
	{
		if (!$this->_storedValueBalance) {
	 		$this->_storedValueBalance = Mage::getModel('eb2cpayment/stored_value_balance');
		}

		return $this->_storedValueBalance;
	}

	/**
	 * filter gift card by by pan and pin
	 *
	 * @return TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount
	 */
	protected function _filterGiftCardByPanPin()
	{
		$giftcardaccount = Mage::getResourceModel('enterprise_giftcardaccount/giftcardaccount_collection');
		$giftcardaccount->getSelect()->where('main_table.eb2c_pan = ?', $this->_requestedPan) // add filter by pan.
			->where('main_table.eb2c_pin = ?', $this->_requestedPin); // add filter by pin

		return $giftcardaccount->load();
	}

	/**
	 * get pin by pan from giftcardaccount
	 *
	 * @param string $pan, the payment account number from eb2c that's stored in magento
	 *
	 * @return string $pin
	 */
	public function giftCardPinByPan($pan)
	{
		$pin = '';
		$giftcardaccount = Mage::getResourceModel('enterprise_giftcardaccount/giftcardaccount_collection');
		$giftcardaccount->getSelect()->where('main_table.eb2c_pan = ?', $pan); // add filter by pan.
		$giftcardaccount->load();
		if (count($giftcardaccount)) {
			$card = $giftcardaccount->getFirstItem();
			$pin = $card->getEb2cPin();
		}

		return $pin;
	}

	/**
	 * overriding the loadByCode method to update magento gift card with actual eb2c records
	 * Load gift card account model using specified code
	 *
	 * @param string $code
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function loadByCode($code)
	{
		return $this->loadByPanPin($code, $this->giftCardPinByPan($code));
	}

	/**
	 * custom Load gift card account model using specified eb2c stored value data
	 *
	 * @param string $pan, payment account number
	 * @param string $pin, personal identification number
	 *
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function loadByPanPin($pan, $pin)
	{
		$this->_requestedCode = $pan;
		$this->_requestedPan = $pan;
		$this->_requestedPin = $pin;

		// Check eb2c stored value first
		if (trim($pan) !== '' && trim($pin) !== '') {
			// only fetch eb2c stored value balance when both pan and pin is valid
			if ($storeValueBalanceReply = $this->_getStoredValueBalance()->getBalance($pan, $pin)) {
				if ($balanceData = $this->_getStoredValueBalance()->parseResponse($storeValueBalanceReply)) {
					$balanceData['pin'] = $pin;

					// making sure we have the right data
					if (isset($balanceData['paymentAccountUniqueId']) && $balanceData['paymentAccountUniqueId'] === $pan) {
						// We have the right gift card info from eb2c let's add it to to magento enteprise giftcard account
						// Let's first check if pan and pin already exists in magento enterprise gift card
						$mgGiftCard = $this->_filterGiftCardByPanPin();
						if (count($mgGiftCard)) {
							$this->_updateGiftCardWithEb2cData($mgGiftCard->getFirstItem(), $balanceData);
						} else {
							$this->_addGiftCardWithEb2cData($balanceData);
						}
					}
				}
			}
		}

		return $this->load($this->_requestedCode, 'code');
	}

	/**
	 * Update enterprise giftcard account with data from eb2c
	 *
	 * @param TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount $giftCard, the gift card object
	 * @param array $balanceData, the eb2c stored value balance data
	 *
	 * @return void
	 */
	protected function _updateGiftCardWithEb2cData(Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard, array $balanceData)
	{
		if ($giftCard->getGiftcardaccountId()) {
			$giftCard->setCode($balanceData['paymentAccountUniqueId'])
				->setEb2cPan($balanceData['paymentAccountUniqueId'])
				->setEb2cPin($balanceData['pin'])
				->setStatus(1)
				->setState(1)
				->setBalance((float) $balanceData['balanceAmount'])
				->setIsRedeemable(1)
				->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
				->setDateExpries(null)
				->save();
		}
	}

	/**
	 * add eb2c storedvalue gift card data to magento enterprise giftcard account
	 *
	 * @param array $balanceData, the eb2c stored value balance data
	 *
	 * @return void
	 */
	protected function _addGiftCardWithEb2cData(array $balanceData)
	{
		$giftCard = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->load(null);;
		$giftCard->setGiftcardaccountId(null)
			->setCode($balanceData['paymentAccountUniqueId'])
			->setEb2cPan($balanceData['paymentAccountUniqueId'])
			->setEb2cPin($balanceData['pin'])
			->setStatus(1)
			->setState(1)
			->setBalance((float) $balanceData['balanceAmount'])
			->setIsRedeemable(1)
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->setDateExpries(null)
			->setDateCreated(Mage::getModel('core/date')->date('Y-m-d'))
			->save();
	}

	/**
	 * overrriding addToCart method in order to save the eb2c pan and pin field in the quote
	 * Add gift card to quote gift card storage
	 *
	 * @param bool $saveQuote
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function addToCart($saveQuote = true, $quote = null)
	{
		if (is_null($quote)) {
			$quote = $this->_getCheckoutSession()->getQuote();
		}
		$website = Mage::app()->getStore($quote->getStoreId())->getWebsite();
		if ($this->isValid(true, true, $website)) {
			$cards = Mage::helper('enterprise_giftcardaccount')->getCards($quote);
			if (!$cards) {
				$cards = array();
			} else {
				foreach ($cards as $one) {
					if ($one['i'] == $this->getId()) {
						Mage::throwException(
							Mage::helper('enterprise_giftcardaccount')->__('This gift card account is already in the quote.')
						);
					}
				}
			}
			$cards[] = array(
				// id
				'i' => $this->getId(),
				// code
				'c' => $this->getCode(),
				// amount
				'a' => $this->getBalance(),
				// base amount
				'ba' => $this->getBalance(),
				// eb2c pan
				'pan' => $this->getEb2cPan(),
				// eb2c pin
				'pin' => $this->getEb2cPin(),
			);
			Mage::helper('enterprise_giftcardaccount')->setCards($quote, $cards);

			if ($saveQuote) {
				$quote->save();
			}
		}

		return $this;
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Model_StoredValue extends Mage_Payment_Model_Method_Abstract
{
	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'eb2cstoredvalue';

	/**
	 * payment form block
	 *
	 * @var string MODULE/BLOCKNAME
	 */
	protected $_formBlockType = 'eb2cstoredvalue/form';

	/**
	 * payment info block
	 *
	 * @var string MODULE/BLOCKNAME
	 */
	protected $_infoBlockType = 'eb2cstoredvalue/info';

	/**
	 * @var bool Allow capturing for this payment method
	 */
	protected $_canCapture = true;

	/**
	 * Assigns data to the payment info instance
	 *
	 * @param  Varien_Object|array $data Payment Data from checkout
	 * @return TrueAction_Eb2cStoredValue_Model_StoredValue Self.
	 */
	public function assignData($data)
	{
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}

		$info = $this->getInfoInstance();

		// Fetch the account Pan
		$pan = $data->getStoredvaluePan();
		if ($pan) {
			$pan = $info->encrypt($pan);
		}

		// Fetch the account Pin
		$pin = $data->getStoredvaluePin();
		if ($pin) {
			$pin = $info->encrypt($pin);
		}

		// Fetch the account action
		$action = $data->getStoredvalueAction();
		if ($action) {
			$action = trim($action);
		}

		if (strtoupper(trim($action)) === 'CHECK BALANCE') {
			// TODO: check eb2c for gift card balance

			// Throwing exception to prevent one page checkout from going to the next page.
			Mage::throwException('Your gift card balance is $600.00');
		} else {
			// This is redeem action

			// TODO: check if gift card has enought balance to cover the order todo

			// if not then throw exception to prevent from going to payment review.

			// else, then allow payment

		}

		Mage::log('action = ' . $action . "\n\rClass = " . get_class($info), Zend_Log::ERR);

		// Set account data in payment info model
		$info->setStoredvaluePan($pan)
			 ->setStoredvaluePin($pin)
			 ->setStoredvalueAction($action);

		return $this;
	}

	/**
	 * Returns the account pan code from the payment info instance
	 *
	 * @return string pan
	 */
	public function getAccountPan()
	{
		$info = $this->getInfoInstance();
		$data = $info->decrypt($info->getStoredvaluePan());

		return $data;
	}

	/**
	 * Returns the account pin from the payment info instance
	 *
	 * @return string pin
	 */
	public function getAccountPin()
	{
		$info = $this->getInfoInstance();
		$data = $info->decrypt($info->getStoredvaluePin());

		return $data;
	}

	/**
	 * Returns the account action code from the payment info instance
	 *
	 * @return string action
	 */
	public function getAccountAction()
	{
		$info = $this->getInfoInstance();
		$data = trim($info->getStoredvalueAction());

		return $data;
	}

	/**
	 * Returns the encrypted data for mail
	 *
	 * @param  string $data Data to crypt
	 * @return string Crypted data
	 */
	public function maskString($data)
	{
		$crypt = str_repeat('*', strlen($data)-3) . substr($data, -3);

		return $crypt;
	}
}

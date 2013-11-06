<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Suppression
	extends Varien_Object
{
	/**
	 * Payment methods that should be enabled when eb2c payments is enabled
	 * @var array
	 */
	protected $_eb2cPaymentMethods = array(
		'pbridge',
		'pbridge_eb2cpayment_cc',
	);

	/**
	 * Payment methods that are allowed to be active while eb2c payments is enabled
	 * @var array
	 */
	protected $_whitelistPaymentMethods = array(
		'paypal_express',
		'free',
	);

	/**
	 * All payment methods allowed while Eb2c Payments is enabled - a merge of
	 * eb2cPaymentMethods and whitelistPaymentMethods
	 * @var array
	 */
	protected $_allowedPaymentMethods = array();

	/**
	 * Core config model used to update config values
	 * @var Mage_Core_Model_Config
	 */
	protected $_configModel;

	/**
	 * Setup the config model and allowed payment methods
	 */
	protected function _construct()
	{
		$this->_configModel = Mage::getConfig();
		$this->_allowedPaymentMethods = array_merge(
			$this->_whitelistPaymentMethods,
			$this->_eb2cPaymentMethods
		);
	}

	/**
	 * Get the relevent store id if there is one, otherwise null for the default/active store
	 * @return int|null Store id or null
	 */
	protected function _getStoreId()
	{
		return $this->getStore() ? $this->getStore()->getId() : null;
	}

	/**
	 * updating eBay Enterprise payment methods, to enabled or disabled base on the pass value
	 * @param int $value, 0 to turn payment off, 1 to turn payment on.
	 * @return self
	 */
	public function saveEb2CPaymentMethods($value)
	{
		$config = Mage::app()->getStore($this->_getStoreId())->getConfig('payment');
		// when enabled, should enable all allowed payment methods
		// when disabled, should only disable methods exclusive to eb2c payments
		foreach ($this->_eb2cPaymentMethods as $method) {
			// @todo we need a better way of determining and setting the scope and scope id
			$this->_configModel->saveConfig('payment/' . $method . '/active', $value, 'default', 0);
		}
		// when enabling eb2c payments, free payments need to be enabled...this is a bit
		// hackish but should be ok
		if ($value === '1') {
			$this->_configModel->saveConfig('payment/free/active', $value, 'default', 0);
		}
		$this->_configModel->reinit();
		return $this;
	}

	/**
	 * disabled non-eBay Enterprise payment methods
	 * @return self
	 */
	public function disableNonEb2CPaymentMethods()
	{
		foreach ($this->getActivePaymentMethods() as $method => $methodConfig) {
			if (!$this->isMethodAllowed($method)) {
				$this->_configModel->saveConfig('payment/' . $method . '/active', 0, 'default', 0);
			}
		}
		$this->_configModel->reinit();
		return $this;
	}

	/**
	 * check eb2c pbrige payment method required setting, return false if anyone of these required
	 * settings are not valid, otherwise true
	 * @return bool, true for all valid configuration otherwise false;
	 */
	public function isEbcPaymentConfigured()
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->setStore($this->_getStoreId())
			->addConfigModel(Mage::getSingleton('eb2cpayment/method_config'));

		return (bool) $cfg->pbridgeActive &&
			(trim($cfg->pbridgeMerchantCode) !== '') &&
			(trim($cfg->pbridgeMerchantKey) !== '') &&
			(trim($cfg->pbridgeGatewayUrl) !== '') &&
			(trim($cfg->pbridgeTransferKey) !== '') &&
			(bool) $cfg->ebcPbridgeActive &&
			(trim($cfg->ebcPbridgeTitle) !== '');
	}

	/**
	 * check if any non-eb2c payment method enabled
	 * @return boolean true if any non-allowed payment method is enabled
	 */
	public function isAnyNonEb2CPaymentMethodEnabled()
	{
		Mage::log($this->getActivePaymentMethods());
		foreach ($this->getActivePaymentMethods() as $method => $methodConfig) {
			if (!$this->isMethodAllowed($method)) {
				Mage::log($method);
				return true;
			}
		}
		return false;
	}

	/**
	 * Get only payment methods that are currently active.
	 * @return array Maps of config values for active payment methods
	 */
	public function getActivePaymentMethods()
	{
		return array_filter(
			$this->getPaymentMethods(),
			function ($e) { return $e['active'] === '1'; }
		);
	}

	/**
	 * Get all payment methods
	 * @return array Maps of config values for all payment methods
	 */
	public function getPaymentMethods()
	{
		return array_filter(
			Mage::app()->getStore($this->_getStoreId())->getConfig('payment'),
			function ($e) { return isset($e['active']); }
		);
	}

	/**
	 * Is the payment method allowed while eb2c payments are enabled.
	 * @param  string  $paymentMethodName name of the payment method in config
	 * @return boolean                    true if allowed, false if not
	 */
	public function isMethodAllowed($paymentMethodName)
	{
		return in_array($paymentMethodName, $this->_allowedPaymentMethods);
	}

}

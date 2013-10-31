<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Suppression
{
	/**
	 * @var array, hold list of eb2c specific payment methods
	 */
	private $_ebcPaymentMthd = array();

	/**
	 * Initialize payment methods settings, etc
	 * @return self
	 */
	public function __construct()
	{
		$this->_ebcPaymentMthd = array(
			'pbridge',
			'pbridge_eb2cpayment_cc'
		);
		return $this;
	}

	/**
	 * query all payment methods from config
	 * @return Mage_Core_Model_Resource_Config_Data_Collection
	 */
	public function queryConfigPayment()
	{
		$config = Mage::getResourceModel('core/config_data_collection');
		$config->getSelect()
			->where("main_table.path LIKE '%payment%' AND main_table.path LIKE '%active%'");
		return $config->load();
	}

	/**
	 * updating eBay Enterprise payment methods, to enabled or disabled base on the pass value
	 * @param int $value, 0 to turn payment off, 1 to turn payment on.
	 * @return self
	 */
	public function saveEb2CPaymentMethods($value)
	{
		$config = $this->queryConfigPayment();
		foreach ($this->_ebcPaymentMthd as $mthd) {
			foreach ($config as $cfg) {
				$cfgData = explode('/', $cfg->getPath());
				if (in_array($mthd, explode('/', $cfg->getPath())) && (int) $cfg->getValue() !== $value) {
					$cfg->setValue($value)->save();
				}
			}
		}

		// reload config
		Mage::getConfig()->reinit();

		return $this;
	}

	/**
	 * disabled none eBay Enterprise payment methods
	 * @return self
	 */
	public function disableNoneEb2CPaymentMethods()
	{
		$config = $this->queryConfigPayment();
		foreach ($this->_ebcPaymentMthd as $mthd) {
			foreach ($config as $cfg) {
				$cfgData = explode('/', $cfg->getPath());
				if (!in_array($mthd, explode('/', $cfg->getPath())) && (int) $cfg->getValue() === 1) {
					$cfg->setValue(0)->save();
				}
			}
		}

		// reload config
		Mage::getConfig()->reinit();

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
	 * check if any none eb2c payment method enabled
	 * @return self
	 */
	public function isAnyNoneEb2CPaymentMethodEnabled()
	{
		$config = $this->queryConfigPayment();
		foreach ($this->_ebcPaymentMthd as $mthd) {
			foreach ($config as $cfg) {
				$cfgData = explode('/', $cfg->getPath());
				if (!in_array($mthd, explode('/', $cfg->getPath())) && (int) $cfg->getValue() === 1) {
					return true;
				}
			}
		}

		return false;
	}
}

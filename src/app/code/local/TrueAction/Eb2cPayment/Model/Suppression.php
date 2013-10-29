<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Suppression
{
	const PAYMENT_NEED_CONFIGURATION_TITLE = 'TrueAction_Eb2cPayment_Admin_Dasboard_Payment_Config_Title';
	const PAYMENT_NEED_CONFIGURATION_DESCRIPTION = 'TrueAction_Eb2cPayment_Admin_Dasboard_Payment_Config_Description';
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
	 * delete a notification, by querying the notification_inbox table by title and removing any record found
	 * @return $this;
	 */
	public function removeNotification($title='')
	{
		if (trim($title) !== '') {
			$inbox = Mage::getResourceModel('adminnotification/inbox_collection');
			$inbox->getSelect()
				->where(sprintf(
					"TRIM(UPPER(main_table.title)) = '%s' AND main_table.severity = '%d'",
					$title, Mage_AdminNotification_Model_Inbox::SEVERITY_CRITICAL
				));
			$inbox->load();

			if ($inbox->count()) {
				// we records let's delete them
				foreach ($inbox as $ibx) {
					$ibx->delete();
				}
			}
		}

		return $this;
	}

	/**
	 * add configuration notification message to admin dasboard/
	 * @return $this;
	 */
	public function addConfigurationNotification()
	{
		Mage::getModel('adminnotification/inbox')->addCritical(
			Mage::helper('eb2cpayment')->__(self::PAYMENT_NEED_CONFIGURATION_TITLE),
			Mage::helper('eb2cpayment')->__(self::PAYMENT_NEED_CONFIGURATION_DESCRIPTION),
			Mage::getModel('adminhtml/url')->getUrl('adminhtml/system_config/edit', array('section'=>'payment')),
			false
		);

		return $this;
	}
}

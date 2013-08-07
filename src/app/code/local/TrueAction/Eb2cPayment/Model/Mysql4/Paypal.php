<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Mysql4_Paypal extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal', 'paypal_id');
	}

	/**
	 * Load paypal by quote_id
	 *
	 * @throws Mage_Core_Exception
	 *
	 * @param TrueAction_Eb2cPayment_Model_Paypal $paypal
	 * @param int $quoteId
	 * @param bool $testOnly
	 * @return TrueAction_Eb2cPayment_Model_Mysql4_Paypal
	 */
	public function loadByQuoteId(TrueAction_Eb2cPayment_Model_Paypal $paypal, $quoteId, $testOnly=false)
	{
		$adapter = $this->_getReadAdapter();
		$select  = $adapter->select()
			->from($this->getMainTable(), array('paypal_id'))
			->where("quote_id = '" . (int) $quoteId . "'");

		$paypalId = $adapter->fetchOne($select);
		if ($paypalId) {
			$this->load($paypal, $paypalId);
		} else {
			$paypal->setData(array());
		}

		return $this;
	}
}

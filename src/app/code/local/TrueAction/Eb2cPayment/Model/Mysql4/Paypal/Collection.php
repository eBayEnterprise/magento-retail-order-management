<?php
class TrueAction_Eb2cPayment_Model_Mysql4_Paypal_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal');
	}
}

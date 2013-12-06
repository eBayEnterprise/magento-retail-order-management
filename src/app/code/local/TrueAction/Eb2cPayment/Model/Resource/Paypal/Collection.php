<?php
class TrueAction_Eb2cPayment_Model_Resource_Paypal_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal');
	}
}

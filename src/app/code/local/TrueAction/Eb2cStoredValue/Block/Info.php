<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Block_Info extends Mage_Payment_Block_Info
{
	/**
	 * Construct payment info block and set template
	 *
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('eb2cstoredvalue/info.phtml');
	}

	/**
	 * Returns email data and mask the data if necessary
	 *
	 * @return array Bank data
	 */
	public function getEmailData()
	{
		$storedvalueType = '';

		/* @var $payment TrueAction_Eb2cStoredValue_Model_StoredValue */
		$payment = $this->getMethod();
		$method  = $this->getMethod()->getCode();
		$data = array(
			'account_pan' => $payment->getAccountPan(),
			'account_pin' => $payment->getAccountPin(),
			'account_action' => $payment->getAccountAction(),
		);

		return $data;
	}
}

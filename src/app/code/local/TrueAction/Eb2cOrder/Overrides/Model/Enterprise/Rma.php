<?php
class TrueAction_Eb2cOrder_Overrides_Model_Enterprise_Rma
	extends Enterprise_Rma_Model_Rma
{
	/**
	 * Sending email with RMA data
	 *
	 * @return Enterprise_Rma_Model_Rma
	 */
	public function sendNewRmaEmail()
	{
		/** @var $configRmaEmail Enterprise_Rma_Model_Config */
		$configRmaEmail = Mage::getSingleton('enterprise_rma/config');
		return $this->_sendRmaEmailWithItems($configRmaEmail->getRootRmaEmail());
	}

	/**
	 * Sending authorizing email with RMA data
	 *
	 * @return Enterprise_Rma_Model_Rma
	 */
	public function sendAuthorizeEmail()
	{
		if (!$this->getIsSendAuthEmail()) {
			return $this;
		}
		/** @var $configRmaEmail Enterprise_Rma_Model_Config */
		$configRmaEmail = Mage::getSingleton('enterprise_rma/config');
		return $this->_sendRmaEmailWithItems($configRmaEmail->getRootAuthEmail());
	}
}

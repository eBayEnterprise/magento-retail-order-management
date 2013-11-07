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
		if (Mage::helper('eb2corder')->getConfig()->transactionalEmailer === 'eb2c') {
			Mage::log('Suppressing new RMA email');
			return $this;
		} else {
			return parent::sendNewRmaEmail();
		}
	}

	/**
	 * Sending authorizing email with RMA data
	 *
	 * @return Enterprise_Rma_Model_Rma
	 */
	public function sendAuthorizeEmail()
	{
		if (Mage::helper('eb2corder')->getConfig()->transactionalEmailer === 'eb2c') {
			Mage::log('Suppressing RMA authorization email');
			return $this;
		} else {
			return parent::sendAuthorizeEmail();
		}
	}
}

<?php
class TrueAction_Eb2cPayment_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
	/**
	 * Get array of payment methods that need to be configured
	 * @return array
	 */
	public function getEbcPaymentNotice()
	{
		Mage::log(sprintf("[%s::%s]", __CLASS__, __METHOD__), Zend_Log::DEBUG);
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			if (Mage::getModel('eb2cpayment/suppression')->isEbcPaymentConfigured()) {
				return array('Payment Bridge and eBay Enterprise Credit Card');
			}
		} else {
			return array('This store currently have no payment methods');
		}

		return array();
	}

	/**
	 * Get payment configuration section url
	 * @return string
	 */
	public function getPaymentSectionConfigurationUrl()
	{
		Mage::log(sprintf("[%s::%s]", __CLASS__, __METHOD__), Zend_Log::DEBUG);
		return $this->getUrl('adminhtml/system_config/edit', array('section'=>'payment'));
	}

	/**
	 * ACL validation before html generation
	 * @return string
	 */
	protected function _toHtml()
	{
		Mage::log(sprintf("[%s::%s]", __CLASS__, __METHOD__), Zend_Log::DEBUG);
		if (Mage::getSingleton('admin/session')->isAllowed('adminhtml/system_config')) {
			return parent::_toHtml();
		}
		return '';
	}
}

<?php
class TrueAction_Eb2cPayment_Model_Adminhtml_Comment
{
	const COMMENT_VERBIAGE = 'TrueAction_Eb2cPayment_Admin_System_Config_PBridge_Comments';

	/**
	 * return the comment content
	 * @return string
	 */
	public function getCommentText()
	{
		return sprintf(Mage::helper('eb2cpayment')->__(self::COMMENT_VERBIAGE), $this->_getUrl());
	}

	/**
	 * return the payment method configuration section URL
	 * @return string
	 */
	protected function _getUrl()
	{
		return Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit', array('section' => 'payment'));
	}
}

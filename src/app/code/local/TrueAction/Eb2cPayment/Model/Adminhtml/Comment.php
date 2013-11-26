<?php
class TrueAction_Eb2cPayment_Model_Adminhtml_Comment
{
	/**
	 * return the comment content
	 * @return string
	 */
	public function getCommentText()
	{
		return sprintf('click here to <a href="%s">Configure Payment Bridge</a>', $this->_getUrl());
	}

	/**
	 * return the paymenthod configuration section rul
	 * @return string
	 */
	protected function _getUrl()
	{
		return Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit', array('section' => 'payment'));
	}
}

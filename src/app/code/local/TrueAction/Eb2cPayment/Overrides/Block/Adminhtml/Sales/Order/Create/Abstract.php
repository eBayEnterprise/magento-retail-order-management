<?php
class TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_Sales_Order_Create_Abstract
	extends Enterprise_Pbridge_Block_Adminhtml_Sales_Order_Create_Abstract
{
	/**
	 * @see Enterprise_Pbridge_Block_Adminhtml_Sales_Order_Create_Abstract::is3dSecureEnabled
	 * in order to resolved issue in pbridge/cc model class where creating order in admin
	 * is calling a non-existing method in the pbridge class
	 * @return bool false because we hardcoded to always return false
	 * @codeCoverageIgnore
	 */
	public function is3dSecureEnabled()
	{
		return false;
	}
}

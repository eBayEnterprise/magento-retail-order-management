<?php
class TrueAction_Eb2cOrder_Model_System_Config_Source_Emailer
{

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Exchange Platform')),
			array('value' => 0, 'label' => Mage::helper('adminhtml')->__('Magento')),
		);
	}

	/**
	 * Get options in "key-value" format
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			0 => Mage::helper('adminhtml')->__('Exchange Platform'),
			1 => Mage::helper('adminhtml')->__('Magento'),
		);
	}

}

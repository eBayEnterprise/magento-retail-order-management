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
			array('value' => 'eb2c', 'label' => Mage::helper('adminhtml')->__('Exchange Platform')),
			array('value' => 'mage', 'label' => Mage::helper('adminhtml')->__('Magento')),
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
			'eb2c' => Mage::helper('adminhtml')->__('Exchange Platform'),
			'mage' => Mage::helper('adminhtml')->__('Magento'),
		);
	}

}

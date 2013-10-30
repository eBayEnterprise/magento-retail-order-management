<?php
class TrueAction_Eb2cAddress_Block_Address_Renderer
	extends Mage_Customer_Block_Address_Renderer_Default
{
	/**
	 * @param string $format - the template used to format the address
	 */
	public function initType($format)
	{
		$type = new Varien_Object();
		$type->setCode('address_verification')
			->setTitle('Address Verification Suggestion')
			->setDefaultFormat($format)
			->setHtmlEscape(true);
		$this->setType($type);
		return $this;
	}
}

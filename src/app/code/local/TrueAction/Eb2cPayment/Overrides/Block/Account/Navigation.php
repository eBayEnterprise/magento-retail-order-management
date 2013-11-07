<?php
class TrueAction_Eb2cPayment_Overrides_Block_Account_Navigation extends Mage_Customer_Block_Account_Navigation
{
	/**
	 * adding method to remove links from customer account navigation section
	 *
	 * @param string $name, the name of the module link
	 *
	 * @return void,
	 */
	public function removeLinkByName($name)
	{
		unset($this->_links[$name]);
	}
}

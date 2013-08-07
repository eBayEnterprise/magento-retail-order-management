<?php
class TrueAction_Eb2cFraud_Block_Jsc extends Mage_Core_Block_Abstract implements Mage_Widget_Block_Interface
{

	/**
	* Injects appropriate randomized JavaScript for 41st Parameter
	*
	* @return string
	*/
	protected function _toHtml()
	{
		parent::_toHtml();
		$javaScriptTag = Mage::getModel('eb2cfraud/jsc')->getJscHtml();
		return $javaScriptTag;
	}

	/**
	 * I do *not* want this particular block cached. Setting getCacheLifetime to null is how
	 *	you tell Magento "don't save this to cache."
	 *
	 */
	public function getCacheLifetime() 
	{
		return null;
	}
}

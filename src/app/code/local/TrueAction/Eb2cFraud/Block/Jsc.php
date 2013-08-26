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
		return Mage::getModel('eb2cfraud/jsc')->getJscHtml();
	}

	/**
	 * Do not cache this block.
	 * Setting getCacheLifetime to null is how
	 * you tell Magento "don't save this to cache."
	 *
	 * @todo Can we do this in config like app/code/core/Enterprise/PageCache/etc/cache.xml?
	 * @todo Or should it be done with a setCacheLifetime(0) in the constructor?
	 */
	public function getCacheLifetime()
	{
		return null;
	}
}

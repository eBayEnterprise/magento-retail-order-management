<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Model_Orders extends Mage_Core_Model_Abstract
{
	/**
	 * (non-PHPdoc)
	 * @see Varien_Object::_construct()
	 */
	protected function _construct()
	{
		$this->_init('eb2cstoredvalue/orders');
	}
}

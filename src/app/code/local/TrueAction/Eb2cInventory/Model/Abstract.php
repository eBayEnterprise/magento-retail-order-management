<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Abstract extends Varien_Object
{
	/**
	 * check if quote item has manage stock enabled.
	 *
	 * @param Mage_Sales_Model_Quote_Item $item, the item to check if manage stock is enabled
	 */
	public function filterInventoriedItems($item)
	{
		return (!$item->getProduct()->getStockItem()->getManageStock() || $item->getIsVirtual())? false : true;
	}
}

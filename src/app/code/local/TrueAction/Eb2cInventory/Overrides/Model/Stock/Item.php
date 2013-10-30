<?php
class TrueAction_Eb2cInventory_Overrides_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
	/**
	 * Overriding Check if is possible subtract value from item qty
	 *
	 * @return bool
	 */
	public function canSubtractQty()
	{
		// disabling product stock decreasing
		return false;
	}
}

<?php

class TrueAction_Eb2cProduct_Model_Resource_Feed_Product_Collection
	extends Mage_Catalog_Model_Resource_Product_Collection
{
	/**
	 * Substitute the product sku for entity_id as all products processed from the
	 * feeds will have a sku. This makes looking up a product by SKU more
	 * reasonable and allows for newly created items to be looked up after being
	 * added to the collection but before the collection has been saved.
	 * @param  Varien_Object $item
	 * @return string
	 */
	protected function _getItemId(Varien_Object $item)
	{
		return $item->getSku();
	}
}

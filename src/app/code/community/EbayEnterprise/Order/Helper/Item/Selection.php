<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Order_Helper_Item_Selection
{
	/**
	 * Select all items from the given array of items that should be included
	 * in the order create request.
	 *
	 * Currently only needs to filter out child configurable items - parent
	 * configurable item has all of the expected item data for inclusion in
	 * the OCR. All other item types should be included in the OCR: always
	 * include simple/non-composite items; grouped items do not produce an extra
	 * item in the order, one of the non-composite items added just gets
	 * "flagged" as the grouped item which should still be included; all items
	 * in a bundle should be included.
	 *
	 * @param Mage_Sales_Model_Order_Item[]
	 * @return Mage_Sales_Model_Order_Item[]
	 */
	public function selectFrom(array $items)
	{
		return array_filter($items, [$this, 'isItemIncludedInOrderCreate']);
	}

	/**
	 * Filter for selecting only items to be included in an order create request.
	 *
	 * @param Mage_Sales_Model_Order_Item
	 * @return bool
	 */
	public function isItemIncludedInOrderCreate(Mage_Sales_Model_Order_Item $item)
	{
		return !$this->_itemIsConfigurableChild($item);
	}

	/**
	 * Check if the item is the child of a configurable product.
	 *
	 * @param Mage_Sales_Model_Order_Item
	 * @return bool
	 */
	protected function _itemIsConfigurableChild(Mage_Sales_Model_Order_Item $item)
	{
		$parentItem = $item->getParentItem();
		return ($parentItem && $parentItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
	}
}

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

class EbayEnterprise_Inventory_Helper_Item_Selection implements EbayEnterprise_Inventory_Model_Item_Selection_Interface
{
    /**
     * Select items to be sent in the request from the given array
     * based on product type.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return Mage_Sales_Model_Quote_Item_Abstract[]
     */
    public function selectFrom(array $items)
    {
        return array_filter(
            $items,
            function ($item) {
                return !$this->isExcludedItem($item) && $this->isStockManaged($item);
            }
        );
    }

    /**
     * returns true if the item is the child of configurable/grouped product
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function isExcludedItem(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return $item->getParentItem() instanceof Mage_Sales_Model_Quote_Item;
    }

    /**
     * return true if the item's manage stock setting is on
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function isStockManaged(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $stock = $item->getProduct()->getStockItem();
        return (bool) $stock->getManageStock();
    }
}

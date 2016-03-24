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

class EbayEnterprise_Tax_Helper_Item_Selection
{
    /**
     * Filter out the children of configurable products.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return Mage_Sales_Model_Quote_Item_Abstract[]
     */
    public function selectFrom(array $items)
    {
        $predicate = function (Mage_Sales_Model_Quote_Item_Abstract $item) {
            $parentItem = $item->getParentItem();
            return !($parentItem && $parentItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
        }
        return array_filter($items, $predicate);
    }
}

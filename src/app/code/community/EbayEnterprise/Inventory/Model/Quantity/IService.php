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

interface EbayEnterprise_Inventory_Model_Quantity_IService
{
    /**
     * Check the inventory status of a quote item. Will add errors
     * to the quote and items if any are not currently available at the
     * requested quantity. Will throw an exception if any item not yet added
     * to the quote should be prevented from being added.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @return self
     * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception If any items should not be added to the quote.
     */
    public function checkQuoteItemInventory(Mage_Sales_Model_Quote_Item $item);

    /**
     * Check if a given item is currently available to be fulfilled.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function isItemAvailable(Mage_Sales_Model_Quote_Item_Abstract $item);
}

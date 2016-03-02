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

/**
 * Default unavailable item handling for products. Unavailable items will be:
 * - Prevented from being added if not already in the cart
 * - Marked as out of stock if item in cart is out of stock
 * - Marked as unavailable in requested quantity if already in the cart
 */
class EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_Default
    implements EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_IHandler
{
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $inventoryHelper;

    /**
     * $param array $args May contain:
     *                    - inventory_helper => EbayEnterprise_Inventory_Helper_Data
     */
    public function __construct(array $args = [])
    {
        list(
            $this->inventoryHelper
        ) = $this->_checkTypes(
            $this->_nullCoalesce($args, 'inventory_helper', Mage::helper('ebayenterprise_inventory'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Inventory_Helper_Data $inventoryHelper
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param string
     * @param array
     * @param mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Deal with the unavailable item. Mark the item as either out of stock
     * or unavailable in the requested quantity. If the item has not already
     * been added to cart, prevent it from being added.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @param int
     * @return self
     * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable If item should not be added to the quote
     */
    public function handleUnavailableItem(Mage_Sales_Model_Quote_Item $item, $quantityAvailable)
    {
        // Unavailable handler assumes that if an item is unavailable, it is
        // a manage stock and non-backorderable item (non-manage stock and
        // backorderable items will never be unavailable).
        $itemIsOutOfStock = $quantityAvailable === 0;
        // Items not already in the cart should not be added if unavailable.
        // Items without an id have not yet been saved and can not have been
        // added to the quote previously. Any item with an id, would have been
        // saved when initially added to the cart previously.
        if (!$item->getId()) {
            $message = $itemIsOutOfStock
                ? EbayEnterprise_Inventory_Model_Quantity_Service::OUT_OF_STOCK_EXCEPTION_MESSAGE
                : EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_EXCEPTION_MESSAGE;
            throw Mage::exception(
                'EbayEnterprise_Inventory_Exception_Quantity_Unavailable',
                $this->inventoryHelper->__($message, $item->getName(), $quantityAvailable)
            );
        }
        return $itemIsOutOfStock
            ? $this->_addOutOutOfStockErrorInfo($item)
            : $this->_addInsufficientStockErrorInfoForItem($item, $quantityAvailable);
    }

    /**
     * Add error info to an item when it is not available for purchase.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @return self
     */
    protected function _addOutOutOfStockErrorInfo(Mage_Sales_Model_Quote_Item $item)
    {
        return $this->_addErrorInfoForItem(
            $item,
            EbayEnterprise_Inventory_Model_Quantity_Service::OUT_OF_STOCK_ERROR_CODE,
            $this->inventoryHelper->__(EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_OUT_OF_STOCK_MESSAGE, $item->getName()),
            $this->inventoryHelper->__(EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_OUT_OF_STOCK_MESSAGE)
        );
    }

    /**
     * Add error info to an item when it is not available for purchase.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @param int
     * @return self
     */
    protected function _addInsufficientStockErrorInfoForItem(Mage_Sales_Model_Quote_Item $item, $quantityAvailable)
    {
        return $this->_addErrorInfoForItem(
            $item,
            EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE,
            $this->inventoryHelper->__(EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_INSUFFICIENT_STOCK_MESSAGE, $item->getName(), $quantityAvailable),
            $this->inventoryHelper->__(EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_INSUFFICIENT_STOCK_MESSAGE)
        );
    }

    /**
     * Add error info to the item and quote the item belongs to with the
     * provided error code and messages.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @param int
     * @param string
     * @param string
     * @return self
     */
    protected function _addErrorInfoForItem(
        Mage_Sales_Model_Quote_Item $item,
        $errorCode,
        $itemMessage,
        $quoteMessage
    ) {
        $item->addErrorInfo(
            EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE,
            $errorCode,
            $itemMessage
        );
        if ($item->getProduct()->isSuper()) {
            $children = $this->getItemChildren($item);
            foreach ($children as $child) {
                $child->addErrorInfo(
                    EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE,
                    $errorCode,
                    $itemMessage
                );
            }
        }
        $item->getQuote()->addErrorInfo(
            EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_TYPE,
            EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE,
            $errorCode,
            $quoteMessage
        );
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     * @return Mage_Sales_Model_Quote_Item[]
     */
    protected function getItemChildren(Mage_Sales_Model_Quote_Item $item)
    {
        return Mage::getModel('sales/quote_item')->getCollection()
            ->setQuote($item->getQuote())
            ->addFieldToFilter('parent_item_id', $item->getId());
    }
}

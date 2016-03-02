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

class EbayEnterprise_Inventory_Model_Quantity_Service implements EbayEnterprise_Inventory_Model_Quantity_IService
{
    const QUOTE_INSUFFICIENT_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Quote_Insufficient_Stock_Message';
    const ITEM_INSUFFICIENT_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Item_Insufficient_Stock_Message';
    const INSUFFICIENT_STOCK_EXCEPTION_MESSAGE = 'EbayEnterprise_Inventory_Insufficient_Stock_Exception_Message';
    const QUOTE_OUT_OF_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Quote_Out_Of_Stock_Message';
    const ITEM_OUT_OF_STOCK_MESSAGE = 'EbayEnterprise_Inventory_Item_Out_Of_Stock_Message';
    const OUT_OF_STOCK_EXCEPTION_MESSAGE = 'EbayEnterprise_Inventory_Out_Of_Stock_Exception_Message';
    const INSUFFICIENT_STOCK_ERROR_CODE = 1;
    const OUT_OF_STOCK_ERROR_CODE = 2;
    const ERROR_INFO_SOURCE = 'ebayenterprise_inventory';
    const ERROR_INFO_TYPE = 'inventory';

    /** @var EbayEnterprise_Inventory_Model_Quantity_Collector */
    protected $_quantityCollector;
    /** @var EbayEnterprise_Inventory_Helper_Item_Selection */
    protected $_inventoryItemSelection;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $_inventoryHelper;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $_quantityHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_logContext;

    /**
     * $param array $args May contain:
     *                    - quantity_collector => EbayEnterprise_Inventory_Model_Quantity_Collector
     *                    - inventory_item_selection => EbayEnterprise_Inventory_Helper_Item_Selection
     *                    - inventory_helper => EbayEnterprise_Inventory_Helper_Data
     *                    - quantity_helper => EbayEnterprise_Inventory_Helper_Quantity
     */
    public function __construct(array $args = [])
    {
        list(
            $this->_quantityCollector,
            $this->_inventoryItemSelection,
            $this->_inventoryHelper,
            $this->_quantityHelper,
            $this->_config,
            $this->_logger,
            $this->_logContext
        ) = $this->_checkTypes(
            $this->_nullCoalesce($args, 'quantity_collector', Mage::getModel('ebayenterprise_inventory/quantity_collector')),
            $this->_nullCoalesce($args, 'inventory_item_selection', Mage::helper('ebayenterprise_inventory/item_selection')),
            $this->_nullCoalesce($args, 'inventory_helper', Mage::helper('ebayenterprise_inventory')),
            $this->_nullCoalesce($args, 'quantity_helper', Mage::helper('ebayenterprise_inventory/quantity')),
            $this->_nullCoalesce($args, 'config', Mage::helper('ebayenterprise_inventory')->getConfigModel()),
            $this->_nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Model_Quantity_Collector
     * @param EbayEnterprise_Inventory_Helper_Item_Selection
     * @param EbayEnterprise_Inventory_Helper_Data
     * @param EbayEnterprise_Inventory_Helper_Quantity
     * @param EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Inventory_Model_Quantity_Collector $quantityCollector,
        EbayEnterprise_Inventory_Helper_Item_Selection $inventoryItemSelection,
        EbayEnterprise_Inventory_Helper_Data $inventoryHelper,
        EbayEnterprise_Inventory_Helper_Quantity $quantityHelper,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
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
     * Check if a given item is currently available to be fulfilled.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function isItemAvailable(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $availableQuantity = $this->_getAvailableQuantityForItem($item);
        return $this->isItemBackorderable($item, $availableQuantity) || $this->_calculateTotalQuantityRequested($item) <= $availableQuantity;
    }

    /**
     * Check if inventory detail can be sent.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function canSendInventoryDetail(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        // The session will have inventory details data stored only for managed stock items.
        // We need to make sure this item is managed stock before attempting to retrieve its
        // inventory details data from the session, or it will trigger a pointless call to ROM.
        return $this->_inventoryItemSelection->isStockManaged($item)
            && $this->isRequestedItemAvailable($item);
    }

    /**
     * Check if inventory allocation can be sent.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    public function canSendInventoryAllocation(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return $this->isRequestedItemAvailable($item);
    }

    /**
     * Check whether an item quantity requested in ROM is within the range of the available quantity ROM has in stock.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    protected function isRequestedItemAvailable(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        try {
            $availableQuantity = $this->_getAvailableQuantityForItem($item);
        } catch (EbayEnterprise_Inventory_Exception_Quantity_Collector_Exception $e) {
            // If inventory service sends an unusable response for quantity,
            // try again with details
            return true;
        }
        return $this->_calculateTotalQuantityRequested($item) <= $availableQuantity;
    }

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
    public function checkQuoteItemInventory(Mage_Sales_Model_Quote_Item $item)
    {
        // Only checking inventory that is managed and not a hidden parent item
        if (!$this->_inventoryItemSelection->isExcludedItem($item) &&
            $this->_inventoryItemSelection->isStockManaged($item)) {
            // Determine if a stock message needs to be displayed
            if (!$this->isItemAvailable($item)) {
                $this->_handleUnavailableItem($item);
            } else {
                $this->_notifyCustomerIfItemBackorderable($item);
            }
        }
        return $this;
    }

    /**
     * Get the quantity results for an item.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return EbayEnterprise_Inventory_Model_Quantity|null
     */
    protected function _getQuantityResultForItem(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return $this->_quantityCollector
            ->getQuantityResultsForQuote($item->getQuote())
            ->getQuantityBySku($this->_inventoryHelper->getRomSku($item->getSku()));
    }

    /**
     * Get the quantity available for the given item.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return int
     */
    protected function _getAvailableQuantityForItem(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $quantityResult = $this->_getQuantityResultForItem($item);
        return $quantityResult ? $quantityResult->getQuantity() : 0;
    }

    /**
     * Calculate the total quantity requested of a given item. All items in the
     * quote with the same SKU as the given item will be counted toward the
     * total quantity.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return int
     */
    protected function _calculateTotalQuantityRequested(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $inventoryItems = $this->_inventoryItemSelection->selectFrom($item->getQuote()->getAllItems());
        return $this->_quantityHelper->calculateTotalQuantityRequested($item, $inventoryItems);
    }

    /**
     * When an item is found to be currently unavailable, if it is already in
     * the customer's cart - saved to the quote - add error info to the quote
     * and item. If the item has not yet been added to the cart, throw an
     * exception to prevent it from being added.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @return self
     * @throws EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception
     */
    protected function _handleUnavailableItem(Mage_Sales_Model_Quote_Item $item)
    {
        $availableQuantity = $this->_getAvailableQuantityForItem($item);
        $this->_getUnavailableHandlerForItem($item)
            ->handleUnavailableItem($item, $availableQuantity);
        return $this;
    }

    /**
     * Get the unavailable item handler for the item. Handler based
     * on item product type.
     *
     * @param Mage_Sales_Model_Quote_Item
     * @return EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_IHandler
     */
    protected function _getUnavailableHandlerForItem(Mage_Sales_Model_Quote_Item $item)
    {
        $configuredHandlers = $this->_config->unavailableItemHandlers;
        $productType = $item->getProductType();

        $configuredHandler = $this->_loadConfiguredHandler($configuredHandlers, $productType)
            ?: $this->_loadConfiguredHandler($configuredHandlers, EbayEnterprise_Inventory_Model_Config::DEFAULT_UNAVAILABLE_ITEM_HANDLER_KEY);

        // If no useful specific or default instance is configured, go with this,
        // which should (as much as possible) be useful as a default implementation.
        return $configuredHandler ?: Mage::getModel('ebayenterprise_inventory/quantity_unavailable_item_default');
    }

    /**
     * Load an unavailable item handler for the given type based on a set of
     * handler configuration. Will return null if no viable handler is found
     * for that type.
     *
     * @param array
     * @param string|int
     * @return EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_IHandler|null
     */
    protected function _loadConfiguredHandler(array $configuredHandlers, $handlerType)
    {
        if (isset($configuredHandlers[$handlerType])) {
            $handlerModel = Mage::getModel($configuredHandlers[$handlerType]);
            if ($handlerModel && $handlerModel instanceof EbayEnterprise_Inventory_Model_Quantity_Unavailable_Item_IHandler) {
                return $handlerModel;
            }
        }
        return null;
    }

    /**
     * Notify the customer if the quote item is backorderable with setting to notify customer.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return self
     */
    protected function _notifyCustomerIfItemBackorderable(Mage_Sales_Model_Quote_Item_Abstract $quoteItem)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = $this->getItemProductStockItem($quoteItem);
        /** @var float $quoteItemQty */
        $quoteItemQty = $quoteItem->getQty();
        /** @var float $romQtyAvailable */
        $romQtyAvailable = $this->_getAvailableQuantityForItem($quoteItem);
        if ($romQtyAvailable < $quoteItemQty && (int) $stockItem->getBackorders() === Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY) {
            // Setting the stock item quantity to the quantity available in ROM in order to get Vanilla Magento
            // backorderable message about item out of stock but backorderable to display for this item in the cart.
            $stockItem->setQty($romQtyAvailable);
            /** @var float */
            $rowQty = $this->_calculateTotalQuantityRequested($quoteItem);
            /** @var Varien_Object */
            $result = $stockItem->checkQuoteItemQty($rowQty, $quoteItemQty);

            if (!is_null($result->getMessage())) {
                $quoteItem->setMessage($result->getMessage());
            }

            if (!is_null($result->getItemBackorders())) {
                $quoteItem->setBackorders($result->getItemBackorders());
            }
        }
        return $this;
    }

    /**
     * When an item has no stock in ROM and is set to backorderable in Magento, then return true,
     * otherwise return false as this item is not backorderable it is simply out of stock in ROM.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  int
     * @return bool
     */
    protected function isItemBackorderable(Mage_Sales_Model_Quote_Item_Abstract $quoteItem, $romAvailableQuantity)
    {
        return ($romAvailableQuantity <= $quoteItem->getQty())
            && ($this->getItemProductStockItem($quoteItem)->getBackorders() > Mage_CatalogInventory_Model_Stock::BACKORDERS_NO);
    }

    /**
     * Get a passed in item product stock item.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    protected function getItemProductStockItem(Mage_Sales_Model_Quote_Item_Abstract $quoteItem)
    {
        return $quoteItem->getProduct()->getStockItem();
    }
}

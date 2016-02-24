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

class EbayEnterprise_Inventory_Test_Model_Quantity_ServiceTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Inventory_Model_Quantity_Collector */
    protected $_quantityCollector;
    /** @var EbayEnterprise_Inventory_Model_Quantity_Results */
    protected $_quantityResults;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $_inventoryHelper;
    /** @var Mage_Sales_Model_Quote */
    protected $_quote;
    /** @var EbayEnterprise_Inventory_Helper_Item_Selection */
    protected $_inventoryItemSelection;
    /** @var Mage_Catalog_Model_Product */
    protected $_product;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    public function setUp()
    {
        // Create a mock of the quantity results for the quote.
        $this->_quantityResults = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_results')
            ->disableOriginalConstructor()
            ->setMethods(['getQuantityBySku', 'getQuantityByItemId'])
            ->getMock();

        // Create a quote object to use within tests. Expected by
        // default by the quantity collector when getting results for a quote.
        $this->_quote = $this->getModelMock(
            'sales/quote',
            ['addErrorInfo', 'getAllItems']
        );

        // Mock the quantity collector to simply always return
        // the mocked quantity results throughout the tests.
        $this->_quantityCollector = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_collector')
            ->disableOriginalConstructor()
            ->setMethods(['getQuantityResultsForQuote', 'clearResults'])
            ->getMock();
        $this->_quantityCollector->expects($this->any())
            ->method('getQuantityResultsForQuote')
            ->with($this->identicalTo($this->_quote))
            ->will($this->returnValue($this->_quantityResults));

        // Mock of the item selection helper, used to filter
        // down quote items down to just the items that need
        // to be checked by the inventory service.
        $this->_inventoryItemSelection = $this->getHelperMock(
            'ebayenterprise_inventory/item_selection',
            ['isExcludedParent','isStockManaged']
        );

        // Mock the inventory's data helper to control
        // expected results from translations doing translations.
        $this->_inventoryHelper = $this->getHelperMock(
            'ebayenterprise_inventory/data',
            ['__', 'getRomSku']
        );
        // Mock out the translate method, while it would be nice to ensure
        // strings are getting translated through this method, the complexity
        // of doing so is not currently worth the effort.
        $this->_inventoryHelper->expects($this->any())
            ->method('__')
            ->will($this->returnArgument(0));
        // Mock the SKU normalization method to just always return the given
        // SKU. Prevents the need for ensuring configuration is set up just so
        // for given SKUs to match the "normalized" SKU.
        $this->_inventoryHelper->expects($this->any())
            ->method('getRomSku')
            ->will($this->returnArgument(0));

        // Mock calculations of total item quantity.
        $this->_quantityHelper = $this->getHelperMock(
            'ebayenterprise_inventory/quantity',
            ['calculateTotalQuantityRequested']
        );

        /** @var EbayEnterprise_MageLog_Helper_Context */
        $this->_context = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $this->_context->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        // Instance of the model being tested, injected
        // with the mocked dependencies.
        $this->_quantityService = Mage::getModel(
            'ebayenterprise_inventory/quantity_service',
            [
                'quantity_collector' => $this->_quantityCollector,
                'inventory_item_selection' => $this->_inventoryItemSelection,
                'inventory_helper' => $this->_inventoryHelper,
                'quantity_helper' => $this->_quantityHelper,
                'log_context' => $this->_context,
            ]
        );
        $this->_product = Mage::getModel('catalog/product', ['stock_item' => Mage::getModel('catalogInventory/stock_item', [
            'backorders' => Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
        ])]);
    }

    /**
     * Create a mock quote item. Item will return an expected
     * sku, quantity, id, name and parent item id. It will also be set up to
     * return the quote used within this test class. Mock will also
     * have the "addErrorInfo" method mocked for testing side-effects
     * of this method.
     *
     * @param string
     * @param int
     * @param int|null
     * @param string
     * @param Mage_Sales_Model_Quote_Item|null
     * @return Mage_Sales_Model_Quote_Item
     */
    protected function _mockQuoteItem(
        $sku,
        $qty,
        $id = null,
        $name = 'Item Name',
        Mage_Sales_Model_Quote_Item $parentItem = null
    ) {
        $quoteItem = $this->getModelMock(
            'sales/quote_item',
            [
                'addErrorInfo',
                'getQty',
                'getParentItem',
                'getSku',
                'getId',
                'getName',
                'getQuote',
                'getProduct'
            ]
        );
        $quoteItem->expects($this->any())
            ->method('getQty')
            ->will($this->returnValue($qty));
        $quoteItem->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $quoteItem->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $quoteItem->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $quoteItem->expects($this->any())
            ->method('getParentItem')
            ->will($this->returnValue($parentItem));
        $quoteItem->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->_quote));
        $quoteItem->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($this->_product));
        return $quoteItem;
    }

    /**
     * Create a quantity model with the provided
     * quantity, sku and item id.
     *
     * @param int
     * @param string
     * @param int
     * @return EbayEnterprise_Inventory_Model_Quantity
     */
    protected function _createQuantity($quantity, $sku, $itemId)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/quantity',
            ['quantity' => $quantity, 'sku' => $sku, 'item_id' => $itemId]
        );
    }

    /**
     * Script the quantity results mock to return a quantity result by
     * sku or item id.
     *
     * @param array $resultsData Array of arrays, each inner array must contain:
     *                           - sku => string
     *                           - item_id => int|null
     *                           - quantity => EbayEnterprise_Inventory_Model_Quantity
     */
    protected function _mockQuantityResults($resultsData)
    {
        $bySku = array_map(
            function ($result) {
                return [$result['sku'], $result['quantity']];
            },
            $resultsData
        );
        $byId = array_map(
            function ($result) {
                return [$result['item_id'], $result['quantity']];
            },
            // If an item doesn't have an id, it will not be retrievable
            // by id, so filter out any results that have an empty item id.
            array_filter(
                $resultsData,
                function ($result) {
                    return (bool) $result['item_id'];
                }
            )
        );

        $this->_quantityResults->expects($this->any())
            ->method('getQuantityBySku')
            ->will($this->returnValueMap($bySku));
        $this->_quantityResults->expects($this->any())
            ->method('getQuantityByItemId')
            ->will($this->returnValueMap($byId));
    }

    /**
     * Mock out the collection of items to be checked - quote should return an
     * array containing the item the the item selector, given that array, should
     * return it (assume the item isn't filtered out).
     *
     * @param Mage_Sales_Model_Quote_Item
     * @return self
     */
    protected function _mockQuoteItemSelection($quoteItem, $dupeSkuQuoteItem = null)
    {
        $quoteItems = $dupeSkuQuoteItem ?
            [$quoteItem, $dupeSkuQuoteItem] :
            [$quoteItem];
        $invokeArgument = $dupeSkuQuoteItem ?
            $this->logicalOr(
                $this->identicalTo($quoteItem),
                $this->identicalTo($dupeSkuQuoteItem)
            ) : $this->identicalTo($quoteItem);
        $this->_quote->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue($quoteItems));
        $this->_inventoryItemSelection->expects($this->any())
            ->method('isExcludedParent')
            ->with($invokeArgument)
            ->willReturn(false);
        $this->_inventoryItemSelection->expects($this->any())
            ->method('isStockManaged')
            ->with($invokeArgument)
            ->willReturn(true);
        return $this;
    }

    /**
     * When checking a quote to have available inventory,
     * each item in the quote should be checked for sufficient
     * available quantity. If all items are available, method
     * should return "self" without manipulating the quote or
     * any of the items.
     */
    public function testCheckQuoteItemInventory()
    {
        $itemSku = 'the-item';
        $itemQty = 5;
        $itemId = 39;
        // Available quantity should be greater than item quantity.
        $availQty = 10;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId);
        $quoteItems = [$quoteItem];

        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Side-effect tests: ensure that no error infos are added
        // to the item or the quote.
        $quoteItem->expects($this->never())
            ->method('addErrorInfo');
        $this->_quote->expects($this->never())
            ->method('addErrorInfo');

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($quoteItem)
        );
    }

    /**
     * When insufficient stock is available for an item that
     * is already in the quote, an error info should be added
     * to the quote as well as the quote item.
     */
    public function testCheckQuoteItemInventoryInsufficientStock()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $itemId = 39;
        // Available quantity should be greater than item quantity.
        $availQty = 1;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_INSUFFICIENT_STOCK_MESSAGE;
        $itemErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_INSUFFICIENT_STOCK_MESSAGE;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $quoteItems = [$quoteItem];
        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Side-effect tests: ensure that both the item and
        // the quote have error info added for the unavailable
        // item.
        $quoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $this->_quote->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_TYPE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($quoteErrorMessage)
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($quoteItem)
        );
    }

    /**
     * When attempting to add an item to cart that does not
     * have sufficient stock available, an exception should
     * be thrown to prevent the item from being added to cart.
     */
    public function testCheckQuoteItemInventoryPreventItemAddToQuote()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $itemId = null;
        // Available quantity should be greater than item quantity.
        $availQty = 1;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_EXCEPTION_MESSAGE;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $quoteItems = [$quoteItem];
        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Item is expected to not already be in the order, so when unavailable,
        // an exception should be thrown.
        $this->setExpectedException('EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception', $quoteErrorMessage);
        $this->_quantityService->checkQuoteItemInventory($quoteItem);
    }

    /**
     * When multiple items with the same sku exist in the quote, each item's
     * quantity should be checked against the total quantity of that sku in the
     * quote. If the available quantity is greater than the combined quantity
     * no errors should be added to items or the quote.
     */
    public function testCheckQuoteItemInventoryMultipleItemsSameSku()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $dupeSkuItemQty = 3;
        // Both items with the same sku should have the different item ids as
        // they will be different items in the quote.
        $itemId = 3;
        $dupeSkuItemId = 5;
        // Available quantity should be greater than both item's quantities.
        $availQty = 10;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $dupeSkuQuoteItem = $this->_mockQuoteItem($itemSku, $dupeSkuItemQty, $dupeSkuItemId, $itemName);
        $quoteItems = [$quoteItem, $dupeSkuQuoteItem];
        $this->_mockQuoteItemSelection($quoteItem, $dupeSkuQuoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with(
                $this->logicalOr($this->identicalTo($quoteItem), $this->identicalTo($dupeSkuQuoteItem)),
                $this->identicalTo($quoteItems)
            )
            ->will($this->returnValue($itemQty + $dupeSkuItemQty));

        // Mock the quantity results - a quantity model for the
        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($dupeSkuQuoteItem)
        );
    }

    /**
     * When multiple items with the same sku exist in the quote, each item's
     * quantity should be checked against the total quantity of that sku in the
     * quote. If the available quantity is less than the combined quantity for
     * all items with the same sku, error info should be added to all items with
     * the sku.
     */
    public function testCheckQuoteItemInventoryMultipleItemsSameSkuUnavailableQuantity()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $dupeSkuItemQty = 8;
        // Both items with the same sku should have the different item ids as
        // they will be different items in the quote.
        $itemId = 3;
        $dupeSkuItemId = 5;
        // Available quantity should be less than sum of both item's quantity.
        $availQty = 10;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_INSUFFICIENT_STOCK_MESSAGE;
        $itemErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_INSUFFICIENT_STOCK_MESSAGE;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $dupeSkuQuoteItem = $this->_mockQuoteItem($itemSku, $dupeSkuItemQty, $dupeSkuItemId, $itemName);
        $quoteItems = [$quoteItem, $dupeSkuQuoteItem];
        $this->_mockQuoteItemSelection($quoteItem, $dupeSkuQuoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with(
                $this->logicalOr($this->identicalTo($quoteItem), $this->identicalTo($dupeSkuQuoteItem)),
                $this->identicalTo($quoteItems)
            )
            ->will($this->returnValue($itemQty + $dupeSkuItemQty));

        // Mock the quantity results - a quantity model for the
        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Side-effect tests: ensure that both items and
        // the quote have error info added for the unavailable
        // item.
        $quoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $dupeSkuQuoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $this->_quote->expects($this->atLeastOnce())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_TYPE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($quoteErrorMessage)
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($quoteItem)
        );

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($dupeSkuQuoteItem)
        );
    }

    /**
     * When an item already in the cart is updated and that item is found to be
     * out of stock, the out of stock messages should be added to the quote
     * and item error info.
     */
    public function testCheckQuoteItemInventoryOutOfStockUpdate()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $itemId = 39;
        // Available quantity should be greater than item quantity.
        $availQty = 0;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_OUT_OF_STOCK_MESSAGE;
        $itemErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_OUT_OF_STOCK_MESSAGE;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $quoteItems = [$quoteItem];
        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Side-effect tests: ensure that both the item and
        // the quote have error info added for the unavailable
        // item.
        $quoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::OUT_OF_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $this->_quote->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_TYPE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::OUT_OF_STOCK_ERROR_CODE),
                $this->identicalTo($quoteErrorMessage)
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($quoteItem)
        );
    }

    /**
     * When an item is added to the cart and that item is found to be
     * out of stock, an exception should be thrown with the out of stock
     * exception message.
     */
    public function testCheckQuoteItemInventoryOutOfStockPreventItemAddToQuote()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $itemId = null;
        // Available quantity should be greater than item quantity.
        $availQty = 0;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::OUT_OF_STOCK_EXCEPTION_MESSAGE;

        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName);
        $quoteItems = [$quoteItem];
        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Item is expected to not already be in the order, so when unavailable,
        // an exception should be thrown.
        $this->setExpectedException('EbayEnterprise_Inventory_Exception_Quantity_Unavailable_Exception', $quoteErrorMessage);
        $this->_quantityService->checkQuoteItemInventory($quoteItem);
    }

    /**
     * When inventory errors are encountered for a child item, error info should
     * be added to the parent item, child item and quote.
     */
    public function testCheckQuoteItemInventoryParentItemMessages()
    {
        $itemSku = 'the-item';
        $itemName = 'Item Name';
        $itemQty = 5;
        $itemId = 39;
        // Force an error by setting available quantity to be less than requested quantity.
        $availQty = 1;
        // Error messages to be added to the quote and item.
        $quoteErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::QUOTE_INSUFFICIENT_STOCK_MESSAGE;
        $itemErrorMessage = EbayEnterprise_Inventory_Model_Quantity_Service::ITEM_INSUFFICIENT_STOCK_MESSAGE;

        // Create a parent item and a child item. Child item will be handled by
        // the service but when errors are present, they need to also be added
        // to the parent.
        $parentQuoteItem = $this->_mockQuoteItem($itemSku, 1, 38, $itemName);
        $quoteItem = $this->_mockQuoteItem($itemSku, $itemQty, $itemId, $itemName, $parentQuoteItem);
        $quoteItems = [$quoteItem];
        $this->_mockQuoteItemSelection($quoteItem);

        // Mock the mechanism used to determine total quantity of an item,
        // can return the expected quantity of the item requested.
        $this->_quantityHelper->expects($this->any())
            ->method('calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem), $this->identicalTo($quoteItems))
            ->will($this->returnValue($itemQty));

        // item which will be returned from the quantity results
        // model when getting the quantity result by sku using the
        // item's sku or id when using the item's id.
        $itemQuantityResult = $this->_createQuantity($availQty, $itemSku, $itemId);
        $this->_mockQuantityResults([
            ['sku' => $itemSku, 'item_id' => $itemId, 'quantity' => $itemQuantityResult]
        ]);

        // Side-effect tests: ensure that the item, parent item and
        // the quote have error info added for the unavailable
        // item.
        $parentQuoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $quoteItem->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($itemErrorMessage)
            )
            ->will($this->returnSelf());
        $this->_quote->expects($this->once())
            ->method('addErrorInfo')
            ->with(
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_TYPE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::ERROR_INFO_SOURCE),
                $this->identicalTo(EbayEnterprise_Inventory_Model_Quantity_Service::INSUFFICIENT_STOCK_ERROR_CODE),
                $this->identicalTo($quoteErrorMessage)
            )
            ->will($this->returnSelf());

        $this->assertSame(
            $this->_quantityService,
            $this->_quantityService->checkQuoteItemInventory($quoteItem)
        );
    }

    /**
     * Test that the method ebayenterprise_inventory/quantity_service::_notifyCustomerIfItemBackorderable()
     * when invoked, will be passed an instance of type sales/quote_item, in which it will determine
     * if the item in the passed in quote item is backorders and notify customer if so, then, it
     * will set the quote item with notification message.
     */
    public function testNotifyCustomerIfItemBackorderable()
    {
        /** @var string $message */
        $message = 'Some message about item not being stock';
        /** @var int $backorder */
        $backorder = Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY;
        /** @var Varien_Object $result */
        $result = new Varien_Object([
            'message' => $message,
            'item_backorders' => $backorder,
        ]);
        /** @var int $qty */
        $itemQty = 1;
        /** @var int $qty */
        $rowQty = 1;
        /** @var int $availableQty */
        $availableQty = 0;
        /** @var Mock_Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = $this->getModelMock('catalogInventory/stock_item', ['getBackorders', 'checkQuoteItemQty']);
        $stockItem->expects($this->once())
            ->method('getBackorders')
            ->will($this->returnValue($backorder));
        $stockItem->expects($this->once())
            ->method('checkQuoteItemQty')
            ->with($this->identicalTo($rowQty), $this->identicalTo($itemQty))
            ->will($this->returnValue($result));

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product', ['stock_item' => $stockItem]);
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = $this->getModelMock('sales/quote_item', ['getProduct', 'getQty', 'setMessage', 'setBackorders']);
        $quoteItem->expects($this->once())
            ->method('getProduct')
            ->will($this->returnValue($product));
        $quoteItem->expects($this->once())
            ->method('getQty')
            ->will($this->returnValue($itemQty));
        $quoteItem->expects($this->once())
            ->method('setMessage')
            ->with($this->identicalTo($message))
            ->will($this->returnSelf());
        $quoteItem->expects($this->once())
            ->method('setBackorders')
            ->with($this->identicalTo($backorder))
            ->will($this->returnSelf());

        $quantityService = $this->getModelMock('ebayenterprise_inventory/quantity_service', ['_calculateTotalQuantityRequested', '_getAvailableQuantityForItem']);
        $quantityService->expects($this->once())
            ->method('_calculateTotalQuantityRequested')
            ->with($this->identicalTo($quoteItem))
            ->will($this->returnValue($rowQty));
        $quantityService->expects($this->once())
            ->method('_getAvailableQuantityForItem')
            ->with($this->identicalTo($quoteItem))
            ->will($this->returnValue($availableQty));
        $this->assertSame($quantityService, EcomDev_Utils_Reflection::invokeRestrictedMethod($quantityService, '_notifyCustomerIfItemBackorderable', [$quoteItem]));
    }
}

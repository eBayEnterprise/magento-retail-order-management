<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_AllocationTest
	extends TrueAction_Eb2cCore_Test_Base
{
	protected $_allocation;

	const DATE_PAST = '2000-01-01 00:00:00 +0';
	const DATE_FUTURE = '2050-01-01 00:00:00 +0';

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_allocation = Mage::getModel('eb2cinventory/allocation');
	}

	public function buildQuoteMock()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue('1938 Some Street')
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('getManageStock')
		);

		$stockItemMock->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue(true)
			);

		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getStockItem')
		);

		$productMock->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItemMock)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array(
				'getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save', 'setEb2cReservationId',
				'setEb2cReservedAt', 'setEb2cQtyReserved', 'getProduct', 'getIsVirtual'
			)
		);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);

		$itemMock->expects($this->any())
			->method('setEb2cReservationId')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('setEb2cReservedAt')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('setEb2cQtyReserved')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock)
			);
		$itemMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue(false)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses', 'deleteItem')
		);

		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);

		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);
		$quoteMock->expects($this->any())
			->method('deleteItem')
			->will($this->returnSelf()
			);

		return $quoteMock;
	}

	public function providerAllocateQuoteItems()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing allocating quote items
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 * @loadFixture loadConfig.yaml
	 */
	public function testAllocateQuoteItems($quote)
	{
		$inventoryHelperMock = $this->getHelperMock('eb2cinventory/data', array('getOperationUri'));
		$inventoryHelperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/AllocationResponseMessage.xml'));
		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelperMock);

		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_allocation->allocateQuoteItems($quote)
		);
	}

	/**
	 * testing when allocating quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 * @loadFixture loadConfig.yaml
	 */
	public function testAllocateQuoteItemsWithApiCallException($quote)
	{
		$apiModelMock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will(
				$this->throwException(new Exception)
			);
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame(
			'',
			trim($this->_allocation->allocateQuoteItems($quote))
		);
	}

	public function providerBuildAllocationRequestMessage()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessage
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildAllocationRequestMessage($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_allocation->buildAllocationRequestMessage($quote)
		);
	}

	public function providerBuildAllocationRequestMessageWithException()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue('1938 Some Street')
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('getManageStock')
		);

		$stockItemMock->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue(true)
			);

		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getStockItem')
		);

		$productMock->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItemMock)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId', 'getProduct', 'getIsVirtual')
		);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue($this->throwException(new Exception)));
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue(false)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($this->throwException(new Exception))
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing building allocation request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessageWithException
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildAllocationRequestMessageWithException($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertNotNull(
			$this->_allocation->buildAllocationRequestMessage($quote)
		);
	}

	public function providerProcessAllocation()
	{
		$allocationData = array(
			array(
				'lineId' => 1,
				'reservation_id' => 'TAN_DEV_CLI-ABC-44',
				'reserved_at' => '2013-06-20 15:02:20',
				'qty' => 0,
			)
		);

		return array(
			array($this->buildQuoteMock(), $allocationData)
		);
	}

	/**
	 * testing processing allocation data
	 *
	 * @test
	 * @dataProvider providerProcessAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessAllocation($quote, $allocationData)
	{
		$this->assertSame(
			array('Sorry, item "SKU-1234" out of stock.'),
			$this->_allocation->processAllocation($quote, $allocationData)
		);
	}

	public function providerUpdateQuoteWithEb2cAllocation()
	{
		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('getManageStock')
		);

		$stockItemMock->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue(true)
			);

		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getStockItem')
		);

		$productMock->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItemMock)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array(
				'getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save', 'setQty', 'setEb2cReservationId',
				'setEb2cReservedAt', 'setEb2cQtyReserved', 'getProduct', 'getIsVirtual'
			)
		);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(2)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($this->buildQuoteMock())
			);
		$itemMock->expects($this->any())
			->method('setQty')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('setEb2cReservationId')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('setEb2cReservedAt')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('setEb2cQtyReserved')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue(false)
			);

		$quoteData = array(
			'lineId' => 1,
			'reservation_id' => 'TAN_DEV_CLI-ABC-44',
			'reserved_at' => '2013-06-20 15:02:20',
			'qty' => 1,
		);

		return array(
			array($itemMock, $quoteData)
		);
	}

	/**
	 * testing _updateQuoteWithEb2cAllocation method
	 *
	 * @test
	 * @dataProvider providerUpdateQuoteWithEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testUpdateQuoteWithEb2cAllocation($quoteItem, $quoteData)
	{
		$allocationReflector = new ReflectionObject($this->_allocation);
		$updateQuoteWithAllocation = $allocationReflector->getMethod('_updateQuoteWithEb2cAllocation');
		$updateQuoteWithAllocation->setAccessible(true);
		$this->assertSame(
			'Sorry, we only have 1 of item "SKU-1234" in stock.',
			$updateQuoteWithAllocation->invoke($this->_allocation, $quoteItem, $quoteData)
		);
	}

	public function providerRollbackAllocation()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing rollbackAllocation method
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocation($quote)
	{
		// testing when you can rolling back allocated inventory
		$this->assertNotNull(
			$this->_allocation->rollbackAllocation($quote)
		);
	}

	/**
	 * testing when rolling back allocation quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocationWithApiCallException($quote)
	{
		$apiModelMock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame(
			'',
			trim($this->_allocation->rollbackAllocation($quote))
		);
	}

	public function providerHasAllocation()
	{
		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('getManageStock')
		);

		$stockItemMock->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue(true)
			);

		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getStockItem')
		);

		$productMock->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItemMock)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getEb2cReservationId', 'getProduct', 'getIsVirtual')
		);
		$itemMock->expects($this->any())
			->method('getEb2cReservationId')
			->will($this->returnValue('FAKE-RESERVATION-ID')
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));
		$itemMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue(false)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing when allocation data is found in quote items
	 *
	 * @test
	 * @dataProvider providerHasAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testHasAllocation($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertSame(
			true,
			$this->_allocation->hasAllocation($quote)
		);
	}

	/**
	 * @todo Check the configured expiration period and don't use hard-coded dates in this provider.
	 */
	public function providerIsExpired()
	{
		$stockItemMock = $this->getModelMock('cataloginventory/stock_item', array('getManageStock'));
		$stockItemMock->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue(true));

		$productMock = $this->getModelMock('catalog/product', array('getStockItem'));
		$productMock->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItemMock));

		$notExpiredItem = $this->getModelMock('sales/quote_item', array('hasEb2cReservedAt', 'getEb2cReservedAt'));
		$notExpiredItem->setData(array(
			'is_virtual' => false,
			'product' => $productMock,
		));
		$notExpiredItem
			->expects($this->once())
			->method('getEb2cReservedAt')
			->will($this->returnValue(self::DATE_FUTURE));
		$notExpiredItem
			->expects($this->once())
			->method('hasEb2cReservedAt')
			->will($this->returnValue(true));

		$expiredItem = $this->getModelMock('sales/quote_item', array('hasEb2cReservedAt', 'getEb2cReservedAt'));
		$expiredItem->setData(array(
			'is_virtual' => false,
			'product' => $productMock,
		));
		$expiredItem
			->expects($this->once())
			->method('getEb2cReservedAt')
			->will($this->returnValue(self::DATE_PAST));
		$expiredItem
			->expects($this->once())
			->method('hasEb2cReservedAt')
			->will($this->returnValue(true));

		$notExpiredQuote = $this->getModelMock('sales/quote', array('getAllItems'));
		$notExpiredQuote->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($notExpiredItem)));

		$expiredQuote = $this->getModelMock('sales/quote', array('getAllItems'));
		$expiredQuote->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($expiredItem, $notExpiredItem)));

		return array(
			array($notExpiredQuote->setId('not_expired')),
			array($expiredQuote->setId('expired')),
		);
	}

	/**
	 * testing isExpired method
	 *
	 * @test
	 * @dataProvider providerIsExpired
	 * @loadFixture loadConfig.yaml
	 */
	public function testIsExpired($quote)
	{
		// If the quote has at least one managed-stock item that is expired, isExpired should be false.
		// If the quote has no managed-stock items, or no managed-stock items are expired, isExpired should be true.
		$isExpired = $this->expected($quote->getId())->getEb2cIsExpired();
		$this->assertSame($isExpired, $this->_allocation->isExpired($quote));
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/AllocationTest/fixtures/AllocationResponseMessage.xml', FILE_USE_INCLUDE_PATH))
		);
	}

	/**
	 * testing parseResponse
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerParseResponse
	 */
	public function testParseResponse($allocationResponseMessage)
	{
		$mageDate = $this->getModelMock('core/date', array('date'));
		$mageDate->expects($this->any())
			->method('date')
			->will($this->returnValue('flub'));
		$this->replaceByMock('model', 'core/date', $mageDate);
		$parsedResponse = $this->_allocation->parseResponse($allocationResponseMessage);
		$itemResponse = $parsedResponse[0]; // Expecting a nested array with one array in it.

		$expected = array(
			'lineId' => '106',
			'itemId' => '8525 PDA',
			'qty' => 1,
			'reservation_id' => 'TAN_DEV_CLI-ABC-44',
			'reserved_at' => Mage::getModel('core/date')->date('Y-m-d H:i:s')
		);
		$this->assertSame($expected, $itemResponse);
	}

	/**
	 * Create a stub quote that will return the given array of items
	 * @param  Mage_Sales_Model_Quote_Item[] $items
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _createQuoteStubWithItems($items)
	{
		$quote = $this->getModelMock('sales/quote', array('getAllItems'));
		$quote->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($items));
		return $quote;
	}

	protected function _createItemStub($managed, $hasAlloc, $isExpired)
	{
		$stubStockItem = $this->getModelMock('cataloginventory/stock_item', array('getManageStock'));
		$stubStockItem->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue($managed));
		$productStub = $this->getModelMock('catalog/product', array('getStockItem'));
		$productStub->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stubStockItem));

		$item = $this->getModelMock(
			'sales/quote_item',
			array('getProduct', 'getEb2cReservationId', 'hasEb2cReservedAt', 'getEb2cReservedAt',)
		);
		$item->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productStub));
		$item->expects($this->any())
			->method('getEb2cReservationId')
			->will($this->returnValue($hasAlloc ? '12345' : null));
		$item->expects($this->any())
			->method('hasEb2cReservedAt')
			->will($this->returnValue(true));
		$item->expects($this->any())
			->method('getEb2cReservedAt')
			->will($this->returnValue($isExpired ? self::DATE_PAST : self::DATE_FUTURE));
		return $item;
	}

	/**
	 * Data provider for testing if an allocation is required.
	 * @return array
	 */
	public function allocationRequiredProvider()
	{
		return array(
			array(
				'noManaged-nAlloc-stale',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(false, false, true),)),
			),
			array(
				'noManaged-nAlloc-fresh',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(false, false, false),)),
			),
			array(
				'noManaged-yAlloc-stale',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(false, true, true),)),
			),
			array(
				'noManaged-yAlloc-fresh',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(false, true, false),)),
			),
			array(
				'isManaged-nAlloc-stale',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(true, false, true),)),
			),
			array(
				'isManaged-nAlloc-fresh',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(true, false, false),)),
			),
			array(
				'isManaged-yAlloc-stale',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(true, true, true),)),
			),
			array(
				'isManaged-yAlloc-fresh',
				$this->_createQuoteStubWithItems(array($this->_createItemStub(true, true, false),)),
			),
		);
	}

	/**
	 * Test for checking if an allocation is required for the given quote.
	 * @param  Mage_Sales_Model_Quote $quote The quote to be tested.
	 * @test
	 * @dataProvider allocationRequiredProvider
	 */
	public function testIfAllocationRequired($key, $quote)
	{
		$this->assertSame(
			$this->expected($key)->getIsRequired(),
			Mage::getModel('eb2cinventory/allocation')->requiresAllocation($quote)
		);
	}

}

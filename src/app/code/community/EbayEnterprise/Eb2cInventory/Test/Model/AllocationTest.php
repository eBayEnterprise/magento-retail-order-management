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

class EbayEnterprise_Eb2cInventory_Test_Model_AllocationTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_allocation;

	const DATE_PAST = '2000-01-01 00:00:00 +0';
	const DATE_FUTURE = '2050-01-01 00:00:00 +0';
	const REQUEST_ID = '123';
	const RESERVATION_ID = '123-123-123-123';
	const QUOTE_ENTITY_ID = 123;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_allocation = Mage::getModel('eb2cinventory/allocation');

		// suppressing the real session from starting
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);
	}

	/**
	 * Get a simple address object.
	 * @param  array $data If specified, address object will be created with the given data. Otherwise with some sample data.
	 * @return Mage_Sales_Model_Quote_Address
	 */
	protected function _createAddressObject($data=array())
	{
		$addressData = empty($data) ?
			array('firstname' => 'Foo', 'lastname' => 'Bar', 'street' => 'One Bagshot Row',
				'city' => 'Bag End', 'region_id' => '51', 'region' => 'PA', 'country_id' => 'US',
				'telephone' => '555-555-5555', 'postcode' => '19123', 'shipping_method' => 'USPSStandard'
			) :
			$data;
		return Mage::getModel('sales/quote_address', $addressData);
	}

	public function buildQuoteMock()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode')
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
			array('getAllVisibleItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses', 'deleteItem')
		);

		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);

		$quoteMock->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValueMap(array(array(1, $itemMock))));
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

	/**
	 * testing building inventory details request message
	 *
	 */
	public function testAllocateQuoteItems()
	{
		$uri = 'https://some.u.r/i';
		$xsd = 'allocationschema.xsd';
		$this->replaceCoreConfigRegistry(array(
			'xsdFileAllocation' => $xsd
		));

		$helper = $this->getHelperMock('eb2cinventory/data', array('getOperationUri'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);
		$helper->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('allocate_inventory'))
			->will($this->returnValue($uri));

		// Mock the API to verify the request is made with the proper request
		$api = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('request'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXml('<inventoryallocation />');

		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getShippingAddress'))
			->getMock();
		$quote->expects($this->once())
			->method('getShippingAddress')
			->will($this->returnValue(New Varien_Object()));
		$testModel = $this->getModelMock('eb2cinventory/allocation', array('_buildAllocationRequestMessage'));
		$testModel->expects($this->once())
			->method('_buildAllocationRequestMessage')
			->with($this->identicalTo($quote))
			->will($this->returnValue($doc));

		// Canned response from the API. Should be what finally gets returned
		// from the allocateQuoteItems method when successful.
		$response = '<What>Ever</What>';
		$api->expects($this->once())
			->method('request')
			->with($this->identicalTo($doc, $xsd, $uri))
			->will($this->returnValue($response));
		// Validate the request message matches the expected message
		$this->assertSame($response, $testModel->allocateQuoteItems($quote));
	}

	/**
	 * Verify that allocateQuoteItems logs a warning when the quote object
	 * doesn't include a shipping address
	 *
	 */
	public function testAllocateQuoteItemsLogsWarning()
	{
		$mockLogger = $this->getHelperMock('ebayenterprise_magelog', array('warning'));
		$this->replaceByMock('helper', 'ebayenterprise_magelog', $mockLogger);

		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getShippingAddress'))
			->getMock();
		$quote->expects($this->once())
			->method('getShippingAddress')
			->willReturn(null);

		$testModel = $this->getModelMock('eb2cinventory/allocation', array('_buildAllocationRequestMessage'));

		// check that 'warning' is called with the appropriate message
		$mockLogger->expects($this->once())
			->method('warning')
			->with($this->identicalTo('{function_name} missing shipping address.'), $this->isType('array'));
		// Validate the request message returns an empty string
		$this->assertSame('', $testModel->allocateQuoteItems($quote));
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
			array('getAllVisibleItems')
		);
		$quoteMock->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($itemMock))
			);

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing when allocation data is found in quote items
	 *
	 * @dataProvider providerHasAllocation
	 */
	public function testHasAllocation($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertTrue($this->_allocation->hasAllocation($quote));
	}

	public function providerIsExpired()
	{
		$elapseTime = (int) Mage::getStoreConfig('eb2cinventory/allocation_expired', null) + 15; // configure elapse time plus 15 minute more
		$currentDate = new DateTime(gmdate('c'));
		$intervalFormat = 'PT' . $elapseTime . 'M';
		$interval = new DateInterval($intervalFormat);
		$passDate = new DateTime($currentDate->format('Y-m-d H:i:s'));
		$passDate->sub($interval);
		$dateInPass = $passDate->format('Y-m-d H:i:s') . ' +0';
		$futureDate = new DateTime($currentDate->format('Y-m-d H:i:s'));
		$futureDate->add($interval);
		$dateInFuture = $futureDate->format('Y-m-d H:i:s') . ' +0';

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
		$notExpiredItem->expects($this->once())
			->method('getEb2cReservedAt')
			->will($this->returnValue($dateInFuture));
		$notExpiredItem->expects($this->once())
			->method('hasEb2cReservedAt')
			->will($this->returnValue(true));

		$expiredItem = $this->getModelMock('sales/quote_item', array('hasEb2cReservedAt', 'getEb2cReservedAt'));
		$expiredItem->setData(array(
			'is_virtual' => false,
			'product' => $productMock,
		));

		$expiredItem->expects($this->once())
			->method('getEb2cReservedAt')
			->will($this->returnValue($dateInPass));
		$expiredItem->expects($this->once())
			->method('hasEb2cReservedAt')
			->will($this->returnValue(true));

		$notExpiredQuote = $this->getModelMock('sales/quote', array('getAllVisibleItems'));
		$notExpiredQuote->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($notExpiredItem)));

		$expiredQuote = $this->getModelMock('sales/quote', array('getAllVisibleItems'));
		$expiredQuote->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($expiredItem, $notExpiredItem)));

		return array(
			array($notExpiredQuote->setId('not_expired')),
			array($expiredQuote->setId('expired')),
		);
	}

	/**
	 * testing isExpired method
	 *
	 * @dataProvider providerIsExpired
	 */
	public function testIsExpired($quote)
	{
		// If the quote has at least one managed-stock item that is expired, isExpired should be false.
		// If the quote has no managed-stock items, or no managed-stock items are expired, isExpired should be true.
		$isExpired = $this->expected($quote->getId())->getEb2cIsExpired();
		$this->assertSame($isExpired, $this->_allocation->isExpired($quote));
	}
	/**
	 * testing parseResponse
	 *
	 */
	public function testParseResponse()
	{
		$allocationResponseMessage = '<?xml version="1.0" encoding="utf-8"?>
<AllocationResponseMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0" reservationId="TAN_DEV_CLI-ABC-44">
	<AllocationResponse lineId="106" itemId="8525 PDA">
		<AmountAllocated>1</AmountAllocated>
	</AllocationResponse>
</AllocationResponseMessage>';
		$mageDate = $this->getModelMock('core/date', array('date'));
		$mageDate->expects($this->any())
			->method('date')
			->will($this->returnValue('flub'));
		$this->replaceByMock('model', 'core/date', $mageDate);
		$parsedResponse = $this->_allocation->parseResponse($allocationResponseMessage);

		$expected = array(array(
			'lineId' => '106',
			'itemId' => '8525 PDA',
			'qty' => 1,
			'reservation_id' => 'TAN_DEV_CLI-ABC-44',
			'reserved_at' => Mage::getModel('core/date')->date('Y-m-d H:i:s')
		));
		$this->assertSame($expected, $parsedResponse);
	}
	/**
	 * When an allocation is made with 0 quantity, assume the product is not
	 * available and remove it from the cart. This should result in a message
	 * being returned indicating that the item was removed.
	 */
	public function testUpdateQuoteWithZeroQuantityAllocation()
	{
		// when the allocation qty is 0, none of the other data matters
		$allocationData = array('qty' => 0);
		$resultMessage = 'translated message';
		$sku = '45-123abc';

		$quote = $this->getModelMock('sales/quote', array('deleteItem', 'save'));
		$quoteItem = $this->getModelMock('sales/quote_item', array('save'));
		$orderitem = Mage::getModel('sales/order_item');
		$inventoryHelper = $this->getHelperMock('eb2cinventory/data', array('__'));
		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelper);

		// populate the quote item with data needed by the method
		$quoteItem->addData(array('qty' => 1, 'sku' => $sku));
		$quoteItem->setQuote($quote);

		$quote->expects($this->once())
			->method('deleteItem')
			->with($this->identicalTo($quoteItem))
			->will($this->returnSelf());

		// Due to timing of the event updating the quote, there's no reason to
		// ever save the quote or quote item during this method.
		$quote->expects($this->never())
			->method('save')
			->will($this->returnSelf());
		$quoteItem->expects($this->never())
			->method('save')
			->will($this->returnSelf());

		$inventoryHelper->expects($this->once())
			->method('__')
			->with(
				$this->identicalTo('EbayEnterprise_Eb2cInventory_Allocation_Qty_Out_Stock_Message'),
				$this->identicalTo($sku)
			)
			->will($this->returnValue($resultMessage));

		$this->assertSame(
			$resultMessage,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_allocation,
				'_updateQuoteWithEb2cAllocation',
				array($quoteItem, $orderitem, $allocationData)
			)
		);
	}
	/**
	 * When the quantity of an item allocated is less than the quantity requested
	 * but still greater than 0, the item quantity should be set to the quantity
	 * allocated and a message indicating the quantity has been changed should
	 * be returned.
	 */
	public function testUpdateQuoteWithInsufficientQuantity()
	{
		$sku = '45-abc123';
		$resultMessage = 'translated message';
		$requestQty = 3;
		$allocatedQty = 2;
		$quote = $this->getModelMock('sales/quote', array('save'));
		$quoteItem = $this->getModelMock('sales/quote_item', array('save'));
		$quoteItem->setData(array('qty' => $requestQty, 'sku' => $sku));
		$quoteItem->setQuote($quote);
		$orderitem = Mage::getModel('sales/order_item');
		$allocationData = array(
			'qty' => $allocatedQty,
			'reservation_id' => 'RES-ID-2',
			'reserved_at' => '2014-01-01 11:11:11'
		);
		$inventoryHelper = $this->getHelperMock('eb2cinventory/data', array('__'));
		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelper);

		// Due to timing of the event updating the quote, there's no reason to
		// ever save the quote or quote item during this method.
		$quote->expects($this->never())
			->method('save')
			->will($this->returnSelf());
		$quoteItem->expects($this->never())
			->method('save')
			->will($this->returnSelf());
		$inventoryHelper->expects($this->once())
			->method('__')
			->with(
				$this->identicalTo('EbayEnterprise_Eb2cInventory_Allocation_Qty_Limited_Stock_Message'),
				$this->identicalTo($allocatedQty),
				$this->identicalTo($sku)
			)
			->will($this->returnValue($resultMessage));

		$this->assertSame(
			$resultMessage,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_allocation,
				'_updateQuoteWithEb2cAllocation',
				array($quoteItem, $orderitem, $allocationData)
			)
		);
		// make sure the quote item was updated with allocation data
		$this->assertSame($allocationData['reservation_id'], $quoteItem->getEb2cReservationId());
		$this->assertSame($allocationData['reserved_at'], $quoteItem->getEb2cReservedAt());
		$this->assertSame($allocationData['qty'], $quoteItem->getEb2cQtyReserved());
		// quote item quantity should have been updated to match the reserved quantity
		$this->assertEquals($allocatedQty, $quoteItem->getQty());
	}
	/**
	 * When an item was successfully allocated, allocated qty is greater than or
	 * equal to the requested quantity, the item should be updated with allocation
	 * data and the quote and item saved. An empty response/error message should
	 * be returned as no errors were encountered.
	 */
	public function testUpdateQuoteWithAllocation()
	{
		$sku = '45-abc123';
		$origItemQuantity = 2;
		$allocatedQty = 3;

		$quote = $this->getModelMock('sales/quote', array('save'));
		$quoteItem = $this->getModelMock('sales/quote_item', array('save'));
		$quoteItem->setData(array('qty' => $origItemQuantity, 'sku' => $sku));
		$quoteItem->setQuote($quote);
		$orderItem = Mage::getModel('sales/order_item');
		$allocationData = array(
			'qty' => $allocatedQty,
			'reservation_id' => 'RES-ID-2',
			'reserved_at' => '2014-01-01 11:11:11'
		);
		// Due to timing of the event updating the quote, there's no reason to
		// ever save the quote or quote item during this method.
		$quote->expects($this->never())
			->method('save')
			->will($this->returnSelf());
		$quoteItem->expects($this->never())
			->method('save')
			->will($this->returnSelf());

		$this->assertSame(
			'',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_allocation,
				'_updateQuoteWithEb2cAllocation',
				array($quoteItem, $orderItem, $allocationData)
			)
		);
		// make sure the quote item was updated with allocation data
		$this->assertSame($allocationData['reservation_id'], $quoteItem->getEb2cReservationId());
		$this->assertSame($allocationData['reserved_at'], $quoteItem->getEb2cReservedAt());
		$this->assertSame($allocationData['qty'], $quoteItem->getEb2cQtyReserved());
		$this->assertSame($origItemQuantity, $quoteItem->getQty());
	}
	/**
	 * Create a stub quote that will return the given array of items
	 * @param  Mage_Sales_Model_Quote_Item[] $items
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _createQuoteStubWithItems($items)
	{
		$quote = $this->getModelMock('sales/quote', array('getAllVisibleItems'));
		$quote->expects($this->any())
			->method('getAllVisibleItems')
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
	 * @dataProvider allocationRequiredProvider
	 */
	public function testIfAllocationRequired($key, $quote)
	{
		$this->assertSame(
			$this->expected($key)->getIsRequired(),
			Mage::getModel('eb2cinventory/allocation')->requiresAllocation($quote)
		);
	}

	/**
	 * Testing _buildAllocationRequestMessage method
	 */
	public function testBuildAllocationRequestMessage()
	{
		$item = $this->getModelMockBuilder('sales/quote_item')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'getSku', 'getQty', ))
			->getMock();
		$item->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$item->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234'));
		$item->expects($this->once())
			->method('getQty')
			->will($this->returnValue(1));

		$address = $this->getModelMockBuilder('sales/quote_address')
			->disableOriginalConstructor()
			->setMethods(array('getShippingMethod', 'getStreet', 'getCity', 'getRegionCode', 'getCountryId', 'getPostcode'))
			->getMock();
		$address->expects($this->once())
			->method('getShippingMethod')
			->will($this->returnValue('mage_ship_method'));
		$address->expects($this->once())
			->method('getStreet')
			->with($this->equalTo(1))
			->will($this->returnValue('1075 First Avenue'));
		$address->expects($this->once())
			->method('getCity')
			->will($this->returnValue('King of Prussia'));
		$address->expects($this->once())
			->method('getRegionCode')
			->will($this->returnValue('PA'));
		$address->expects($this->once())
			->method('getCountryId')
			->will($this->returnValue('US'));
		$address->expects($this->once())
			->method('getPostcode')
			->will($this->returnValue('19406'));

		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getAllVisibleItems', 'getShippingAddress'))
			->getMock();
		$quote->expects($this->once())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($item)));
		$quote->expects($this->once())
			->method('getShippingAddress')
			->will($this->returnValue($address));

		$helper = $this->getHelperMockBuilder('eb2ccore/shipping')
			->disableOriginalConstructor()
			->setMethods(array('getMethodSdkId'))
			->getMock();
		$helper->expects($this->once())
			->method('getMethodSdkId')
			->with($this->identicalTo('mage_ship_method'))
			->will($this->returnValue('ANY_STD'));
		$this->replaceByMock('helper', 'eb2ccore/shipping', $helper);

		$invHelper = $this->getHelperMock('eb2cinventory/data', array('getInventoriedItems'));
		$invHelper->expects($this->once())
			->method('getInventoriedItems')
			->will($this->returnArgument(0));
		$this->replaceByMock('helper', 'eb2cinventory', $invHelper);

		$testModel = Mage::getModel('eb2cinventory/allocation');
		$this->assertInstanceOf(
			'DOMDocument',
			EcomDev_Utils_Reflection::invokeRestrictedMethod($testModel, '_buildAllocationRequestMessage', array($quote))
		);
	}
	/**
	 * Test EbayEnterprise_Eb2cInventory_Model_Allocation::_getReservationIdFromOrder method
	 * passing in a known 'sales/order' object with known data and expect the proper reservation
	 * item to be returned.
	 * @param array $orderData
	 * @param bool $isKnownReservation
	 * @param string $expected
	 * @dataProvider dataProvider
	 */
	public function testGetReservationIdFromOrder(array $orderItem, $isKnownReservation, $expected)
	{
		$allocation = Mage::getModel('eb2cinventory/allocation');
		$order = Mage::getModel('sales/order');
		if ($isKnownReservation) {
			$order->addItem(Mage::getModel('sales/order_item', $orderItem));
			$this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($allocation, '_getReservationIdFromOrder', array($order)));
		} else {
			$this->assertNotSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($allocation, '_getReservationIdFromOrder', array($order)));
		}
	}
}

<?php
class TrueAction_Eb2cInventory_Test_Model_AllocationTest
	extends TrueAction_Eb2cCore_Test_Base
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
	 * @test
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
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
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
			array(Mage::helper('eb2cinventory')->__(TrueAction_Eb2cInventory_Model_Allocation::ALLOCATION_QTY_OUT_STOCK_MESSAGE)),
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
			Mage::helper('eb2cinventory')->__(TrueAction_Eb2cInventory_Model_Allocation::ALLOCATION_QTY_LIMITED_STOCK_MESSAGE),
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
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocation()
	{
		$customerMock = $this->getModelMockBuilder('customer/customer')
			->disableOriginalConstructor()
			->setMethods(array('getGroupId'))
			->getMock();
		$customerMock->expects($this->any())
			->method('getGroupId')
			->will($this->returnValue(1));

		$customerSessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer'))
			->getMock();
		$customerSessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnValue($customerMock));
		$this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

		$checkoutSessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('singleton', 'checkout/session', $checkoutSessionMock);

		// The address to use in the quote for the shipping address
		$address = $this->_createAddressObject();

		// Group of products to assign to each item. All but one will have managed stock (test filtering)
		$products = array();
		for ($i = 0; $i < 4; $i++) {
			$products[] = Mage::getModel('catalog/product', array(
				'website_id' => 1,
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					// first three items should all be managed stock
					'manage_stock' => $i !== 3,
				)),
			));
		}

		// Items for each of the products. One of these will be a virtual product (test filtering)
		$items = array();
		foreach ($products as $idx => $product) {
			$items[] = Mage::getModel('sales/quote_item', array(
				'product' => $product,
				// third item will be virtual, rest will not be
				'is_virtual' => $idx === 2,
				'sku' => sprintf('item%s', $idx),
				'qty' => $idx + 1,
			));
		}

		// Create the quote to allocate
		$quote = Mage::getModel('sales/quote');
		$quote->setShippingAddress($address);
		$quote->setEntityId(self::QUOTE_ENTITY_ID);
		// Add each item to the quote.
		foreach ($items as $idx => $item) {
			$quote->addItem($item);
			// Give the item in id, this normally happens when saving the quote, which
			// would have happened by now, but as this is being avoided here it needs to be
			// manually assigned.
			$item->setId($idx);
		}

		// Set eb2c allocation data on the inventoried items (the first two from the provider)
		// This data should be unset when rolling back the allocation.
		$items = $quote->getAllItems();

		for ($i = 0; $i < 2; $i++) {
			$items[$i]->addData(array(
				'eb2c_reservation_id' => 'some data',
				'eb2c_reserved_at' => 'some data',
				'eb2c_qty_reserved' => 'some data',
			));
		}

		$invHelper = $this->getHelperMock('eb2cinventory/data', array('getRequestId', 'getReservationId'));
		$invHelper->expects($this->once())
			->method('getRequestId')
			->with($this->identicalTo(self::QUOTE_ENTITY_ID))
			->will($this->returnValue(self::REQUEST_ID));
		$invHelper->expects($this->once())
			->method('getReservationId')
			->with($this->identicalTo(self::QUOTE_ENTITY_ID))
			->will($this->returnValue(self::RESERVATION_ID));
		$this->replaceByMock('helper', 'eb2cinventory', $invHelper);

		// Canned response from the API. Should be what finally gets returned
		// from the allocateQuoteItems method when successful.
		$response = '<What>Ever</What>';

		$request = new DOMDocument();
		$request->loadXML(
			'<RollbackAllocationRequestMessage requestId="' .
			self::REQUEST_ID . '" reservationId="' .
			self::RESERVATION_ID .
			'" xmlns="http://api.gsicommerce.com/schema/checkout/1.0"/>'
		);

		// Mock the API to verify the request is made with the proper request
		$api = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('request'))
			->getMock();
		// Validate the request message matches the expected message
		$api->expects($this->once())
			->method('request')
			->with($this->callback(function ($arg) use ($request) {
					// compare the canonicalized XML of the TrueAction_Dom_Document
					// passed to the request method to the expected XML for this quote
					return $request->C14N() === $arg->C14N();
				}))
			->will($this->returnValue($response));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		// pre-test to ensure items that should have eb2c allocation data do
		for ($i = 0; $i < 2; $i++) {
			$this->assertSame('some data', $quote->getItemById($i)->getEb2cReservationId());
			$this->assertSame('some data', $quote->getItemById($i)->getEb2cReservedAt());
			$this->assertSame('some data', $quote->getItemById($i)->getEb2cQtyReserved());
		}
		// make sure the api response is returned when successful
		$this->assertSame(
			$response,
			$this->_allocation->rollbackAllocation($quote)
		);
		// none of the quote items should have eb2c allocation data anymore
		foreach ($quote->getAllItems() as $item) {
			$this->assertNull($item->getEb2cReservationId());
			$this->assertNull($item->getEb2cReservedAt());
			$this->assertNull($item->getEb2cQtyReserved());
		}
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

	/**
	 * verify the helper is called to translate the shipping method
	 */
	public function testBuildAllocationRequestMessageShippingMethod()
	{
		$item = $this->getModelMock('sales/quote_item', array('getQty', 'setQty'));
		$item->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1));

		$address = $this->getModelMock('sales/quote_address', array('getShippingMethod', 'getAllItems'));
		$address->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('mage_ship_method'));
		$address->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array()));

		$quote = $this->getModelMock('sales/quote', array('getAllAddresses', 'getShippingAddress'));
		$quote->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($address)));
		$quote->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($address));

		$helper = $this->getHelperMock('eb2ccore/data', array('lookupShipMethod'));
		$helper->expects($this->atLeastOnce())
			->method('lookupShipMethod')
			->with($this->identicalTo('mage_ship_method'))
			->will($this->throwException(new Mage_Core_Exception('Test Complete')));
		$this->replaceByMock('helper', 'eb2ccore', $helper);
		$this->setExpectedException('Mage_Core_Exception', 'Test Complete');

		$testModel = $this->getModelMock('eb2cinventory/allocation', array('getInventoriedItems'));
		$testModel->expects($this->once())
			->method('getInventoriedItems')
			->will($this->returnValue(array($item)));
		$this->_reflectMethod($testModel, '_buildAllocationRequestMessage')->invoke($testModel, $quote);
	}

	/**
	 * Testing _buildAllocationRequestMessage method
	 * @test
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
			->setMethods(array('getShippingMethod', 'getAllItems', 'getStreet', 'getCity', 'getRegionCode', 'getCountryId', 'getPostcode'))
			->getMock();
		$address->expects($this->once())
			->method('getShippingMethod')
			->will($this->returnValue('mage_ship_method'));
		$address->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array()));
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
			->setMethods(array('getAllAddresses', 'getShippingAddress'))
			->getMock();
		$quote->expects($this->once())
			->method('getAllAddresses')
			->will($this->returnValue(array($address)));
		$quote->expects($this->once())
			->method('getShippingAddress')
			->will($this->returnValue($address));

		$helper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('lookupShipMethod'))
			->getMock();
		$helper->expects($this->once())
			->method('lookupShipMethod')
			->with($this->identicalTo('mage_ship_method'))
			->will($this->returnValue('ANY_STD'));
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$testModel = $this->getModelMockBuilder('eb2cinventory/allocation')
			->disableOriginalConstructor()
			->setMethods(array('getInventoriedItems'))
			->getMock();
		$testModel->expects($this->once())
			->method('getInventoriedItems')
			->will($this->returnValue(array($item)));
		$this->assertInstanceOf(
			'DOMDocument',
			$this->_reflectMethod($testModel, '_buildAllocationRequestMessage')->invoke($testModel, $quote)
		);
	}
}

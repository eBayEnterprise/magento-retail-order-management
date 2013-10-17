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
	 * Provider method for a quote with 4 items - 1 non-managed stock, 1 virtual, 2 managed
	 * @return array
	 */
	public function providerQuoteWithItems()
	{
		// The address to use in the quote for the shipping address
		$address = $this->_createAddressObject();

		// Group of products to assign to each item. All but one will have managed stock (test filtering)
		$products = array();
		for ($i = 0; $i < 4; $i++) {
			$products[] = Mage::getModel('catalog/product', array(
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

		return array(
			array($quote),
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @dataProvider providerQuoteWithItems
	 */
	public function testAllocateQuoteItems($quote)
	{
		// Stub out the Eb2cInventory helper to ensure a consistent request and reservation id
		$invHelper = $this->getHelperMock('eb2cinventory/data', array('getRequestId', 'getReservationId'));
		$invHelper->expects($this->once())
			->method('getRequestId')
			// entity id set on the quote in the provider
			->with($this->identicalTo(self::QUOTE_ENTITY_ID))
			->will($this->returnValue(self::REQUEST_ID));
		$invHelper->expects($this->once())
			->method('getReservationId')
			// entity id set on the quote in the provider
			->with($this->identicalTo(self::QUOTE_ENTITY_ID))
			->will($this->returnValue(self::RESERVATION_ID));
		$this->replaceByMock('helper', 'eb2cinventory', $invHelper);

		// Canned response from the API. Should be what finally gets returned
		// from the allocateQuoteItems method when successful.
		$response = '<What>Ever</What>';

		$request = new DOMDocument();
		$request->loadXML(preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '',
			'<AllocationRequestMessage requestId="' . self::REQUEST_ID . '" reservationId="' .
			self::RESERVATION_ID . '" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
			<OrderItem itemId="item0" lineId="0">
			<Quantity>1</Quantity>
			<ShipmentDetails>
			<ShippingMethod>USPSStandard</ShippingMethod>
			<ShipToAddress>
			<Line1>One Bagshot Row</Line1>
			<City>Bag End</City>
			<MainDivision>PA</MainDivision>
			<CountryCode>US</CountryCode>
			<PostalCode>19123</PostalCode>
			</ShipToAddress>
			</ShipmentDetails>
			</OrderItem>
			<OrderItem itemId="item1" lineId="1">
			<Quantity>2</Quantity>
			<ShipmentDetails>
			<ShippingMethod>USPSStandard</ShippingMethod>
			<ShipToAddress>
			<Line1>One Bagshot Row</Line1>
			<City>Bag End</City>
			<MainDivision>PA</MainDivision>
			<CountryCode>US</CountryCode>
			<PostalCode>19123</PostalCode>
			</ShipToAddress>
			</ShipmentDetails>
			</OrderItem>
			</AllocationRequestMessage>'
		)));

		// Mock the API to verify the request is made with the proper request
		$api = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'request'))
			->getMock();
		$api->expects($this->once())
			->method('setUri')
			->will($this->returnSelf());
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

		$this->assertSame($response, $this->_allocation->allocateQuoteItems($quote));
	}

	/**
	 * testing when allocating quote item API call throw an exception
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testAllocateQuoteItemsWithApiCallException()
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

		// Avoid overly broad coverage as the only assertion in this test is that
		// API model exceptions are caught and an empty string is given as the response.
		// This will help ensure coverage relfects that.
		$allocation = $this->getModelMock('eb2cinventory/allocation', array('buildAllocationRequestMessage'));
		$allocation->expects($this->any())
			->method('buildAllocationRequestMessage')
			->will($this->returnValue(new DOMDocument()));

		// just need a quote item to pass, won't be used for anything
		$dummyQuote = Mage::getModel('sales/quote');

		$this->assertSame(
			'',
			trim($allocation->allocateQuoteItems($dummyQuote))
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
	 * @dataProvider providerQuoteWithItems
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocation($quote)
	{
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
			->setMethods(array('setUri', 'request'))
			->getMock();
		$api->expects($this->once())
			->method('setUri')
			->will($this->returnSelf());
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

	/**
	 * Testing when rolling back allocation quote item API call throw an exception.
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
		$apiModelMock->expects($this->once())
			->method('request')
			->will($this->throwException(new Mage_Core_Exception()));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		// Avoid overly broad coverage as the only assertion in this test is that
		// API model exceptions are caught and an empty string is given as the response.
		// This will help ensure coverage relfects that.
		$allocation = $this->getModelMock('eb2cinventory/allocation', array('buildRollbackAllocationRequestMessage'));
		$allocation->expects($this->any())
			->method('buildRollbackAllocationRequestMessage')
			->will($this->returnValue(new DOMDocument()));

		$this->assertSame(
			'',
			trim($allocation->rollbackAllocation($quote))
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
		$this->assertTrue($this->_allocation->hasAllocation($quote));
	}

	public function providerIsExpired()
	{
		$elapseTime = (int) Mage::getStoreConfig('eb2c/inventory/allocation_expired', null) + 15; // configure elapse time plus 15 minute more
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

}

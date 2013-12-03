<?php
class TrueAction_Eb2cInventory_Test_Model_DetailsTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_details;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_details = Mage::getModel('eb2cinventory/details');
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

	/**
	 * Provide inventory data, a quote and quote item
	 * @return array
	 */
	public function providerProcessInventoryDetails()
	{
		// create some inventory details
		$inventoryData = array();
		for ($i = 0; $i < 2; $i++) {
			$inventoryData[] = array(
				'lineId' => $i, 'creationTime' => '2010-11-02T17:47:00', 'display' => true,
				'deliveryWindow_from' => '2011-11-02T05:01:50Z', 'deliveryWindow_to' => '2011-11-02T05:01:50Z',
				'shippingWindow_from' => '2011-11-02T05:01:50Z', 'shippingWindow_to' => '2011-11-02T05:01:50Z',
				'shipFromAddress_line1' => 'Ten Bagshot Row', 'shipFromAddress_city' => 'Bag End',
				'shipFromAddress_mainDivision' => 'PA', 'shipFromAddress_countryCode' => 'US', 'shipFromAddress_postalCode' => '19123'
			);
		}

		$item = Mage::getModel('sales/quote_item');

		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getItemById', 'save'))
			->getMock();
		// need to trust that this works as expected as mocking out everything for
		// this method to work seems like an exercise in futility
		$quote->expects($this->exactly(2))
			->method('getItemById')
			->will($this->returnValueMap(
				array(array(1, $item))
			));

		return array(
			array($quote, $item, $inventoryData),
		);
	}

	/**
	 * Test the processing of inventory data for a quote. Each quote item found in the
	 * inventory data should have the inventory data added to the item via magic setters.
	 *
	 * @param  Mage_Sales_Model_Quote $quoteMock, The quote the inventory data applies to
	 * @param  Mage_Sales_Model_Quote_Item $item, The quote item included in the quote
	 * @param  array $inventoryData, Assoc array of inventory data
	 *
	 * @dataProvider providerProcessInventoryDetails
	 * @test
	 */
	public function testProcessInventoryDetails($quoteMock, $item, $inventoryData)
	{
		// this method must be called
		$quoteMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		// mock out observers that would trigger during this test
		$taxMock = $this->getModelMock('tax/observer', array());
		$this->replaceByMock('model', 'tax/observer', $taxMock);

		$this->assertSame(
			$this->_details,
			$this->_details->processInventoryDetails($quoteMock, $inventoryData)
		);
		$itemData = $inventoryData[0];
		$this->assertSame($itemData['creationTime'], $item->getEb2cCreationTime());
		$this->assertSame($itemData['display'], $item->getEb2cDisplay());
		$this->assertSame($itemData['deliveryWindow_from'], $item->getEb2cDeliveryWindowFrom());
		$this->assertSame($itemData['deliveryWindow_to'], $item->getEb2cDeliveryWindowTo());
		$this->assertSame($itemData['shippingWindow_from'], $item->getEb2cShippingWindowFrom());
		$this->assertSame($itemData['shippingWindow_to'], $item->getEb2cShippingWindowTo());
		$this->assertSame($itemData['shipFromAddress_line1'], $item->getEb2cShipFromAddressLine1());
		$this->assertSame($itemData['shipFromAddress_city'], $item->getEb2cShipFromAddressCity());
		$this->assertSame($itemData['shipFromAddress_mainDivision'], $item->getEb2cShipFromAddressMainDivision());
		$this->assertSame($itemData['shipFromAddress_countryCode'], $item->getEb2cShipFromAddressCountryCode());
		$this->assertSame($itemData['shipFromAddress_postalCode'], $item->getEb2cShipFromAddressPostalCode());
		$this->assertEventDispatched('eb2cinventory_details_process_after');
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/DetailsTest/fixtures/InventoryDetailsResponseMessage.xml', FILE_USE_INCLUDE_PATH))
		);
	}

	/**
	 * testing parseResponse
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerParseResponse
	 */
	public function testParseResponse($inventoryDetailsResponseMessage)
	{
		$this->assertSame(
			array(array('lineId' => '106',
				'itemId' => '8525 PDA',
				'creationTime' => '2010-11-02T17:47:00',
				'display' => 'true',
				'deliveryWindow_from' => '2011-11-02T05:01:50Z',
				'deliveryWindow_to' => '2011-11-02T05:01:50Z',
				'shippingWindow_from' => '2011-11-02T05:01:50Z',
				'shippingWindow_to' => '2011-11-02T05:01:50Z',
				'shipFromAddress_line1' => 'Ten Bagshot Row',
				'shipFromAddress_city' => 'Bag End',
				'shipFromAddress_mainDivision' => 'PA',
				'shipFromAddress_countryCode' => 'US',
				'shipFromAddress_postalCode' => '19123'
			)),
			$this->_details->parseResponse($inventoryDetailsResponseMessage)
		);
	}

	/**
	 * Create a quote and items that will create a given request message
	 * @return array Args to the testGetInventoryDetailsQuote method, a quote and DOMDocument request should match.
	 */
	public function providerInventoryDetailsQuote()
	{
		$address = $this->_createAddressObject();

		$products = array();
		for ($i = 0; $i < 4; $i++) {
			$products[] = Mage::getModel('catalog/product', array(
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					// first three items should all be managed stock
					'manage_stock' => $i !== 3,
				)),
			));
		}

		$items = array();
		foreach ($products as $idx => $product) {
			$items[] = Mage::getModel('sales/quote_item', array(
				'product' => $product,
				// first, second and fourth should be non-virtual, third should be
				'is_virtual' => $idx === 2,
				'sku' => sprintf('item%s', $idx),
				'qty' => $idx + 1,
			));
		}

		$quote = Mage::getModel('sales/quote');
		$quote->setShippingAddress($address);
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
	 * Test getting inventory details
	 * @param Mage_Sales_Model_Quote $quote
	 * @dataProvider providerInventoryDetailsQuote
	 * @loadFixture
	 * @test
	 */
	public function testGetInventoryDetails($quote)
	{
		$request = new DOMDocument();
		$request->loadXML('<InventoryDetailsRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><OrderItem itemId="item0" lineId="0"><Quantity>1</Quantity><ShipmentDetails><ShippingMethod>USPSStandard</ShippingMethod><ShipToAddress><Line1>One Bagshot Row</Line1><City>Bag End</City><MainDivision>PA</MainDivision><CountryCode>US</CountryCode><PostalCode>19123</PostalCode></ShipToAddress></ShipmentDetails></OrderItem><OrderItem itemId="item1" lineId="1"><Quantity>2</Quantity><ShipmentDetails><ShippingMethod>USPSStandard</ShippingMethod><ShipToAddress><Line1>One Bagshot Row</Line1><City>Bag End</City><MainDivision>PA</MainDivision><CountryCode>US</CountryCode><PostalCode>19123</PostalCode></ShipToAddress></ShipmentDetails></OrderItem></InventoryDetailsRequestMessage>');
		$response = '<What>Ever</What>';
		$api = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'request'))
			->getMock();
		$api->expects($this->once())
			->method('setUri')
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->with($this->callback(function ($arg) use ($request) {
					// compare the canonicalized XML of the TrueAction_Dom_Document
					// passed to the request method to the expected XML for this quote
					if ($request->C14N() === $arg->C14N()) {
						return true;
					}
					echo $request->C14N() . "\n";
					echo $arg->C14N() . "\n";
					return false;
				}))
			->will($this->returnValue($response));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$this->assertSame($response, $this->_details->getInventoryDetails($quote));
	}

	/**
	 * When the API model throws an exception, it should be caught and an empty string returned.
	 * @test
	 */
	public function testGetInventoryDetailsApiException()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session', array('addError'))
			->disableOriginalConstructor()
			->getMock();
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);

		$api = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'request'))
			->getMock();
		$api->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$api->expects($this->any())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception()));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$address = $this->_createAddressObject();

		$products = array();
		for ($i = 0; $i < 4; $i++) {
			$products[] = Mage::getModel('catalog/product', array(
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					// first three items should all be managed stock
					'manage_stock' => $i !== 3,
				)),
			));
		}

		$items = array();
		foreach ($products as $idx => $product) {
			$items[] = Mage::getModel('sales/quote_item', array(
				'product' => $product,
				// first, second and fourth should be non-virtual, third should be
				'is_virtual' => $idx === 2,
				'sku' => sprintf('item%s', $idx),
				'qty' => $idx + 1,
			));
		}

		$quote = Mage::getModel('sales/quote');
		$quote->setShippingAddress($address);
		// Add each item to the quote.
		foreach ($items as $idx => $item) {
			$quote->addItem($item);
			// Give the item in id, this normally happens when saving the quote, which
			// would have happened by now, but as this is being avoided here it needs to be
			// manually assigned.
			$item->setId($idx);
		}

		$detailsMock = $this->getModelMock('eb2cinventory/details', array('_buildInventoryDetailsRequestMessage'));
		$detailsMock->expects($this->any())
			->method('buildInventoryDetailsRequestMessage')
			->will($this->returnValue(''));
		$this->assertSame('', $this->_details->getInventoryDetails($quote));
	}
	/**
	 * Test that buildInventoryDetailsRequestMessage takes a quote,
	 * passes the order items and address in the quote downstream
	 * and returns a DOMDocument.
	 * @test
	 */
	public function testBuildInventoryDetailsRequestMessage()
	{
		$quote = $this->getModelMock('sales/quote', array('getAllItems', 'getShippingAddress'));
		$quote
			->expects($this->once())
			->method('getAllItems')
			->will($this->returnValue(array()));
		$quote
			->expects($this->once())
			->method('getShippingAddress')
			->will($this->returnValue(Mage::getModel('sales/quote_address')));
		$deets = $this->getModelMock('eb2cinventory/details', array('_buildOrderItemsXml', 'getInventoriedItems'));
		$deets
			->expects($this->once())
			->method('_buildOrderItemsXml')
			->with($this->isType('array'), $this->isInstanceOf('Mage_Sales_Model_Quote_Address'))
			->will($this->returnValue('<OrderItemStub></OrderItemStub>'));
		$deets
			->expects($this->once())
			->method('getInventoriedItems')
			->with($this->isType('array'))
			->will($this->returnValue(array()));
		$invDeetReqMsg = $this->_invokeProt($deets, '_buildInventoryDetailsRequestMessage', array($quote));
		$this->assertInstanceOf('DOMDocument', $invDeetReqMsg);
		$this->assertSame(
			'<InventoryDetailsRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><OrderItemStub></OrderItemStub></InventoryDetailsRequestMessage>',
			$invDeetReqMsg->C14N()
		);
	}
	/**
	 * Test that buildOrderItemsXml takes an array of quote items
	 * and a quote address and concatenates the result of calling testBuildOrderItemXml
	 * on each of them.
	 * @test
	 */
	public function testBuildOrderItemsXml()
	{
		$deets = $this->getModelMock('eb2cinventory/details', array('_buildOrderItemXml', '_buildShipmentDetailsXml'));
		$deets
			->expects($this->once())
			->method('_buildShipmentDetailsXml')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Address'))
			->will($this->returnValue('<ShipStub/>'));
		$deets
			->expects($this->atLeastOnce())
			->method('_buildOrderItemXml')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Item'), $this->equalTo('<ShipStub/>'))
			->will($this->returnValue('<OrderItemStub/>'));
		$this->assertSame('<OrderItemStub/>', $this->_invokeProt($deets, '_buildOrderItemsXml', array(
			array(Mage::getModel('sales/quote_item')),
			Mage::getModel('sales/quote_address')
		)));
	}
	/**
	 * Test that buildOrderItemXml takes a quote item and shipment details string,
	 * extracts the expected values from the item and returns a string.
	 * @test
	 */
	public function testBuildOrderItemXml()
	{
		$item = $this->getModelMock('sales/quote_item', array('getId', 'getSku', 'getQty'));
		$item
			->expects($this->once())
			->method('getId')
			->will($this->returnValue('id'));
		$item
			->expects($this->once())
			->method('getSku')
			->will($this->returnValue('sku'));
		$item
			->expects($this->once())
			->method('getQty')
			->will($this->returnValue(1));
		$deets = Mage::getModel('eb2cinventory/details');
		$this->assertSame(
			'<OrderItem lineId="id" itemId="sku"><Quantity>1</Quantity><ShipmentDetails/></OrderItem>',
			$this->_invokeProt($deets, '_buildOrderItemXml', array($item, '<ShipmentDetails/>'))
		);
	}
	/**
	 * Test that buildShipmentDetailsXml takes a quote address and returns a string.
	 * @test
	 */
	public function testBuildShipmentDetailsXml()
	{
		$helper = $this->getHelperMock('eb2ccore/data', array('lookupShipMethod'));
		$helper
			->expects($this->once())
			->method('lookupShipMethod')
			->with($this->equalTo('method'))
			->will($this->returnValue('mapped'));
		$this->replaceByMock('helper', 'eb2ccore', $helper);
		$address = $this->getModelMock(
			'sales/quote_address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegionCode', 'getCountryId', 'getPostcode')
		);
		$address
			->expects($this->once())
			->method('getShippingMethod')
			->will($this->returnValue('method'));
		$address
			->expects($this->atLeastOnce())
			->method('getStreet')
			->with($this->isType('int'))
			->will($this->returnValue('street'));
		$address
			->expects($this->once())
			->method('getCity')
			->will($this->returnValue('city'));
		$address
			->expects($this->once())
			->method('getRegionCode')
			->will($this->returnValue('state'));
		$address
			->expects($this->once())
			->method('getCountryId')
			->will($this->returnValue('country'));
		$address
			->expects($this->once())
			->method('getPostcode')
			->will($this->returnValue('zip'));
		$deets = Mage::getModel('eb2cinventory/details');
		$this->assertSame(
			'<ShipmentDetails><ShippingMethod>mapped</ShippingMethod><ShipToAddress><Line1>street</Line1><Line2>street</Line2><Line3>street</Line3><Line4>street</Line4><City>city</City><MainDivision>state</MainDivision><CountryCode>country</CountryCode><PostalCode>zip</PostalCode></ShipToAddress></ShipmentDetails>',
			$this->_invokeProt($deets, '_buildShipmentDetailsXml', array($address))
		);
	}
	/**
	 * Invoke a protected method and return the results.
	 * @param mixed $obj the object that has the method
	 * @param string $meth the method name to invoke
	 * @param array $args the arguments to pass
	 * @return mixed
	 */
	protected function _invokeProt($obj, $meth, $args)
	{
		$ref = new ReflectionObject($obj);
		$prot = $ref->getMethod($meth);
		$prot->setAccessible(true);
		return $prot->invokeArgs($obj, $args);
	}
}


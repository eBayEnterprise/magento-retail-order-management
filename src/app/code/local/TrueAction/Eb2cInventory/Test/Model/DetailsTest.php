<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_DetailsTest
	extends TrueAction_Eb2cCore_Test_Base
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
	 * @param  Mage_Sales_Model_Quote $quote
	 * @param  string $request The request XML that should be created for the given quote
	 *
	 * @dataProvider providerInventoryDetailsQuote
	 * @test
	 */
	public function testGetInventoryDetails($quote)
	{
		$request = new DOMDocument();
		$request->loadXML(preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '',
			'<InventoryDetailsRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
			<OrderItem itemId="item0" lineId="0">
			<Quantity><![CDATA[1]]></Quantity>
			<ShipmentDetails>
			<ShippingMethod><![CDATA[USPSStandard]]></ShippingMethod>
			<ShipToAddress>
			<Line1><![CDATA[One Bagshot Row]]></Line1>
			<City><![CDATA[Bag End]]></City>
			<MainDivision><![CDATA[PA]]></MainDivision>
			<CountryCode><![CDATA[US]]></CountryCode>
			<PostalCode><![CDATA[19123]]></PostalCode>
			</ShipToAddress>
			</ShipmentDetails>
			</OrderItem>
			<OrderItem itemId="item1" lineId="1">
			<Quantity><![CDATA[2]]></Quantity>
			<ShipmentDetails>
			<ShippingMethod><![CDATA[USPSStandard]]></ShippingMethod>
			<ShipToAddress>
			<Line1><![CDATA[One Bagshot Row]]></Line1>
			<City><![CDATA[Bag End]]></City>
			<MainDivision><![CDATA[PA]]></MainDivision>
			<CountryCode><![CDATA[US]]></CountryCode>
			<PostalCode><![CDATA[19123]]></PostalCode>
			</ShipToAddress>
			</ShipmentDetails>
			</OrderItem>
			</InventoryDetailsRequestMessage>'
		)));
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
					return $request->C14N() === $arg->C14N();
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

		$detailsMock = $this->getModelMock('eb2cinventory/details', array('buildInventoryDetailsRequestMessage'));
		$detailsMock->expects($this->any())
			->method('buildInventoryDetailsRequestMessage')
			->will($this->returnValue(''));
		$this->assertSame('', $this->_details->getInventoryDetails($quote));
	}
}

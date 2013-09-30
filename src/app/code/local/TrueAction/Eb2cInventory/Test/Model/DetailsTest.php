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
			->will($this->returnValue(array('1938 Some Street'))
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
			array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'getProduct', 'getIsVirtual')
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
			->method('getProduct')
			->will($this->returnValue($productMock)
			);
		$itemMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue(false)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save')
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
			->will($this->returnValue(1)
			);

		return $quoteMock;
	}

	// public function providerGetInventoryDetails()
	// {
	// 	return array(
	// 		array($this->buildQuoteMock())
	// 	);
	// }

	// /**
	//  * testing getting inventory details
	//  *
	//  * @test
	//  * @loadFixture loadConfig.yaml
	//  * @dataProvider providerGetInventoryDetails
	//  */
	// public function testGetInventoryDetails($quote)
	// {
	// 	// testing when you can allocated inventory
	// 	$this->assertNotNull(
	// 		$this->_details->getInventoryDetails($quote)
	// 	);
	// }

	// /**
	//  * testing when getting inventory details API call throw an exception
	//  *
	//  * @test
	//  * @loadFixture loadConfig.yaml
	//  * @dataProvider providerGetInventoryDetails
	//  */
	// public function testGetInventoryDetailsWithApiCallException($quote)
	// {
	// 	$apiModelMock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
	// 	$apiModelMock->expects($this->any())
	// 		->method('setUri')
	// 		->will($this->returnSelf());
	// 	$apiModelMock->expects($this->any())
	// 		->method('request')
	// 		->will($this->throwException(new Exception));
	// 	$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

	// 	$this->assertSame(
	// 		'',
	// 		trim($this->_details->getInventoryDetails($quote))
	// 	);
	// }

	// public function providerBuildInventoryDetailsRequestMessage()
	// {
	// 	return array(
	// 		array($this->buildQuoteMock())
	// 	);
	// }

	// /**
	//  * testing building inventory details request message
	//  *
	//  * @test
	//  * @loadFixture loadConfig.yaml
	//  * @dataProvider providerBuildInventoryDetailsRequestMessage
	//  */
	// public function testBuildInventoryDetailsRequestMessage($quote)
	// {
	// 	// testing when you can allocated inventory
	// 	$this->assertNotNull(
	// 		$this->_details->buildInventoryDetailsRequestMessage($quote)
	// 	);
	// }

	// public function providerBuildInventoryDetailsRequestMessageWithException()
	// {
	// 	$addressMock = $this->getMock(
	// 		'Mage_Sales_Model_Quote_Address',
	// 		array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode')
	// 	);
	// 	$addressMock->expects($this->any())
	// 		->method('getShippingMethod')
	// 		->will($this->returnValue('USPS: 3 Day Select')
	// 		);
	// 	$addressMock->expects($this->any())
	// 		->method('getStreet')
	// 		->will($this->returnValue(array('1938 Some Street'))
	// 		);
	// 	$addressMock->expects($this->any())
	// 		->method('getCity')
	// 		->will($this->returnValue('King of Prussia')
	// 		);
	// 	$addressMock->expects($this->any())
	// 		->method('getRegion')
	// 		->will($this->returnValue('Pennsylvania')
	// 		);
	// 	$addressMock->expects($this->any())
	// 		->method('getCountryId')
	// 		->will($this->returnValue('US')
	// 		);
	// 	$addressMock->expects($this->any())
	// 		->method('getPostcode')
	// 		->will($this->returnValue('19726')
	// 		);

	// 	$stockItemMock = $this->getMock(
	// 		'Mage_CatalogInventory_Model_Stock_Item',
	// 		array('getManageStock')
	// 	);

	// 	$stockItemMock->expects($this->any())
	// 		->method('getManageStock')
	// 		->will($this->returnValue(true)
	// 		);

	// 	$productMock = $this->getMock(
	// 		'Mage_Catalog_Model_Product',
	// 		array('getStockItem')
	// 	);

	// 	$productMock->expects($this->any())
	// 		->method('getStockItem')
	// 		->will($this->returnValue($stockItemMock)
	// 		);

	// 	$itemMock = $this->getMock(
	// 		'Mage_Sales_Model_Quote_Item',
	// 		array('getQty', 'getId', 'getSku', 'getItemId', 'getProduct', 'getIsVirtual')
	// 	);
	// 	$itemMock->expects($this->any())
	// 		->method('getQty')
	// 		->will($this->returnValue(1)
	// 		);
	// 	$itemMock->expects($this->any())
	// 		->method('getId')
	// 		->will($this->returnValue(1)
	// 		);
	// 	$itemMock->expects($this->any())
	// 		->method('getSku')
	// 		->will($this->returnValue($this->throwException(new Exception)));
	// 	$itemMock->expects($this->any())
	// 		->method('getItemId')
	// 		->will($this->returnValue(1)
	// 		);
	// 	$itemMock->expects($this->any())
	// 		->method('getProduct')
	// 		->will($this->returnValue($productMock)
	// 		);
	// 	$itemMock->expects($this->any())
	// 		->method('getIsVirtual')
	// 		->will($this->returnValue(false)
	// 		);

	// 	$quoteMock = $this->getMock(
	// 		'Mage_Sales_Model_Quote',
	// 		array('getAllItems', 'getShippingAddress', 'getItemById', 'save')
	// 	);
	// 	$quoteMock->expects($this->any())
	// 		->method('getAllItems')
	// 		->will($this->returnValue(array($itemMock))
	// 		);
	// 	$quoteMock->expects($this->any())
	// 		->method('getShippingAddress')
	// 		->will($this->returnValue($addressMock)
	// 		);
	// 	$quoteMock->expects($this->any())
	// 		->method('getItemById')
	// 		->will($this->returnValue($itemMock)
	// 		);
	// 	$quoteMock->expects($this->any())
	// 		->method('save')
	// 		->will($this->returnValue(1)
	// 		);

	// 	return array(
	// 		array($quoteMock)
	// 	);
	// }

	// /**
	//  * testing building inventory details request message
	//  *
	//  * @test
	//  * @loadFixture loadConfig.yaml
	//  * @dataProvider providerBuildInventoryDetailsRequestMessageWithException
	//  */
	// public function testBuildInventoryDetailsRequestMessageWithException($quote)
	// {
	// 	// testing when building the inventory details message throw an exception
	// 	$this->assertNotNull(
	// 		$this->_details->buildInventoryDetailsRequestMessage($quote)
	// 	);
	// }

	public function providerProcessInventoryDetails()
	{
		$inventoryData = array(
			array(
				'lineId' => 1,
				'creationTime' => '2010-11-02T17:47:00',
				'display' => true,
				'deliveryWindow_from' => '2011-11-02T05:01:50Z',
				'deliveryWindow_to' => '2011-11-02T05:01:50Z',
				'shippingWindow_from' => '2011-11-02T05:01:50Z',
				'shippingWindow_to' => '2011-11-02T05:01:50Z',
				'shipFromAddress_line1' => 'Ten Bagshot Row',
				'shipFromAddress_city' => 'Bag End',
				'shipFromAddress_mainDivision' => 'PA',
				'shipFromAddress_countryCode' => 'US',
				'shipFromAddress_postalCode' => '19123'
			)

		);

		return array(
			array($this->buildQuoteMock(), $inventoryData)
		);
	}

	/**
	 * testing processing inventory details data
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerProcessInventoryDetails
	 */
	public function testProcessInventoryDetails($quote, $inventoryData)
	{
		$this->assertNull(
			$this->_details->processInventoryDetails($quote, $inventoryData)
		);
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
	 * @return array
	 */
	public function providerInventoryDetailsQuote()
	{
		$address = $this->_createAddressObject();

		$products = array();
		for ($i = 0; $i < 4; $i++) {
			$products[] = Mage::getModel('catalog/product', array(
				'stock_item' => Mage::getModel('cataloginventory/stock_item', array(
					// first three items should all be managed stock
					'manage_stock' => !($i > 0 && $i % 3 === 0),
				)),
			));
		}

		$items = array();
		foreach ($products as $idx => $product) {
			$items[] = Mage::getModel('sales/quote_item', array(
				'product' => $product,
				// first, second and fourth should be non-virtual, third should be
				'is_virtual' => ($idx > 0 && $idx % 2 === 0),
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
		// This should be moved to an expectation.
		$request = new DOMDocument();
		$request->loadXML('<InventoryDetailsRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><OrderItem itemId="item0" lineId="0"><Quantity><![CDATA[1]]></Quantity><ShipmentDetails><ShippingMethod><![CDATA[USPSStandard]]></ShippingMethod><ShipToAddress><Line1><![CDATA[One Bagshot Row]]></Line1><City><![CDATA[Bag End]]></City><MainDivision><![CDATA[PA]]></MainDivision><CountryCode><![CDATA[US]]></CountryCode><PostalCode><![CDATA[19123]]></PostalCode></ShipToAddress></ShipmentDetails></OrderItem><OrderItem itemId="item1" lineId="1"><Quantity><![CDATA[2]]></Quantity><ShipmentDetails><ShippingMethod><![CDATA[USPSStandard]]></ShippingMethod><ShipToAddress><Line1><![CDATA[One Bagshot Row]]></Line1><City><![CDATA[Bag End]]></City><MainDivision><![CDATA[PA]]></MainDivision><CountryCode><![CDATA[US]]></CountryCode><PostalCode><![CDATA[19123]]></PostalCode></ShipToAddress></ShipmentDetails></OrderItem></InventoryDetailsRequestMessage>');
		return array(
			array($quote, $request),
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
	public function testGetInventoryDetails($quote, $request)
	{
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

}

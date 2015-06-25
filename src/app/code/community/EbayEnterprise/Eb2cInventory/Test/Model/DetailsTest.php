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

class EbayEnterprise_Eb2cInventory_Test_Model_DetailsTest
	extends EbayEnterprise_Eb2cCore_Test_Base
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

	/*****************************************************************************
	 * Request methods                                                           *
	 ****************************************************************************/

	/**
	 * Data provider for the canMakeRequestWithQuote tests. Provide the items a quote contains,
	 * the quote shipping address and whether is should be possible to send a request for a quote
	 * containing the provided data.
	 * @return array Arg arrays containing an array or Mage_Sales_Model_Quote_Items, a Mage_Sales_Model_Quote_Address and a boolean
	 */
	public function providerCanMakeRequestWithQuote()
	{
		$item = $this->getModelMock('sales/quote_item');
		$addressNoMethod = $this->getModelMock('sales/quote_address', array('getShippingMethod'));
		$addressWithMethod = $this->getModelMock('sales/quote_address', array('getShippingMethod'));

		$addressNoMethod
			->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue(false));

		$addressWithMethod
			->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue(true));
		return array(
			array(array(), null, false),
			array(array($item), null, false),
			array(array($item), $addressNoMethod, false),
			array(array($item), $addressWithMethod, true),
		);
	}
	/**
	 * Test checking if a request could be made with a quote. Quote is expected to have
	 * one or more items and a shipping address with a shipping method.
	 * @param Mage_Sales_Model_Quote_Item[]  $items          Quote items in the quote
	 * @param Mage_Sales_Model_Quote_Address $address        Shipping address for the quote
	 * @param bool                        $canMakeRequest Can a request be made for a quote with the provided items and address
	 * @dataProvider providerCanMakeRequestWithQuote
	 */
	public function testCanMakeRequestWithQuote($items, $address, $canMakeRequest)
	{
		$quote = $this->getModelMock('sales/quote', array('getAllItems', 'getShippingAddress'));
		$helper = $this->getHelperMock('eb2cinventory/data', array('getInventoriedItems'));

		$this->replaceByMock('helper', 'eb2cinventory', $helper);

		$quote
			->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($items));
		$quote
			->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($address));

		$helper
			->expects($this->any())
			->method('getInventoriedItems')
			->will($this->returnArgument(0));

		$details = Mage::getModel('eb2cinventory/details');
		$this->assertSame($canMakeRequest, EcomDev_Utils_Reflection::invokeRestrictedMethod($details, '_canMakeRequestWithQuote', array($quote)));
	}
	/**
	 * Test that buildInventoryDetailsRequestMessage takes a quote,
	 * passes the order items and address in the quote downstream
	 * and returns a DOMDocument.
	 */
	public function testBuildInventoryDetailsRequestMessage()
	{
		$quote = $this->getModelMock('sales/quote', array('getAllVisibleItems', 'getShippingAddress'));
		$quote
			->expects($this->once())
			->method('getAllVisibleItems')
			->will($this->returnValue(array()));
		$quote
			->expects($this->once())
			->method('getShippingAddress')
			->will($this->returnValue(Mage::getModel('sales/quote_address')));

		$helper = $this->getHelperMock('eb2cinventory/data', array('getInventoriedItems'));
		$helper
			->expects($this->once())
			->method('getInventoriedItems')
			->with($this->isType('array'))
			->will($this->returnValue(array()));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);
		$deets = $this->getModelMock('eb2cinventory/details', array('_buildOrderItemsXml'));
		$deets
			->expects($this->once())
			->method('_buildOrderItemsXml')
			->with($this->isType('array'), $this->isInstanceOf('Mage_Sales_Model_Quote_Address'))
			->will($this->returnValue('<OrderItemStub></OrderItemStub>'));

		$invDeetReqMsg = EcomDev_Utils_Reflection::invokeRestrictedMethod($deets, '_buildRequestMessage', array($quote));
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
	 */
	public function testBuildOrderItemsXml()
	{
		$itemOne = Mage::getModel('sales/quote_item');
		$itemTwo = Mage::getModel('sales/quote_item');
		$xmlOne = '<OrderItemStub itemId="0"/>';
		$xmlTwo = '<OrderItemStub itemId="1"/>';
		$itemXmlMap = array(
			array($itemOne, '<ShipStub/>', 0, $xmlOne),
			array($itemTwo, '<ShipStub/>', 1, $xmlTwo),
		);
		$deets = $this->getModelMock('eb2cinventory/details', array('_buildOrderItemXml', '_buildShipmentDetailsXml'));
		$deets
			->expects($this->exactly(2))
			->method('_buildShipmentDetailsXml')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Address'))
			->will($this->returnValue('<ShipStub/>'));
		$deets
			->expects($this->exactly(2))
			->method('_buildOrderItemXml')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Item'), $this->equalTo('<ShipStub/>'), $this->greaterThanOrEqual(0))
			->will($this->returnValueMap($itemXmlMap));
		$this->assertSame($xmlOne . $xmlTwo, EcomDev_Utils_Reflection::invokeRestrictedMethod($deets, '_buildOrderItemsXml', array(
			array($itemOne, $itemTwo),
			Mage::getModel('sales/quote_address')
		)));
	}
	/**
	 * Test that buildOrderItemXml takes a quote item and shipment details string,
	 * extracts the expected values from the item and returns a string.
	 */
	public function testBuildOrderItemXml()
	{
		$idx = 0;
		$sku = 'sku';
		$qty = 1;
		$shipmentXml = '<ShipmentDetails/>';
		$item = Mage::getModel('sales/quote_item', array('sku' => $sku, 'qty' => $qty));

		$deets = Mage::getModel('eb2cinventory/details');
		$results = sprintf(
			EbayEnterprise_Eb2cInventory_Model_Details::ORDER_ITEM_XML_TEMPLATE,
			$idx, $sku, $qty, $shipmentXml
		);
		$this->assertSame($results, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$deets, '_buildOrderItemXml', array($item, $shipmentXml, $idx)
		));
	}
	/**
	 * Test that buildShipmentDetailsXml takes a quote address and returns a string.
	 */
	public function testBuildShipmentDetailsXml()
	{
		$shippingMethod = 'method';
		$translateMethod = 'mapped';
		$lines = array('street 1', 'street 2', 'street 3', 'street 4');
		$city = 'city';
		$state = 'state';
		$country = 'country';
		$zipCode = 'zip';

		$helper = $this->getHelperMock('eb2ccore/shipping', array('getMethodSdkId'));
		$helper->expects($this->once())
			->method('getMethodSdkId')
			->with($this->equalTo($shippingMethod))
			->will($this->returnValue($translateMethod));
		$this->replaceByMock('helper', 'eb2ccore/shipping', $helper);

		$address = Mage::getModel('sales/quote_address', array(
			'shipping_method' => $shippingMethod,
			'street' => $lines,
			'city' => $city,
			'region_code' => $state,
			'country_id' => $country,
			'postcode' => $zipCode
		));

		$deets = Mage::getModel('eb2cinventory/details');

		$streetLines = array_reduce(array_keys($lines), function ($result, $i) use ($lines){
			$result .= sprintf(EbayEnterprise_Eb2cInventory_Model_Details::LINE_TEMPLATE, $i+1, $lines[$i]);
			return $result;
		});
		$results = sprintf(
			EbayEnterprise_Eb2cInventory_Model_Details::SHIPMENT_DETAILS_XML_TEMPLATE,
			$translateMethod, $streetLines, $city, $state, $country, $zipCode
		);

		$this->assertSame($results, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$deets, '_buildShipmentDetailsXml', array($address)
		));
	}

	/*****************************************************************************
	 * Response methods                                                          *
	 ****************************************************************************/

	/**
	 * Test extracting details data from the details response message, this mainly consists
	 * of extracting values from DOMNodeLists looked up using XPath expressions.
	 */
	public function testExtractItemDetails()
	{
		$helper = $this->getHelperMock('eb2ccore/data', array('extractNodeVal'));
		$this->replaceByMock('helper', 'eb2ccore', $helper);
		$xpath = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(array('query'))
			->getMock();
		// a single InventoryDetail node extracted from the response
		$detailNode = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute'))
			->getMock();
		// use an array as the mock DOMNodeList - just loop over the items, so the array will do here
		// not optimal but I can't figure out how to mock a DOMNodeList to work here
		$inventoryDetails = array($detailNode);

		// a single delivery estimate node extracted from the response
		$delEstNode = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute'))
			->getMock();

		// mock a DOMNodeList to return the delivery estimate node.
		$delEstNodeList = $this->getMockBuilder('DOMNodeList')
			->disableOriginalConstructor()
			->setMethods(array('item'))
			->getMock();
		$delEstNodeList->expects($this->atLeastOnce())
			->method('item')
			->with($this->identicalTo(0))
			->will($this->returnValue($delEstNode));

		// a single ShipFromAddress node extracted from the response
		$shipFromNode = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute'))
			->getMock();

		// mock a DOMNodeList to return the delivery estimate node.
		$shipFromAddress = $this->getMockBuilder('DOMNodeList')
			->disableOriginalConstructor()
			->setMethods(array('item'))
			->getMock();
		$shipFromAddress->expects($this->atLeastOnce())
			->method('item')
			->with($this->identicalTo(0))
			->will($this->returnValue($shipFromNode));

		// returnValueMap to the getAttribute calls on the InventoryDetail node
		$detailNodeAttributes = array(
			array('itemId', 'item-sku'),
			array('lineId', 'line-id'),
		);
		// these are all of the expected values that should be pulled out of the InventoryDetail node
		$nodeValues = array(
			'lineId' => 'line-id',
			'itemId' => 'item-sku',
			'creationTime' => 'value-creationTime',
			'display' => 'value-display',
			'deliveryWindow_from' => 'value-deliveryWindow_from',
			'deliveryWindow_to' => 'value-deliveryWindow_to',
			'shippingWindow_from' => 'value-shippingWindow_from',
			'shippingWindow_to' => 'value-shippingWindow_to',
			'shipFromAddress_line1' => 'value-shipFromAddress_line1',
			'shipFromAddress_city' => 'value-shipFromAddress_city',
			'shipFromAddress_mainDivision' => 'value-shipFromAddress_mainDivision',
			'shipFromAddress_countryCode' => 'value-shipFromAddress_countryCode',
			'shipFromAddress_postalCode' => 'value-shipFromAddress_postalCode',
		);
		// collection of node lists that should be extracted from the InventoryDetail node
		$nodeLists = array(
			'creationTime' => $this->getMock('DOMNodeList'),
			'display' => $this->getMock('DOMNodeList'),
			'deliveryWindow_from' => $this->getMock('DOMNodeList'),
			'deliveryWindow_to' => $this->getMock('DOMNodeList'),
			'shippingWindow_from' => $this->getMock('DOMNodeList'),
			'shippingWindow_to' => $this->getMock('DOMNodeList'),
			'shipFromAddress_line1' => $this->getMock('DOMNodeList'),
			'shipFromAddress_city' => $this->getMock('DOMNodeList'),
			'shipFromAddress_mainDivision' => $this->getMock('DOMNodeList'),
			'shipFromAddress_countryCode' => $this->getMock('DOMNodeList'),
			'shipFromAddress_postalCode' => $this->getMock('DOMNodeList'),
		);
		// returnValueMap for the xpath queries
		$xpathValueMap = array(
			// this query is from the root to return all of the InventoryDetail nodes
			array('//a:InventoryDetail', null, null, $inventoryDetails),
			// query to get the delivery estimate and ship from adress nodes
			array('a:DeliveryEstimate[1]', $detailNode, null, $delEstNodeList),
			array('a:ShipFromAddress[1]', $detailNode, null, $shipFromAddress),
			// rest of the queries should all be relative to a InventoryDetail node, passed as context in second arg
			array('a:CreationTime', $delEstNode, null, $nodeLists['creationTime']),
			array('a:Display', $delEstNode, null, $nodeLists['display']),
			array('a:DeliveryWindow/a:From', $delEstNode, null, $nodeLists['deliveryWindow_from']),
			array('a:DeliveryWindow/a:To', $delEstNode, null, $nodeLists['deliveryWindow_to']),
			array('a:ShippingWindow/a:From', $delEstNode, null, $nodeLists['shippingWindow_from']),
			array('a:ShippingWindow/a:To', $delEstNode, null, $nodeLists['shippingWindow_to']),
			array('a:Line1', $shipFromNode, null, $nodeLists['shipFromAddress_line1']),
			array('a:City', $shipFromNode, null, $nodeLists['shipFromAddress_city']),
			array('a:MainDivision', $shipFromNode, null, $nodeLists['shipFromAddress_mainDivision']),
			array('a:CountryCode', $shipFromNode, null, $nodeLists['shipFromAddress_countryCode']),
			array('a:PostalCode', $shipFromNode, null, $nodeLists['shipFromAddress_postalCode']),
		);
		// returnValueMap for the helper extractNodeVal, when passed the given nodeList, should return the given value
		$nodeValMap = array(
			array($nodeLists['creationTime'], $nodeValues['creationTime']),
			array($nodeLists['display'], $nodeValues['display']),
			array($nodeLists['deliveryWindow_from'], $nodeValues['deliveryWindow_from']),
			array($nodeLists['deliveryWindow_to'], $nodeValues['deliveryWindow_to']),
			array($nodeLists['shippingWindow_from'], $nodeValues['shippingWindow_from']),
			array($nodeLists['shippingWindow_to'], $nodeValues['shippingWindow_to']),
			array($nodeLists['shipFromAddress_line1'], $nodeValues['shipFromAddress_line1']),
			array($nodeLists['shipFromAddress_city'], $nodeValues['shipFromAddress_city']),
			array($nodeLists['shipFromAddress_mainDivision'], $nodeValues['shipFromAddress_mainDivision']),
			array($nodeLists['shipFromAddress_countryCode'], $nodeValues['shipFromAddress_countryCode']),
			array($nodeLists['shipFromAddress_postalCode'], $nodeValues['shipFromAddress_postalCode']),
		);
		$detailNode
			->expects($this->any())
			->method('getAttribute')
			->will($this->returnValueMap($detailNodeAttributes));
		$xpath
			->expects($this->any())
			->method('query')
			->will($this->returnValueMap($xpathValueMap));
		$helper
			->expects($this->any())
			->method('extractNodeVal')
			->will($this->returnValueMap($nodeValMap));

		// expect an array of sku => details
		$this->assertSame(
			array('item-sku' => $nodeValues),
			Mage::getModel('eb2cinventory/details')->extractItemDetails($xpath)
		);
	}
	/**
	 * Test extracting unavailable items from the inventory details response. Should
	 * get all UnavailableItem nodes via XPath and iterate over the DOMNodeList pulling
	 * the itemId and lineId attributes from each node. Should result in an array
	 * of sku => item info (sku and line id)
	 */
	public function testExtractUnavailableItems()
	{
		$xpath = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(array('query'))
			->getMock();
		// a single InventoryDetail node extracted from the response
		$unavailNode = $this->getMockBuilder('DOMNode')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute'))
			->getMock();
		// use an array as the mock DOMNodeList - just loop over the items, so the array will do here
		// not optimal but I can't figure out how to mock a DOMNodeList to work here
		$unavailNodeList = array($unavailNode);

		$xpathValueMap = array(
			array('//a:UnavailableItem', null, null, $unavailNodeList),
		);
		$attributeValueMap = array(
			array('itemId', 'item-sku'),
			array('lineId', 'line-id'),
		);

		$xpath
			->expects($this->any())
			->method('query')
			->will($this->returnValueMap($xpathValueMap));
		$unavailNode
			->expects($this->any())
			->method('getAttribute')
			->will($this->returnValueMap($attributeValueMap));

		$this->assertSame(
			array('item-sku' => array('lineId' => 'line-id')),
			Mage::getModel('eb2cinventory/details')->extractUnavailableItems($xpath)
		);
	}
	protected function _mockQuoteItemWithSku($sku)
	{
		$item = $this->getModelMock('sales/quote_item', array('getSku'));
		$item->expects($this->any())->method('getSku')->will($this->returnValue($sku));
		return $item;
	}
	/**
	 * Test updating a quote with an inventory details response. Method should extract
	 * details and unavailable items (via other methods) and then iterate over all items
	 * in the quote. Any items in the delete list should be removed from the quote, any
	 * items in the details list and not in the delete list should be updated to include
	 * the additional data form the inventory details response. If the quote is successfully
	 * updated, an event should be dispatched with the updated quote.
	 */
	public function testUpdateQuoteWithResponse()
	{
		// mock out observers that would trigger during this test
		$this->replaceByMock('model', 'tax/observer', $this->getModelMock('tax/observer', array()));

		$responseMessage = '<MockResponseMessage/>';
		// skus of items in the test
		$updateSku = 'sku-update';
		$deleteSku = 'sku-delete';
		$ignoreSku = 'sku-ignore';

		// data for each item extracted from the response message
		$updateDetails = array('itemId' => $updateSku);
		$deleteDetails = array('itemId' => $deleteSku);
		// data extracted from the response message
		$itemDetails = array(
			$updateSku => $updateDetails,
			$deleteSku => $deleteDetails,
		);
		// items listed as unavailable in the response message
		$unavailItems = array(
			$deleteSku => array('itemId' => $deleteSku, 'lineId' => 'line-id'),
		);

		$details = $this->getModelMock(
			'eb2cinventory/details',
			array('extractItemDetails', 'extractUnavailableItems', '_updateQuoteItemWithDetails')
		);
		$quoteHelper = $this->getHelperMock('eb2cinventory/quote', array('getXPathForMessage', 'removeItemFromQuote'));
		$xpath = $this->getMockBuilder('DOMXPath')->disableOriginalConstructor()->getMock();
		$quote = $this->getModelMock('sales/quote', array('getAllItems'));
		// this item should be updated with inventory details data
		$itemToUpdate = $this->_mockQuoteItemWithSku($updateSku);
		// this item should be removed from the quote - unavailable item
		$itemToDelete = $this->_mockQuoteItemWithSku($deleteSku);
		// nothing should happen to this item - not included in response
		$itemToIgnore = $this->_mockQuoteItemWithSku($ignoreSku);
		$items = array($itemToUpdate, $itemToDelete, $itemToIgnore);

		$this->replaceByMock('helper', 'eb2cinventory/quote', $quoteHelper);
		$quoteHelper
			->expects($this->once())
			->method('getXpathForMessage')
			->with($this->identicalTo($responseMessage))
			->will($this->returnValue($xpath));
		$quoteHelper
			->expects($this->once())
			->method('removeItemFromQuote')
			->with($this->identicalTo($quote), $this->identicalTo($itemToDelete))
			->will($this->returnSelf());
		$quote
			->expects($this->once())
			->method('getAllItems')
			->will($this->returnValue($items));
		$details
			->expects($this->once())
			->method('extractItemDetails')
			->with($this->identicalTo($xpath))
			->will($this->returnValue($itemDetails));
		$details
			->expects($this->once())
			->method('extractUnavailableItems')
			->with($this->identicalTo($xpath))
			->will($this->returnValue($unavailItems));
		$details
			->expects($this->once())
			->method('_updateQuoteItemWithDetails')
			->with($this->identicalTo($itemToUpdate), $this->identicalTo($updateDetails))
			->will($this->returnSelf());
		$this->assertSame($details, $details->updateQuoteWithResponse($quote, $responseMessage));
		$this->assertEventDispatched('eb2cinventory_details_process_after');
	}
	/**
	 * When updateQuoteWithResponse is given a falsey value - empty string, null, false, 0 -
	 * it shouldn't attempt to update the quote, extract any data, dispatch the
	 * eb2cinventory_details_process_after event.
	 */
	public function testUpdateQuoteWithResponseEmptyResponse()
	{
		$quote = $this->getModelMock('sales/quote');
		$details = $this->getModelMock(
			'eb2cinventory/details',
			array('extractUnavailableItems', 'extractItemDetails', '_xpathForMessage')
		);
		$details->expects($this->never())->method('extractUnavailableItems');
		$details->expects($this->never())->method('extractItemDetails');
		$details->expects($this->never())->method('_xpathForMessage');
		$details->updateQuoteWithResponse($quote, null);
		$this->assertEventNotDispatched('eb2cinventory_details_process_after');
	}
	/**
	 */
	public function testUpdateQuoteItemWithDetails()
	{
		$inventoryData = array(
			'creationTime' => 'creationTime-data',
			'display' => 'display-data',
			'deliveryWindow_from' => 'deliveryWindow_from-data',
			'deliveryWindow_to' => 'deliveryWindow_to-data',
			'shippingWindow_from' => 'shippingWindow_from-data',
			'shippingWindow_to' => 'shippingWindow_to-data',
			'shipFromAddress_line1' => 'shipFromAddress_line1-data',
			'shipFromAddress_city' => 'shipFromAddress_city-data',
			'shipFromAddress_mainDivision' => 'shipFromAddress_mainDivision-data',
			'shipFromAddress_countryCode' => 'shipFromAddress_countryCode-data',
			'shipFromAddress_postalCode' => 'shipFromAddress_postalCode-data',
		);
		$itemData = array(
			'eb2c_creation_time' => $inventoryData['creationTime'],
			'eb2c_display' => $inventoryData['display'],
			'eb2c_delivery_window_from' => $inventoryData['deliveryWindow_from'],
			'eb2c_delivery_window_to' => $inventoryData['deliveryWindow_to'],
			'eb2c_shipping_window_from' => $inventoryData['shippingWindow_from'],
			'eb2c_shipping_window_to' => $inventoryData['shippingWindow_to'],
			'eb2c_ship_from_address_line1' => $inventoryData['shipFromAddress_line1'],
			'eb2c_ship_from_address_city' => $inventoryData['shipFromAddress_city'],
			'eb2c_ship_from_address_main_division' => $inventoryData['shipFromAddress_mainDivision'],
			'eb2c_ship_from_address_country_code' => $inventoryData['shipFromAddress_countryCode'],
			'eb2c_ship_from_address_postal_code' => $inventoryData['shipFromAddress_postalCode']
		);
		$item = $this->getModelMock('sales/quote_item', array('addData'));

		$item
			->expects($this->once())
			->method('addData')
			->with($this->identicalTo($itemData))
			->will($this->returnSelf());
		$details = Mage::getModel('eb2cinventory/details');
		$this->assertSame($details, EcomDev_Utils_Reflection::invokeRestrictedMethod($details, '_updateQuoteItemWithDetails', array($item, $inventoryData)));
	}
}

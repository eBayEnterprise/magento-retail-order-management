<?php
class TrueAction_Eb2cOrder_Test_Model_CreateTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const SAMPLE_SUCCESS_XML = <<<SUCCESS_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Success</ResponseStatus>
</OrderCreateResponse>
SUCCESS_XML;
	const SAMPLE_FAILED_XML = <<<FAILED_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Failed</ResponseStatus>
</OrderCreateResponse>
FAILED_XML;
	const SAMPLE_INVALID_XML = <<<INVALID_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse>
This is a fine mess ollie.
INVALID_XML;
	/**
	 * Tests for correctly as parsed from Pbridge Credit Card extensions 'additional_information' variable.
	 * @test
	 */
	public function testPbridgeGetAdditionalInformation()
	{
		$this->markTestIncomplete('overly broad test');
		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue( (Object) array(
				'isPaymentEnabled' => true,
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);
		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';
		$this->replaceCoreSession();
		$this->replaceCoreConfigRegistry();
		$orderCreator = Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder());
		$reflectXmlRequest = $this->_reflectProperty($orderCreator, '_xmlRequest');
		$xmlRequestValue = $reflectXmlRequest->getValue($orderCreator);
		$testDom = new DOMDocument();
		$testDom->loadXML($xmlRequestValue);
		$this->assertStringStartsWith(
			'pb_avsResponseCode',
			$testDom->getElementsByTagName('AVSResponseCode')->item(0)->nodeValue,
			'AVS Response Code was incorrect.'
		);
		$this->assertStringStartsWith(
			'pb_bankAuthorizationCode',
			$testDom->getElementsByTagName('BankAuthorizationCode')->item(0)->nodeValue,
			'BankAuthorizationCode was incorrect.'
		);
		$this->assertStringStartsWith(
			'pb_cvv2ResponseCode',
			$testDom->getElementsByTagName('CVV2ResponseCode')->item(0)->nodeValue,
			'CVV2ResponseCode was incorrect.'
		);
		$this->assertStringStartsWith(
			'pb_responseCode',
			$testDom->getElementsByTagName('ResponseCode')->item(0)->nodeValue,
			'ResponseCode was incorrect.'
		);
	}
	/**
	 * Test getting tax quotes for a given item
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testGettingTaxQuotesForItem($taxType)
	{
		$item = $this->getModelMock('sales/order_item', array('getQuoteItemId'));
		$item->expects($this->any())
			->method('getQuoteItemId')
			->will($this->returnValue(23));
		$quoteCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter'))
			->getMock();
		$quoteCollection->expects($this->exactly(2))
			->method('addFieldToFilter')
			->will($this->returnSelf());
		$quoteCollection->expects($this->at(0))
			->method('addFieldToFilter')
			->with($this->identicalTo('quote_item_id'), $this->identicalTo(23));
		$quoteCollection->expects($this->at(1))
			->method('addFieldToFilter')
			->with($this->identicalTo('type'), $this->identicalTo($taxType));
		$taxQuote = $this->getModelMock('eb2ctax/response_quote', array('getCollection'));
		$taxQuote->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($quoteCollection));
		$this->replaceByMock('model', 'eb2ctax/response_quote', $taxQuote);
		$create = Mage::getModel('eb2corder/create');
		$collection = $create->getItemTaxQuotes($item, $taxType);
	}
	/**
	 * Test building out a DOMDocumentFragment for tax nodes
	 * @test
	 */
	public function testBuildingTaxNodes()
	{
		$calculationModelMock = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('round'))
			->getMock();
		$calculationModelMock->expects($this->exactly(6))
			->method('round')
			->will($this->returnCallback(function($n) { return round($n, 2); }));
		$this->replaceByMock('model', 'tax/calculation', $calculationModelMock);
		$taxQuotes = array();
		$taxQuotes[] = Mage::getModel('eb2ctax/response_quote', array(
			'id' => '1',
			'quote_item_id' => '15',
			'type' => '0',
			'tax_type' => 'SALES',
			'taxability' => 'TAXABLE',
			'jurisdiction' => 'PENNSYLVANIA',
			'jurisdiction_id' => '31152',
			'jurisdiction_level' => 'STATE',
			'imposition' => 'Sales and Use Tax',
			'imposition_type' => 'General Sales and Use Tax',
			'situs' => 'ADMINISTRATIVE_ORIGIN',
			'effective_rate' => 0.06,
			'taxable_amount' => 43.96,
			'calculated_tax' => 2.64,
		));
		$taxQuotes[] = Mage::getModel('eb2ctax/response_quote', array(
			'id' => '2',
			'quote_item_id' => '15',
			'type' => '0',
			'tax_type' => 'CONSUMER_USE',
			'taxability' => 'TAXABLE',
			'jurisdiction' => 'PENNSYLVANIA',
			'jurisdiction_id' => '31152',
			'jurisdiction_level' => 'STATE',
			'imposition' => 'Some Other Tax',
			'imposition_type' => 'General Sales and Use Tax',
			'situs' => 'ADMINISTRATIVE_ORIGIN',
			'effective_rate' => 0.01,
			'taxable_amount' => 43.96,
			'calculated_tax' => 00.44,
		));
		$taxQuotesCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('getIterator', 'count'))
			->getMock();
		$taxQuotesCollection->expects($this->any())
			->method('getIterator')
			->will($this->returnValue(new ArrayIterator($taxQuotes)));
		$taxQuotesCollection->expects($this->any())
			->method('count')
			->will($this->returnValue(2));
		$create = Mage::getModel('eb2corder/create');
		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, new TrueAction_Dom_Document());
		$method = $this->_reflectMethod($create, '_buildTaxDataNodes');
		$taxFragment = $method->invoke($create, $taxQuotesCollection);
		// probe the tax fragment a bit to hopefully ensure the nodes are all populated right
		$this->assertSame(1, $taxFragment->childNodes->length);
		$this->assertSame('TaxData', $taxFragment->firstChild->nodeName);
		$this->assertSame('Taxes', $taxFragment->firstChild->firstChild->nodeName);
		$taxes = $taxFragment->firstChild->firstChild;
		$this->assertSame(2, $taxes->childNodes->length);
		foreach ($taxes->childNodes as $idx => $taxNode) {
			$this->assertSame('Tax', $taxNode->nodeName);
			// check the attributes on the Tax node
			$attrs = $taxNode->attributes;
			$this->assertSame($taxQuotes[$idx]->getTaxType(), $attrs->getNamedItem('taxType')->nodeValue);
			$this->assertSame($taxQuotes[$idx]->getTaxability(), $attrs->getNamedItem('taxability')->nodeValue);
			foreach ($taxNode->childNodes as $taxData) {
				// test a few of the child nodes, making sure they're getting set properly per tax quote
				switch ($taxData->nodeName) {
					case 'Situs':
						$this->assertSame($taxQuotes[$idx]->getSitus(), $taxData->nodeValue);
						break;
					case 'EffectiveRate':
						$this->assertSame($taxQuotes[$idx]->getEffectiveRate(), (float) $taxData->nodeValue);
						break;
					case 'Imposition':
						$this->assertSame($taxQuotes[$idx]->getImposition(), $taxData->nodeValue);
						break;
				}
			}
		}
	}
	public function testGettingXmlRequest()
	{
		$create = Mage::getModel('eb2corder/create');
		$this->assertNull($create->getXmlRequest(), 'XML Request properly should be null on new instance.');
		$xmlRequest = '<foo><bar /></foo>';
		$xmlRequestProp = $this->_reflectProperty($create, '_xmlRequest');
		$xmlRequestProp->setValue($create, $xmlRequest);
		$this->assertSame($xmlRequest, $create->getXmlRequest());
	}
	/**
	 * When the observer triggers, the create model should build a new request
	 * and send it.
	 * @test
	 */
	public function testObserverCreate()
	{
		$order = Mage::getModel('sales/order');
		$event = new Varien_Event(array('order' => $order));
		$observer = new Varien_Event_Observer(array('event' => $event));
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('buildRequest', 'sendRequest'))
			->getMock();
		$create->expects($this->once())
			->method('buildRequest')
			->with($this->identicalTo($order))
			->will($this->returnSelf());
		$create->expects($this->once())
			->method('sendRequest')
			->will($this->returnSelf());
		$create->observerCreate($observer);
	}
	/**
	 * Successful sending of the request should take the already constructed OrderCreate
	 * request and send it via the Eb2cCore Api model	and then process the response.
	 * @test
	 */
	public function testSendRequest()
	{
		$requestDoc = new TrueAction_Dom_Document();
		$this->replaceCoreConfigRegistry(array(
			'serviceOrderTimeout' => 100,
			'xsdFileCreate' => 'example.xsd',
			'apiService' => 'orders',
			'apiCreateOperation' => 'create',
		));
		$helperStub = $this->getHelperMock('eb2corder/data', array('getOperationUri'));
		$helperStub->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('create'))
			->will($this->returnValue('http://example.com/order/create.xml'));
		$this->replaceByMock('helper', 'eb2corder', $helperStub);
		$apiStub = $this->getModelMock('eb2ccore/api', array('addData', 'request'));
		$apiStub->expects($this->any())
			->method('addData')
			->with($this->logicalAnd(
				$this->arrayHasKey('uri'),
				$this->arrayHasKey('timeout'),
				$this->arrayHasKey('xsd')
			))
			->will($this->returnSelf());
		$apiStub->expects($this->once())
			->method('request')
			->with($this->identicalTo($requestDoc))
			->will($this->returnValue(self::SAMPLE_SUCCESS_XML));
		$this->replaceByMock('model', 'eb2ccore/api', $apiStub);
		$create = $this->getModelMockBuilder('eb2corder/create')
			->setMethods(array('_processResponse', 'getXmlRequest'))
			->getMock();
		$create->expects($this->once())
			->method('_processResponse')
			->with($this->identicalTo(self::SAMPLE_SUCCESS_XML))
			->will($this->returnSelf());
		$create->expects($this->any())
			->method('getXmlRequest')
			->will($this->returnValue(''));
		$createRequest = $this->_reflectProperty($create, '_domRequest');
		$createRequest->setValue($create, $requestDoc);
		$create->sendRequest();
	}
	/**
	 * Provider for the types of exceptions that the Api model could throw when
	 * attempting to make a request.
	 * @return array Arguments arrays containing the different Exceptions
	 */
	public function providerApiExceptions()
	{
		return array(
			array(new Zend_Http_Client_Exception),
			array(new Mage_Core_Exception),
		);
	}
	/**
	 * Test the handling of exceptions from the Api model. Any expected exceptions should
	 * be caught and logged. Process response should then be given an empty response.
	 * @test
	 * @dataProvider providerApiExceptions
	 */
	public function testSendRequestExceptions($exception)
	{
		$requestDoc = new TrueAction_Dom_Document();
		$this->replaceCoreConfigRegistry(array(
			'serviceOrderTimeout' => 100,
			'xsdFileCreate' => 'example.xsd',
			'apiCreateOperation' => 'create',
		));
		$helperStub = $this->getHelperMock('eb2corder/data', array('getOperationUri'));
		$helperStub->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('create'))
			->will($this->returnValue('http://example.com/order/create.xml'));
		$this->replaceByMock('helper', 'eb2corder', $helperStub);
		$apiStub = $this->getModelMock('eb2ccore/api', array('addData', 'request'));
		$apiStub->expects($this->any())
			->method('addData')
			->with($this->logicalAnd(
				$this->arrayHasKey('uri'),
				$this->arrayHasKey('timeout'),
				$this->arrayHasKey('xsd')
			))
			->will($this->returnSelf());
		$apiStub->expects($this->once())
			->method('request')
			->with($this->identicalTo($requestDoc))
			->will($this->throwException($exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiStub);
		$create = $this->getModelMockBuilder('eb2corder/create')
			->setMethods(array('_processResponse', 'getXmlRequest'))
			->getMock();
		$create->expects($this->once())
			->method('_processResponse')
			->with($this->identicalTo(''))
			->will($this->returnSelf());
		$create->expects($this->any())
			->method('getXmlRequest')
			->will($this->returnValue(''));
		$createRequest = $this->_reflectProperty($create, '_domRequest');
		$createRequest->setValue($create, $requestDoc);
		$create->sendRequest();
	}
	/**
	 * Data provider of sample XML responses and the status reported in each
	 * @return array Argument arrays of xml string and status code string
	 */
	public function providerServiceResponses()
	{
		return array(
			array(self::SAMPLE_SUCCESS_XML, 'success'),
			array(self::SAMPLE_FAILED_XML, 'failed'),
			array('', 'failed'), // when the xml is empty, there will be no status but the 'failed' here is used as a signal to the test
		);
	}
	/**
	 * Processing of the responses from the Eb2c order create service.
	 * When successful, should set order status to processing
	 * When failed, should set order status to new
	 * @param string $response [description]
	 * @param string $responseStatus [description]
	 * @test
	 * @dataProvider providerServiceResponses
	 */
	public function testResponseProcessing($response, $responseStatus)
	{
		$orderMock = $this->getModelMock('sales/order', array('save'));
		$orderMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$create = Mage::getModel('eb2corder/create');
		$orderProperty = $this->_reflectProperty($create, '_o');
		$orderProperty->setValue($create, $orderMock);
		$processMethod = $this->_reflectMethod($create, '_processResponse');
		$processMethod->invoke($create, $response);
		$this->assertSame(
			$this->expected($responseStatus)->getOrderState(),
			$orderMock->getState(),
			'Order status not set properly for the reponse status'
		);
		$responseProp = $this->_reflectProperty($create, '_domResponse');
		$responseDom = $responseProp->getValue($create);
		if (empty($response)) {
			$this->assertNull($responseDom, 'Empty response should result in no Response DOM object');
		} else {
			$this->assertInstanceOf('TrueAction_Dom_Document', $responseDom, 'Response DOM should be a TrueAction_Dom_Document');
		}
	}
	/**
	 * @todo this method will need to be dramatically refactored to have any chance of being tested well
	 * @test
	 */
	public function testBuildingRequests()
	{
		$this->markTestIncomplete('test not yet implemented');
	}
	/**
	 * Building out the customer XML for a given order
	 * @param array $customerData Customer data for the given order
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testBuildingCustomerNodes($customerData)
	{
		$this->replaceCoreConfigRegistry(array(
			'clientCustomerIdPrefix' => '12345',
		));
		$order = Mage::getModel('sales/order', $customerData);
		$create = Mage::getModel('eb2corder/create');
		$orderProp = $this->_reflectProperty($create, '_o');
		$orderProp->setValue($create, $order);
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$customer = $doc->appendChild($doc->createElement('root', null))->appendChild($doc->createElement('Customer', null));
		$buildCustomerMethod = $this->_reflectMethod($create, '_buildCustomer');
		$buildCustomerMethod->invoke($create, $customer);
		$this->assertSame('12345' . $customerData['customer_id'], $customer->getAttribute('customerId'));
		$this->assertSame($customerData['customer_prefix'], $customer->getElementsByTagName('Honorific')->item(0)->nodeValue);
		$lastname = (isset($customerData['customer_suffix']) && !empty($customerData['customer_suffix']))
			? $customerData['customer_lastname'] . ' ' . $customerData['customer_suffix']
			: $customerData['customer_lastname'];
		$this->assertSame($lastname, $customer->getElementsByTagName('LastName')->item(0)->nodeValue);
		$this->assertSame($customerData['customer_firstname'], $customer->getElementsByTagName('FirstName')->item(0)->nodeValue);
		if (isset($customerData['customer_dob'])) {
			$this->assertSame($customerData['customer_dob'], $customer->getElementsByTagName('DateOfBirth')->item(0)->nodeValue);
		} else {
			$this->assertSame(0, $customer->getElementsByTagName('DateOfBirth')->length);
		}
		if (isset($customerData['customer_gender'])) {
			$this->assertSame(
				($customerData['customer_gender'] === 1) ? 'M' : 'F',
				$customer->getElementsByTagName('Gender')->item(0)->nodeValue
			);
		}
		$this->assertSame($customerData['customer_email'], $customer->getElementsByTagName('EmailAddress')->item(0)->nodeValue);
		$this->assertSame($customerData['customer_taxvat'], $customer->getElementsByTagName('CustomerTaxId')->item(0)->nodeValue);
	}
	/**
	 * Building the XML nodes for a given order item
	 * @todo This method should really be broken down into some smaller chunks to make this test less complicated
	 * @param array $itemData Order item object data
	 * @param array $orderData Order object data
	 * @param boolean $merchTax Should this item have merchandise taxes
	 * @param boolean $shippingTax Should this item have shipping taxes
	 * @param boolean $dutyTax Should this item have duty taxes
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testBuildOrderItemNodes($itemData, $orderData, $merchTax, $shippingTax, $dutyTax)
	{
		$order = Mage::getModel('sales/order', $orderData);
		$item = Mage::getModel('sales/order_item', $itemData);
		$item->setOrder($order);
		if (!isset($itemData['eb2c_reservation_id'])) {
			$invHelper = $this->getHelperMock('eb2cinventory/data', array('getRequestId'));
			$invHelper->expects($this->once())
				->method('getRequestId')
				->with($orderData['quote_id'])
				->will($this->returnValue('generated_reservation_id'));
			$this->replaceByMock('helper', 'eb2cinventory', $invHelper);
		}
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$itemElement = $doc->appendChild($doc->createElement('root', null))->appendChild($doc->createElement('Item', null));
		// DOMDocumentFragments for mocked responses to _buildTaxDataNodes and _buildDuty
		$emptyFragment = $doc->createDocumentFragment();
		$taxFragment = $doc->createDocumentFragment();
		$taxFragment->appendChild($doc->createElement('MockedTaxNodes'));
		$dutyFragment = $doc->createDocumentFragment();
		$dutyFragment->appendChild($doc->createElement('MockedDutyNodes'));
		$create = $this->getModelMock(
			'eb2corder/create',
			array('_buildTaxDataNodes', 'getItemTaxQuotes', '_buildDuty', '_getItemShippingAmount', '_getShippingChargeType')
		);
		$create->expects($this->exactly(2))
			->method('_buildTaxDataNodes')
			->will($this->onConsecutiveCalls(
				$merchTax ? $taxFragment : $emptyFragment,
				$shippingTax ? $taxFragment : $emptyFragment
			));
		$create->expects($this->exactly(2))
			->method('getItemTaxQuotes')
			->with(
				$this->identicalTo($item),
				$this->logicalOr(
					$this->identicalTo(TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE),
					$this->identicalTo(TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING)
				)
			)
			->will($this->returnValue(Mage::getModel('eb2ctax/resource_response_quote_collection')));
		$create->expects($this->any())
			->method('_buildDuty')
			->will(
				$this->returnValue($dutyTax ? $dutyFragment : $emptyFragment)
			);
		$create->expects($this->any())
			->method('_getShippingChargeType')
			->will($this->returnValue('FLATRATE'));
		$create->expects($this->any())
			->method('_getItemShippingAmount')
			->will($this->returnValue(5.00));
		$orderProp = $this->_reflectProperty($create, '_o');
		$orderProp->setValue($create, $order);
		$buildOrderItemMethod = $this->_reflectMethod($create, '_buildOrderItem');
		$buildOrderItemMethod->invoke($create, $itemElement, $item, 1);
		// the itemElement should have been modified, adding the item nodes onto it
		$this->assertTrue($itemElement->hasChildNodes(), 'No child nodes added to Item node');
		$expectedChildNodes = array('ItemId', 'Quantity', 'Description', 'Pricing', 'ShippingMethod', 'ReservationId');
		$includedChildNodes = array();
		foreach ($itemElement->childNodes as $node) {
			$includedChildNodes[] = $node->nodeName;
		}
		$diff = array_diff($expectedChildNodes, $includedChildNodes);
		$this->assertEmpty($diff, 'Item is missing required child nodes - ' . implode(', ', $diff));
	}
	/**
	 * verify the order collection filters are prepared properly
	 */
	public function testGetNewOrders()
	{
		$collection = $this->getResourceModelMockBuilder('sales/order_collection')
			->disableOriginalConstructor()
			->setMethods(array(
				'addAttributeToSelect',
				'addFieldToFilter',
				'load',
			))
			->getMock();
		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('*'))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('state'),
				$this->identicalTo(array('eq' => 'new'))
			)
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'sales/order_collection', $collection);
		$testModel = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->assertSame(
			$collection,
			$this->_reflectMethod($testModel, '_getNewOrders')->invoke($testModel)
		);
	}

	/**
	 * verify the delivery window dates are extracted from $item
	 * verify the dom nodes returned have the correct structure.
	 * @test
	 */
	public function testBuildEstimatedDeliveryDate()
	{
		$item = $this->getModelMockBuilder('sales/order_item')
			->disableOriginalConstructor()
			->setMethods(array(
				'getEb2cDeliveryWindowFrom',
				'getEb2cDeliveryWindowTo',
				'getEb2cShippingWindowFrom',
				'getEb2cShippingWindowTo',
			))
			->getMock();
		$item->expects($this->once())
			->method('getEb2cDeliveryWindowFrom')
			->will($this->returnValue('eb2c_delivery_window_from'));
		$item->expects($this->once())
			->method('getEb2cDeliveryWindowTo')
			->will($this->returnValue('eb2c_delivery_window_to'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowFrom')
			->will($this->returnValue('eb2c_shipping_window_from'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowTo')
			->will($this->returnValue('eb2c_shipping_window_to'));

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderItem = $doc->addElement('OrderItem')
			->documentElement;

		$testModel = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'));
			->getMock();
		$this->_reflectMethod($testModel, '_buildEstimatedDeliveryDate')
			->invoke($testModel, $orderItem, $item);

		$x = new DomXPath($doc);
		$this->assertNotEmpty(
			$x->query(
				'EstimatedDeliverDate/DeliveryWindow/From[.="eb2c_delivery_window_from"]',
				$orderItem
			),
			'EstimatedDeliverDate/DeliveryWindow/From[.="eb2c_delivery_window_from"] does not exist'
		);
	}
}

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

	const SAMPLE_PBRIDGE_ADDITIONAL_DATA = 'a:1:{s:12:"pbridge_data";a:5:{s:23:"original_payment_method";s:22:"pbridge_eb2cpayment_cc";s:5:"token";s:32:"aee4b59993ceffaa5de7b154f9e494a3";s:8:"cc_last4";s:4:"0101";s:7:"cc_type";s:2:"VI";s:8:"x_params";s:4:"null";}}';

	/**
	 * Test getPbridgeData returns format we can consume
	 */
	public function testParsePbridgeAdditionalData()
	{
		$create = Mage::getModel('eb2corder/create');
		$method = $this->_reflectMethod($create, '_getPbridgeData');
		$testPbridge = $method->invoke($create, self::SAMPLE_PBRIDGE_ADDITIONAL_DATA);
		$this->assertEquals('VI', $testPbridge['cc_type']);
	}

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
			->will($this->returnValue((Object) array('isPaymentEnabled' => true)));
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
	 * Test _getAttributeValueByProductId method with the following expectations
	 * Expectation 1: this test is expected to mockout the method Mage_Catalog_Model_Resource_Product::getAttributeRawValue
	 *                to be called once with product id (9) as first parameter, the attribute which is being passed the
	 *                _getAttributeValueByProductId method in this test as 'tax_code' and also the value being returned
	 *                from the mocked method Mage_Core_Helper_Data::getStoreId which is expected to return the value 1
	 * Expectation 2: the method Mage_Core_Helper_Data::getStoreId is expected to be called once to return the value 1
	 * @mock Mage_Catalog_Model_Resource_Product::getAttributeRawValue
	 * @mock Mage_Core_Helper_Data::getStoreId
	 */
	public function testGetAttributeValueByProductId()
	{
		$productResourceModelMock = $this->getResourceModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeRawValue'))
			->getMock();
		$productResourceModelMock->expects($this->once())
			->method('getAttributeRawValue')
			->with($this->equalTo(9), $this->equalTo('tax_code'), $this->equalTo(1))
			->will($this->returnValue('12334423'));
		$this->replaceByMock('resource_model', 'catalog/product', $productResourceModelMock);

		$coreHelperMock = $this->getHelperMockBuilder('core/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue(1));
		$this->replaceByMock('helper', 'core', $coreHelperMock);

		$create = Mage::getModel('eb2corder/create');
		$this->assertSame('12334423', $this->_reflectMethod($create, '_getAttributeValueByProductId')->invoke($create, 'tax_code', 9));
	}
	/**
	 * Test building out a DOMDocumentFragment for tax nodes
	 * The expectations for the TrueAction_Eb2cOrder_Model_Create::_buildTaxDataNodes is as followed
	 * Expectation 1: the TrueAction_Eb2cOrder_Model_Create::_buildTaxDataNodes method is expected to be invoked with
	 *                a mock of TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection object as the its first parameter
	 *                and a real Mage_Sales_Model_Order_Item object with product object loaded to it, then this method (_buildTaxDataNodes)
	 *                is expected to return DOMDocumentFragment object
	 * Expectation 2: the return value of TrueAction_Eb2cOrder_Model_Create::_buildTaxDataNodes method get inspected for
	 *                for valid nodes that should exists in the returned DOMDocumentFragment object
	 * Expectation 3: the Mage_Tax_Model_Calculation::round method is expected to be called 6 times because the test mocked
	 *                the TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator methods to run an array of two
	 *                real TrueAction_Eb2cTax_Model_Response_Quote object elements with data loaded to them. Each iteration from the
	 *                TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator result will call the
	 *                Mage_Tax_Model_Calculation::round method 3 times
	 * Expectation 4: the class property TrueAction_Eb2cOrder_Model_Create::_domRequest is expected to be initalized with an object of
	 *                TrueAction_Dom_Document type, so the test set this property to a known state with the instantiation of
	 *                TrueAction_Dom_Document class
	 * Expectation 5: the mocked method TrueAction_Eb2cOrder_Model_Create::_getAttributeValueByProductId is expected to get the attribute 'tax_code'
	 *                as its first parameter and the product id which is added in the real order item object to be the value 9, this method is expected
	 *                to be called once and will return the value '1234533' which is then get asserted within the test as the value in the TaxClass node
	 * @mock TrueAction_Eb2cOrder_Model_Create::_getAttributeValueByProductId
	 * @mock Mage_Tax_Model_Calculation::round
	 * @mock TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator
	 * @mock TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection::count
	 */
	public function testBuildingTaxNodes()
	{
		$calculationModelMock = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('round'))
			->getMock();
		$calculationModelMock->expects($this->exactly(6))
			->method('round')
			->will($this->returnCallback(function($n) {
					return round($n, 2);
				}));
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

		$create = $this->getModelMockBuilder('eb2corder/create')
			->setMethods(array('_getAttributeValueByProductId'))
			->getMock();
		$create->expects($this->once())
			->method('_getAttributeValueByProductId')
			->with($this->equalTo('tax_code'), $this->equalTo(9))
			->will($this->returnValue('1234533'));

		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, new TrueAction_Dom_Document());
		$taxFragment = $this->_reflectMethod($create, '_buildTaxDataNodes')->invoke($create, $taxQuotesCollection, Mage::getModel('sales/order_item')->addData(array(
			'product_id' => 9,
		)));
		// probe the tax fragment a bit to hopefully ensure the nodes are all populated right
		$this->assertSame(1, $taxFragment->childNodes->length);
		$this->assertSame('TaxData', $taxFragment->firstChild->nodeName);
		$this->assertSame('TaxClass', $taxFragment->firstChild->firstChild->nodeName);
		$this->assertSame('1234533', $taxFragment->firstChild->firstChild->nodeValue);
		$this->assertSame('Taxes', $taxFragment->lastChild->lastChild->nodeName);

		$taxes = $taxFragment->lastChild->lastChild;
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
	/**
	 * Test that we pull the TaxClass for shipping from config.
	 * @test
	 */
	public function testBuildTaxDataNodesShipping()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'shippingTaxClass' => 'UNIT_TEST_CLASS',
			)
		);

		$taxQuotes[] = Mage::getModel(
			'eb2ctax/response_quote',
			array(
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
			)
		);

		$taxQuotesCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('getIterator', 'count'))
			->getMock();
		$taxQuotesCollection->expects($this->any())
			->method('getIterator')
			->will($this->returnValue(new ArrayIterator($taxQuotes)));
		$taxQuotesCollection->expects($this->any())
			->method('count')
			->will($this->returnValue(1));

		$create = Mage::getModel('eb2corder/create');
		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, new TrueAction_Dom_Document());
		$taxFragment = $this
			->_reflectMethod($create, '_buildTaxDataNodes')
			->invoke($create, $taxQuotesCollection, Mage::getModel('sales/order_item'), TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING);

		$this->assertSame('UNIT_TEST_CLASS', $taxFragment->firstChild->firstChild->nodeValue);
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
			->setMethods(array('_processResponse'))
			->getMock();
		$create->expects($this->once())
			->method('_processResponse')
			->with($this->identicalTo(self::SAMPLE_SUCCESS_XML))
			->will($this->returnSelf());
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
		$order = $this->getModelMock('sales/order', array('save'));
		$order->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$create = $this->getModelMock('eb2corder/create', array('_extractResponseState'));
		$create
			->expects($this->any())
			->method('_extractResponseState')
			->will($this->returnValue($this->expected($responseStatus)->getOrderState()));
		$orderProp = $this->_reflectProperty($create, '_o');
		$orderProp->setValue($create, $order);
		$processMethod = $this->_reflectMethod($create, '_processResponse');
		$processMethod->invoke($create, $response);
		$this->assertSame(
			$this->expected($responseStatus)->getOrderState(),
			$order->getState()
		);
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
			array('_buildTaxDataNodes', 'getItemTaxQuotes', '_buildDuty', '_getItemShippingAmount', '_getShippingChargeType', '_buildEstimatedDeliveryDate')
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
		$create->expects($this->once())
			->method('_buildEstimatedDeliveryDate')
			->with($itemElement, $item)
			->will($this->returnValue(null));
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
			->will($this->returnValue('2014-01-28T20:46:34+00:00'));
		$item->expects($this->once())
			->method('getEb2cDeliveryWindowTo')
			->will($this->returnValue('2014-01-29T17:36:08Z'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowFrom')
			->will($this->returnValue('2014-01-21 17:36:08'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowTo')
			->will($this->returnValue('2014-01-27T17:36:08Z'));
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderItem = $doc->addElement('OrderItem')
			->documentElement;
		$testModel = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->_reflectMethod($testModel, '_buildEstimatedDeliveryDate')
			->invoke($testModel, $orderItem, $item);
		$x = new DomXPath($doc);
		$paths = array(
			'EstimatedDeliveryDate/DeliveryWindow/From[.="2014-01-28T20:46:34+00:00"]',
			'EstimatedDeliveryDate/DeliveryWindow/To[.="2014-01-29T17:36:08+00:00"]',
			'EstimatedDeliveryDate/ShippingWindow/From[.="2014-01-21T17:36:08+00:00"]',
			'EstimatedDeliveryDate/ShippingWindow/To[.="2014-01-27T17:36:08+00:00"]',
			'EstimatedDeliveryDate/Mode[.="LEGACY"]',
			'EstimatedDeliveryDate/MessageType[.="NONE"]',
		);
		foreach ($paths as $path) {
			$this->assertNotNull(
				$x->query($path, $orderItem)->item(0),
				$path . ' does not exist'
			);
		}
	}
	/**
	 * Test _buildOrderCreateRequest method
	 * @test
	 */
	public function testBuildOrderCreateRequest()
	{
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_getRequestId'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_getRequestId')
			->will($this->returnValue('12838-383848-944'));
		$this->_reflectProperty($createModelMock, '_domRequest')->setValue($createModelMock, new TrueAction_Dom_Document('1.0', 'UTF-8'));
		$this->_reflectProperty($createModelMock, '_config')->setValue($createModelMock, (object) array(
			'apiCreateDomRootNodeName' => 'OrderCreateRequest',
			'apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0',
			'apiOrderType' => 'SALES'
		));
		$this->assertInstanceOf(
			'TrueAction_Dom_Element',
			$this->_reflectMethod($createModelMock, '_buildOrderCreateRequest')->invoke($createModelMock)
		);
	}
	/**
	 * Test _processResponse method
	 * @test
	 */
	public function testProcessResponse()
	{
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getState', 'setState', 'save', 'getIncrementId'))
			->getMock();
		$orderModelMock->expects($this->exactly(2))
			->method('getState')
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_NEW));
		$orderModelMock->expects($this->at(1))
			->method('setState')
			->with($this->equalTo(Mage_Sales_Model_Order::STATE_PROCESSING))
			->will($this->returnSelf());
		$orderModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$orderModelMock->expects($this->exactly(2))
			->method('getIncrementId')
			->will($this->returnValue('0005400000000001'));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_extractResponseState'))
			->getMock();
		$successResponse = '<?xml version="1.0" encoding="UTF-8"?><OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><ResponseStatus>Success</ResponseStatus></OrderCreateResponse>';
		$failResponse = '<?xml version="1.0" encoding="UTF-8"?><OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><ResponseStatus>Failure</ResponseStatus></OrderCreateResponse>';
		$createModelMock->expects($this->at(0))
			->method('_extractResponseState')
			->with($this->equalTo($successResponse))
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_PROCESSING));
		$createModelMock->expects($this->at(1))
			->method('_extractResponseState')
			->with($this->equalTo($failResponse))
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_NEW));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$testData = array(
			array(
				'expect' => 'TrueAction_Eb2cOrder_Model_Create',
				'response' => $successResponse,
			),
			array(
				'expect' => 'TrueAction_Eb2cOrder_Model_Create',
				'response' => $failResponse,
			)
		);
		foreach ($testData as $data) {
			$this->assertInstanceOf($data['expect'], $this->_reflectMethod($createModelMock, '_processResponse')->invoke($createModelMock, $data['response']));
		}
	}
	/**
	 * If the response XML exists and has a ResponseStatus node with a value of 'success' in any capitalization,
	 * we should see a value of STATE_PROCESSING. Otherwise, we should see STATE_NEW.
	 *
	 * @test
	 */
	public function testExtractResponseState()
	{
		$create = Mage::getModel('eb2corder/create');
		$crRefl = new ReflectionClass($create);
		$extRespSt = $crRefl->getMethod('_extractResponseState');
		$extRespSt->setAccessible(true);
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, ''));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, '<fail/>'));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke(
			$create,
			'<_><ResponseStatus>nobodyhome!</ResponseStatus></_>')
		);
		$this->assertSame(
			Mage_Sales_Model_Order::STATE_PROCESSING,
			$extRespSt->invoke($create, '<_><ResponseStatus>sUcCeSs</ResponseStatus></_>')
		);
	}
	/**
	 * Test _buildOrder method
	 * @test
	 */
	public function testBuildOrder()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getIncrementId', 'getCreatedAt'))
			->getMock();
		$orderModelMock->expects($this->once())
			->method('getIncrementId')
			->will($this->returnValue('00054000000000001'));
		$orderModelMock->expects($this->once())
			->method('getCreatedAt')
			->will($this->returnValue('2013-11-15 17:01:09'));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildCustomer'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildCustomer')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(null));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$this->_reflectProperty($createModelMock, '_config')->setValue($createModelMock, (object) array(
			'apiLevelOfService' => 'REGULAR',
		));
		$this->assertInstanceOf(
			'TrueAction_Dom_Element',
			$this->_reflectMethod($createModelMock, '_buildOrder')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Test _buildItems method
	 * @test
	 */
	public function testBuildItems()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<root/>');
		$itemModelMock = $this->getModelMockBuilder('sales/order_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getAllVisibleItems'))
			->getMock();
		$orderModelMock->expects($this->once())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($itemModelMock)));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildOrderItem'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildOrderItem')
			->with($this->isInstanceOf('DOMElement'), $this->isInstanceOf('Mage_Sales_Model_Order_Item'), $this->equalTo(1))
			->will($this->returnValue(null));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Model_Create',
			$this->_reflectMethod($createModelMock, '_buildItems')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Test _buildShip method
	 * @test
	 */
	public function testBuildShip()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildShipGroup', '_buildShipping'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildShipGroup')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(null));
		$createModelMock->expects($this->once())
			->method('_buildShipping')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(null));
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Model_Create',
			$this->_reflectMethod($createModelMock, '_buildShip')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Test _buildAdditionalOrderNodes method
	 * @test
	 */
	public function testBuildAdditionalOrderNodes()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$orderHelperMock = $this->getHelperMockBuilder('eb2corder/data')
			->disableOriginalConstructor()
			->setMethods(array('getOrderHistoryUrl'))
			->getMock();
		$orderHelperMock->expects($this->once())
			->method('getOrderHistoryUrl')
			->with($this->isInstanceOf('Mage_Sales_Model_Order'))
			->will($this->returnValue('https://example.com/order/history/'));
		$this->replaceByMock('helper', 'eb2corder', $orderHelperMock);
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getOrderCurrencyCode', 'getGrandTotal'))
			->getMock();
		$orderModelMock->expects($this->once())
			->method('getOrderCurrencyCode')
			->will($this->returnValue('USD'));
		$orderModelMock->expects($this->once())
			->method('getGrandTotal')
			->will($this->returnValue(87.00));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_getSourceData'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_getSourceData')
			->will($this->returnValue(array('source' => 'Web', 'type' => 'sales')));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Model_Create',
			$this->_reflectMethod($createModelMock, '_buildAdditionalOrderNodes')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Test buildRequest method
	 * @test
	 */
	public function testBuildRequest()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($doc));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildOrderCreateRequest', '_buildOrder', '_buildItems', '_buildShip', '_buildPayment', '_buildAdditionalOrderNodes', '_buildContext'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildOrderCreateRequest')
			->will($this->returnValue($doc->documentElement));
		$createModelMock->expects($this->once())
			->method('_buildOrder')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnValue($doc->documentElement));
		$createModelMock->expects($this->once())
			->method('_buildItems')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildShip')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildPayment')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildAdditionalOrderNodes')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildContext')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnSelf());
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Model_Create',
			$createModelMock->buildRequest($orderModelMock)
		);
	}
	/**
	 * Test _buildPayments method for the following expectations for building paypal payment node type
	 * Expectation 1: first this test is mocking the TrueAction_Eb2cCore_Model_Config_Registry::__get so that it can
	 *                enabled the eb2cpayment method, when the test run this mock will run with the parament of 'isPaymentEnabled'
	 *                in which it will return true
	 * Expectation 2: the method TrueAction_Eb2cPayment_Helper_Data::getConfigModel get mocked and is expected to be called
	 *                once with and is expected to return the mocked TrueAction_Eb2cCore_Model_Config_Registry object
	 * Expectation 3: setting the class property TrueAction_Eb2cOrder_Model_Create::_o to a known states with a mock of order
	 *                Mage_Sales_Model_Order object, the order object mocked the method Mage_Sales_Model_Order::getAllPayments to return
	 *                a real object of Mage_Sales_Model_Order_Payment with expected data to be used in the run test method
	 * Expectation 4: set the class property to TrueAction_Eb2cOrder_Model_Create::_ebcPaymentMethodMap array key value
	 *                that we expect will return when the method call the Mage_Sales_Model_Order_Payment::getMethod, which we added data for
	 * Expectation 5: mocked Mage_Sales_Model_Order::getGrandTotal method and expected to run once and to return a known value
	 * Expectation 6: this take data provider  that pass in a request base root node to be loaded into the TrueAction_Dom_Document object
	 *                and then pass in the DomElment of this dom object to the TrueAction_Eb2cOrder_Model_Create::_buildPayments method
	 *                when invoked by this test, it then asserted the xml in this dom object should match what is expected in the loaded expectation
	 *                for this test.
	 * @mock TrueAction_Eb2cPayment_Helper_Data::getConfigModel
	 * @mock TrueAction_Eb2cCore_Model_Config_Registry::__get
	 * @mock Mage_Sales_Model_Order::getAllPayments
	 * @mock Mage_Sales_Model_Order::getGrandTotal
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testBuildPaymentsPaypal($response)
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML($response);

		$mockConfig = $this->getModelMockBuilder('eb2ccore/config_registry')
			->setMethods(array('__get'))
			->getMock();
		$mockConfig->expects($this->once())
			->method('__get')
			->will($this->returnValueMap(array(
				array('isPaymentEnabled', true)
			)));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($mockConfig));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$payment = Mage::getModel('sales/order_payment')->addData(array(
			'entity_id' => 1,
			'method' => 'Paypal_express',
			'created_at' => '2012-07-06 10:09:05',
			'amount_authorized' => 50.00,
			'cc_status' => 'success'
		));

		$order = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getAllPayments', 'getGrandTotal'))
			->getMock();
		$order->expects($this->once())
			->method('getAllPayments')
			->will($this->returnValue(array($payment)));
		$order->expects($this->once())
			->method('getGrandTotal')
			->will($this->returnValue(50.00));

		$create = Mage::getModel('eb2corder/create');
		$this->_reflectProperty($create, '_o')->setValue($create, $order);
		$this->_reflectProperty($create, '_ebcPaymentMethodMap')->setValue($create, array('Paypal_express' => 'PayPal'));
		$this->assertSame($create, $this->_reflectMethod($create, '_buildPayments')->invoke($create, $doc->documentElement));

		$this->assertSame(sprintf($this->expected('paypal')->getPaymentNode(), "\n"), trim($doc->saveXML()));
	}

	/**
	 * @test
	 */
	public function testGetOrderGiftCardPan()
	{
		$expectedPanToken = 'abc123';
		$order = $this->getModelMock('sales/order', array('getGiftCards'));
		$order
			->expects($this->once())
			->method('getGiftCards')
			->will($this->returnValue(serialize(array(array('panToken' => $expectedPanToken)))));
		$this->assertSame($expectedPanToken, TrueAction_Eb2cOrder_Model_Create::getOrderGiftCardPan($order));
	}
}

<?php
/**
 * tests the tax response orderItem class.
 */
class TrueAction_Eb2cTax_Test_Model_Response_OrderItemTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Testing _validate method with the following expectations
	 * Expectation 1: when the method TrueAction_Eb2cTax_Model_Response_Orderitem::_validate get invoke by this test
	 *                the class property TrueAction_Eb2cTax_Model_Response_Orderitem::_isValid which set to true in the
	 *                instantiation of the TrueAction_Eb2cTax_Model_Response_Orderitem class which is confirm by the first assertion
	 * Expectation 2: by uns the sku and lineNumber from the TrueAction_Eb2cTax_Model_Response_Orderitem object we are then guaranteed
	 *                that when the _validate method get invoked the property _isValid will be set to false, which is confirm by the
	 *                last assert false statement in this test
	 * Expectation 3: the method TrueAction_Eb2cTax_Model_Response_Orderitem::_validate will return null once invoke
	 */
	public function testValidate()
	{
		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertTrue($this->_reflectProperty($orderItem, '_isValid')->getValue($orderItem));
		$orderItem->unsSku()->unsLineNumber();
		$this->assertNull($this->_reflectMethod($orderItem, '_validate')->invoke($orderItem));
		$this->assertFalse($this->_reflectProperty($orderItem, '_isValid')->getValue($orderItem));
	}

	/**
	 * helper methods to get item node
	 * @param DOMDocument $doc
	 * @param DOMXPath $xpath
	 * @return DomElement
	 */
	protected function _getItemNode(DOMDocument $doc, DOMXPath $xpath)
	{
		$shipGroups = $xpath->query('a:Shipping/a:ShipGroups/a:ShipGroup', $doc->documentElement);
		foreach ($shipGroups as $shipGroup) {
			$items = $xpath->query('./a:Items/a:OrderItem', $shipGroup);
			foreach ($items as $item) {
				return $item;
			}
		}
	}

	/**
	 * Testing _extractData method with the following expectations
	 * Expectation 1: this test is designed to show how TrueAction_Eb2cTax_Model_Response_Orderitem::_extractData
	 *                extract data from the TaxDutyQuoteResponse. Up on the instanciation of TrueAction_Eb2cTax_Model_Response_Orderitem class object
	 *                an array of key (node) can be passed magically to initializ the property _xpath in the constructor method of this, so because
	 *                this test disabled the constructor when mocking this class, the _xpath property need to be set.
	 * Expectation 2: the property TrueAction_Eb2cTax_Model_Response_Orderitem::_isValid is set in this test to a know value because we mock the
	 *                TrueAction_Eb2cTax_Model_Response_Orderitem::_validate method will set the property to false if the magic method (getSku) is nll or empty string
	 * Expectation 3: the magic method TrueAction_Eb2cTax_Model_Response_Orderitem::addData which is inherited from the extended class Varien_Object is mocked to
	 *                show the extracted data from the response DomDocument element is then pass as an array of key values and the method return it self
	 * Expectation 4: because the property class _isValid is set to know state of true, we know the mocked methods _processTaxData, and _processDiscountTaxData
	 *                will run once and return null
	 * Expectation 5: the TrueAction_Eb2cTax_Model_Response_Orderitem::_extractByType is mocked to be called exactly 2 time with parameter value map that show
	 *                exactly what will be return when the expected parameters are pass to this mocked method
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::getNode - this is a magic Varien_Object method
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::addData - this is a magic Varien_Object method
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_extractByType
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_validate
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_processTaxData
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_processDiscountTaxData
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractData($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$orderitemModelMock = $this->getModelMockBuilder('eb2ctax/response_orderitem')
			->disableOriginalConstructor()
			->setMethods(array('getNode', 'addData', '_validate', '_processTaxData', '_processDiscountTaxData', '_extractByType'))
			->getMock();
		$orderitemModelMock->expects($this->once())
			->method('getNode')
			->will($this->returnValue($itemNode));
		$orderitemModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code',
				'merchandise_amount' => 200.00,
				'unit_price' => 100.00,
				'shipping_amount' => 10.00,
				'duty_amount' => 2.00
			)))
			->will($this->returnSelf());
		$orderitemModelMock->expects($this->exactly(2))
			->method('_extractByType')
			->will($this->returnValueMap(array(
				array(
					$itemNode,
					$xpath,
					array(
						'sku' => 'string(./a:ItemId)',
						'line_number' => 'string(./@lineNumber)',
						'item_desc' => 'string(./a:ItemDesc)',
						'hts_code' => 'string(./a:HTSCode)'
					),
					'string',
					array(
						'sku' => 'gc_virtual1',
						'line_number' => '1',
						'item_desc' => 'Test Item 1',
						'hts_code' => 'duty code'
					)
				),
				array(
					$itemNode,
					$xpath,
					array(
						'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
						'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
						'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
						'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
					),
					'float',
					array(
						'merchandise_amount' => 200.00,
						'unit_price' => 100.00,
						'shipping_amount' => 10.00,
						'duty_amount' => 2.00
					)
				)
			)));
		$orderitemModelMock->expects($this->once())
			->method('_validate')
			->will($this->returnValue(true));
		$orderitemModelMock->expects($this->once())
			->method('_processTaxData')
			->with($this->equalTo($xpath), $this->equalTo($itemNode))
			->will($this->returnValue(null));
		$orderitemModelMock->expects($this->once())
			->method('_processDiscountTaxData')
			->with($this->equalTo($xpath), $this->equalTo($itemNode))
			->will($this->returnValue(null));

		// setting class property _xpath to a known state
		$this->_reflectProperty($orderitemModelMock, '_xpath')->setValue($orderitemModelMock, $xpath);

		// setting class property _isValid to a known state
		$this->_reflectProperty($orderitemModelMock, '_isValid')->setValue($orderitemModelMock, true);

		$this->assertNull($this->_reflectMethod($orderitemModelMock, '_extractData')->invoke($orderitemModelMock));
	}

	/**
	 * @see the expectation for testExtractData, except this test will test with the expectation
	 *      if the node in the response doesn't exists it will assigned those key values array null values
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::getNode - this is a magic Varien_Object method
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::addData - this is a magic Varien_Object method
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_extractByType
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_validate
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_processTaxData
	 * @mock TrueAction_Eb2cTax_Model_Response_Orderitem::_processDiscountTaxData
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractDataSetNullValueFormNoneExistedNodeInReponseXml($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$orderitemModelMock = $this->getModelMockBuilder('eb2ctax/response_orderitem')
			->disableOriginalConstructor()
			->setMethods(array('getNode', 'addData', '_validate', '_processTaxData', '_processDiscountTaxData', '_extractByType'))
			->getMock();
		$orderitemModelMock->expects($this->once())
			->method('getNode')
			->will($this->returnValue($itemNode));
		$orderitemModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code',
				'merchandise_amount' => null,
				'unit_price' => null,
				'shipping_amount' => null,
				'duty_amount' => null
			)))
			->will($this->returnSelf());
		$orderitemModelMock->expects($this->exactly(2))
			->method('_extractByType')
			->will($this->returnValueMap(array(
				array(
					$itemNode,
					$xpath,
					array(
						'sku' => 'string(./a:ItemId)',
						'line_number' => 'string(./@lineNumber)',
						'item_desc' => 'string(./a:ItemDesc)',
						'hts_code' => 'string(./a:HTSCode)'
					),
					'string',
					array(
						'sku' => 'gc_virtual1',
						'line_number' => '1',
						'item_desc' => 'Test Item 1',
						'hts_code' => 'duty code'
					)
				),
				array(
					$itemNode,
					$xpath,
					array(
						'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
						'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
						'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
						'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
					),
					'float',
					array(
						'merchandise_amount' => null,
						'unit_price' => null,
						'shipping_amount' => null,
						'duty_amount' => null
					)
				)
			)));
		$orderitemModelMock->expects($this->once())
			->method('_validate')
			->will($this->returnValue(true));
		$orderitemModelMock->expects($this->once())
			->method('_processTaxData')
			->with($this->equalTo($xpath), $this->equalTo($itemNode))
			->will($this->returnValue(null));
		$orderitemModelMock->expects($this->once())
			->method('_processDiscountTaxData')
			->with($this->equalTo($xpath), $this->equalTo($itemNode))
			->will($this->returnValue(null));

		// setting class property _xpath to a known state
		$this->_reflectProperty($orderitemModelMock, '_xpath')->setValue($orderitemModelMock, $xpath);

		// setting class property _isValid to a known state
		$this->_reflectProperty($orderitemModelMock, '_isValid')->setValue($orderitemModelMock, true);

		$this->assertNull($this->_reflectMethod($orderitemModelMock, '_extractData')->invoke($orderitemModelMock));
	}

	/**
	 * Testing _extractByType method with the following expectations
	 * Expectation 1: the TrueAction_Eb2cTax_Model_Response_Orderitem::_extractByType is being tested for two scenarios
	 *                in this test one where the last parameter (type) change from string to float asserting the key value
	 *                map will change from string value map to float values for the array keys result
	 * Expectation 2: the TrueAction_Eb2cTax_Model_Response_Orderitem::_extractByType is expected DomElement object
	 *                as its first parameter, which the test will provide by the provider as the reponse string
	 *                content which it load into a DOMDocument object, this dom object get pass to the creation of a DOMXPath object
	 *                this xpath object register the name space of the dom object document element, the helper method get the shipgroup
	 *                DomElement to be pass as the first parameter to the _extractByType method and along with the xpath object as the second
	 *                parameter, also the array key map to extract the key values, the fourth parameter determine how set the value to the array key
	 *                if float is pass and the key values are no zero the value will be cast as float
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractByType($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$mapFloat = array(
			'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
			'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
			'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
			'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
		);

		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertSame(
			array(
				'merchandise_amount' => 200.00,
				'unit_price' => 100.00,
				'shipping_amount' => 10.00,
				'duty_amount' => 2.00
			),
			$this->_reflectMethod($orderItem, '_extractByType')->invoke($orderItem, $itemNode, $xpath, $mapFloat, 'float')
		);

		$mapString = array(
			'sku' => 'string(./a:ItemId)',
			'line_number' => 'string(./@lineNumber)',
			'item_desc' => 'string(./a:ItemDesc)',
			'hts_code' => 'string(./a:HTSCode)',
		);

		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertSame(
			array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code'
			),
			$this->_reflectMethod($orderItem, '_extractByType')->invoke($orderItem, $itemNode, $xpath, $mapString, 'string')
		);
	}
}

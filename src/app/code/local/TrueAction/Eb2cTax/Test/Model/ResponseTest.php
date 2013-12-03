<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_ResponseTest extends TrueAction_Eb2cCore_Test_Base
{
	public static $respXml = '';
	public static $cls;
	public static $reqXml;
	public $itemResults;
	public $request;

	public static function setUpBeforeClass()
	{
		self::$cls = new ReflectionClass('TrueAction_Eb2cTax_Model_Response');
		self::$respXml = file_get_contents(__DIR__ . '/ResponseTest/fixtures/response.xml');
		self::$reqXml = file_get_contents(__DIR__ . '/ResponseTest/fixtures/request.xml');
	}

	/**
	 * verify a fault message is properly parsed to a log message.
	 */
	public function testGetFaultLogMessage()
	{
		$faultMessageXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<fault>
	<faultstring>stuff happened</faultstring>
	<detail>
		<errorcode>someCode</errorcode>
		<trace>lots of details</trace>
	</detail>
</fault>
XML;
		$message = "Eb2cTax: Fault Message received: Code: (someCode) Description: 'stuff happened' Trace: 'lots of details'";
		$document = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$document->loadXML($faultMessageXml);
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->getMock();
		$fn = $this->_reflectMethod($response, '_getFaultLogMessage');
		$this->assertSame($message, $fn->invoke($response, $document));
	}

	/**
	 * verify xml parsing errors are properly formated to a log message.
	 * 20 characters are displayed around the error location.
	 * if the error column is 0, the first 20 characters are displayed.
	 * each error is on its own line.
	 */
	public function testGetXmlErrorLogMessage()
	{
		$warning          = new libXMLError();
		$warning->level   = LIBXML_ERR_WARNING;
		$warning->code    = 'warncode';
		$warning->column  = 0;
		$warning->line    = 1;
		$warning->message = 'the warning message';

		$error          = new libXMLError();
		$error->level   = LIBXML_ERR_ERROR;
		$error->code    = 'errorcode';
		$error->column  = 30;
		$error->line    = 1;
		$error->message = 'the error message';

		$xml = '.........1.........2.........3.........4';
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->getMock();
		$fn = $this->_reflectMethod($response, '_getXmlErrorLogMessage');
		$message = $fn->invoke($response, array($warning, $error), $xml);
		$expectedMessage = 'XML Parser Warning warncode: the warning message Line: 1 Column: 0: `.........1.........2.........3.........4`' . "\n" .
			'XML Parser Error errorcode: the error message Line: 1 Column: 30: `.........2.........3.........4`';
		$this->assertSame($expectedMessage, $message);
	}

	/**
	 * Testing getResponseForItem method
	 *
	 */
	public function testGetResponseForItem()
	{
		$response = $this->_mockResponse();

		$item = $this->getModelMock('sales/quote_item', array('getSku'));
		$item->expects($this->any())
			->method('getSku')
			->will($this->returnValue('gc_virtual1'));
		$addressMock = $this->getModelMock('sales/quote_address');
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$responseItem = $response->getResponseForItem($item, $addressMock);
		$this->assertNotNull($responseItem);
		$this->assertSame(3, count($responseItem->getTaxQuotes()));
	}

	/**
	 * Testing getResponseItems method
	 */
	public function testGetResponseItems()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$this->assertNotNull(
			$response->getResponseItems()
		);
	}

	/**
	 * ensures the object is valid in only if all the parts of the.
	 * @dataProvider dataProvider
	 */
	public function testIsValid($hasXml, $isDocOk, $validateDestinations, $validateResponseItems)
	{
		$request = $this->_mockRequest();
		$methods  = array('hasXml', '_validateResponseItems', '_checkXml', '_validateDestinations', '_extractResults');
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods($methods)
			->getMock();
		$response->expects($this->any())
			->method('hasXml')
			->will($this->returnValue($hasXml));
		$response->expects($this->any())
			->method('_validateResponseItems')
			->will($this->returnValue($validateResponseItems));
		$response->expects($this->any())
			->method('_checkXml')
			->with($this->identicalTo(self::$respXml))
			->will($this->returnValue($isDocOk));
		$response->expects($this->any())
			->method('_validateDestinations')
			->will($this->returnValue($validateDestinations));
		// select expectation
		$e = $this->expected('%s-%s-%s-%s', (int) $hasXml, (int) $isDocOk, (int) $validateDestinations, (int) $validateResponseItems);
		$timesCalled = $e->getExtractResultsCalled() ? $this->once() : $this->never();
		$response->expects($timesCalled)
			->method('_extractResults')
			->will($this->returnSelf());
		// setup initial data
		$initData = array('xml' => self::$respXml, 'request' => $request);
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$doc->loadXML(self::$respXml);
		$this->_reflectProperty($response, '_doc')->setValue($response, $doc);
		$response->setData($initData);
		// run the test
		$this->_reflectMethod($response, '_construct')->invoke($response);
		// check final result
		$this->assertSame((bool) $e->getIsValid(), $response->isValid());
	}

	/**
	 * Testing isValid method
	 */
	public function testIsValidWithBadRequest()
	{
		// setup the SUT
		$response = $this->_mockResponse();
		$this->assertTrue($response->isValid());
		$request = Mage::getModel('eb2ctax/request');
		$this->assertFalse($request->isValid());
		$response->setRequest($request);
		$this->assertFalse($response->isValid());
	}

	/**
	 * Testing _construct method - valid request/response match xml
	 *
	 * @test
	 */
	public function testConstructValidRequestResponseMatch()
	{
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->_mockRequest()
		));

		$addressMock = $this->getModelMock('sales/quote_address', array('getId'));
		$addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$responseReflector = new ReflectionObject($response);
		$addressProperty = $responseReflector->getProperty('_address');
		$addressProperty->setAccessible(true);
		$addressProperty->setValue($response, $addressMock);

		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * Testing _construct method - invalid request/response match xml
	 *
	 * @test
	 */
	public function testConstructInvalidRequestResponseMatch()
	{
		$request = $this->_mockRequest(file_get_contents(dirname(__FILE__) . '/ResponseTest/fixtures/request-invalid.xml'));
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $request
		));

		$responseReflector = new ReflectionObject($response);
		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * Testing _construct method - invalid request/response match xml because of MailingAddress[@id="2"] element is different
	 *
	 * @test
	 */
	public function testConstructMailingAddressMisMatch()
	{
		$request = $this->_mockRequest(file_get_contents(dirname(__FILE__) . '/ResponseTest/fixtures/request-invalid-2.xml'));
		$response = Mage::getModel('eb2ctax/response', array(
			'xml' => self::$respXml,
			'request' => $this->request
		));

		$responseReflector = new ReflectionObject($response);
		$constructMethod = $responseReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertNull(
			$constructMethod->invoke($response)
		);
	}

	/**
	 * @dataProvider shipGroupXmlProvider
	 */
	public function testGetAddressId($xml, $expected)
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$response = Mage::getModel('eb2ctax/response');
		$responseDoc = new ReflectionProperty($response, '_doc');
		$responseDoc->setAccessible(true);
		$responseDoc->setValue($response, $doc);
		$fn = new ReflectionMethod($response, '_getAddressId');
		$fn->setAccessible(true);
		$val = $fn->invoke($response, $doc->documentElement);
		$this->assertSame($expected, $val);
	}

	/**
	 * @test
	 */
	public function testItemSplitAcrossShipgroups()
	{
		$request  = $this->_mockRequest();
		$xmlPath  = __DIR__ . '/ResponseTest/fixtures/responseSplitAcrossShipGroups.xml';
		$response = $this->_mockResponse(file_get_contents($xmlPath), $request);

		$addressMockA = $this->getModelMock('sales/quote_address', array('getId'));
		$addressMockA->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$addressMockB = $this->getModelMock('sales/quote_address', array('getId'));
		$addressMockB->expects($this->any())
			->method('getId')
			->will($this->returnValue(2));

		$itemMock = $this->getModelMock('sales/quote_item', array('getSku'));
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('gc_virtual1'));
		$responseItems = $response->getResponseItems();

		$this->assertNotEmpty($response->getResponseItems());
		$itemResponse = $response->getResponseForItem($itemMock, $addressMockA);
		$this->assertNotNull($itemResponse);
		$this->assertSame('1', $itemResponse->getLineNumber());
		$itemResponse = $response->getResponseForItem($itemMock, $addressMockB);
		$this->assertNotNull($itemResponse);
		$this->assertSame('2', $itemResponse->getLineNumber());
	}

	/**
	 * @test
	 * @loadExpectation
	 */
	public function testDiscounts()
	{
		$response       = $this->_mockResponse();
		$addressMethods = array('getId');
		$itemMethods    = array('getSku');
		$mockAddress    = $this->getModelMock('sales/quote_address', $addressMethods);
		$mockItem       = $this->getModelMock('sales/quote_address_item', $itemMethods);

		$mockAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockItem->expects($this->any())
			->method('getSku')
			->will($this->returnValue('gc_virtual1'));

		$ir = $response->getResponseForItem($mockItem, $mockAddress);
		$ds = $ir->getTaxQuoteDiscounts();
		$this->assertSame(3, count($ds));
		foreach ($ds as $d) {
			$e = $this->expected('0-' . $d->getDiscountId());
			$this->assertSame((float) $e->getAmount(), $d->getAmount());
			$this->assertSame((float) $e->getEffectiveRate(), $d->getEffectiveRate());
			$this->assertSame((float) $e->getTaxableAmount(), $d->getTaxableAmount());
			$this->assertSame((float) $e->getCalculatedTax(), $d->getCalculatedTax());
		}
	}

	public function testResponseQuote()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
		<Taxes xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
			<Tax taxType="SELLER_USE" taxability="TAXABLE">
				<Situs>DESTINATION</Situs>
				<Jurisdiction jurisdictionLevel="STATE" jurisdictionId="31152">PENNSYLVANIA</Jurisdiction>
				<Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
				<EffectiveRate>0.06</EffectiveRate>
				<TaxableAmount>2.0</TaxableAmount>
				<CalculatedTax>0.12</CalculatedTax>
			</Tax>
		</Taxes>';
		$doc = new TrueAction_Dom_Document();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$node = $doc->documentElement->firstChild;
		$a = array(
			'node'               => $node,
			'code'               => 'PENNSYLVANIA-Sales and Use Tax',
			'tax_type'           => 'SELLER_USE',
			'taxability'         => 'TAXABLE',
			'jurisdiction'       => 'PENNSYLVANIA',
			'jurisdiction_id'    => '31152',
			'jurisdiction_level' => 'STATE',
			'imposition'         => 'Sales and Use Tax',
			'imposition_type'    => 'General Sales and Use Tax',
			'situs'              => 'DESTINATION',
			'effective_rate'     => 0.06,
			'taxable_amount'     => 2.00,
			'calculated_tax'     => 0.12,
		);
		$obj = Mage::getModel('eb2ctax/response_quote', array('node' => $node));
		$this->assertSame($a, $obj->getData());
	}

	/**
	 * verify the orderitem model will return null instead of NAN
	 */
	public function testResponseOrderItemNan()
	{
		$xml = '<OrderItem lineNumber="7"  xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				<ItemId><![CDATA[classic-jeans]]></ItemId>
				<ItemDesc><![CDATA[Classic Jean]]></ItemDesc>
				<HTSCode/>
				<Quantity><![CDATA[1]]></Quantity>
				<Pricing>
					<Merchandise>
						<UnitPrice><![CDATA[foo]]></UnitPrice>
						<Amount><![CDATA[]]></Amount>
						<TaxData>
							<TaxClass>89000</TaxClass>
							<Taxes>
								<Tax taxType="SELLER_USE" taxability="TAXABLE">
									<Situs>DESTINATION</Situs>
									<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
									<Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
									<EffectiveRate>0.06</EffectiveRate>
									<TaxableAmount>99.99</TaxableAmount>
									<CalculatedTax>6.0</CalculatedTax>
								</Tax>
								<Tax taxType="SELLER_USE" taxability="TAXABLE">
									<Situs>DESTINATION</Situs>
									<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
									<Imposition impositionType="Random Tax">Random Tax</Imposition>
									<EffectiveRate>0.02</EffectiveRate>
									<TaxableAmount>99.99</TaxableAmount>
									<CalculatedTax>2.0</CalculatedTax>
								</Tax>
							</Taxes>
						</TaxData>
						<PromotionalDiscounts>
							<Discount calculateDuty="false" id="334">
								<Amount>20.00</Amount>
								<Taxes>
									<Tax taxType="SELLER_USE" taxability="TAXABLE">
										<Situs>DESTINATION</Situs>
										<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
										<Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
										<EffectiveRate>0.06</EffectiveRate>
										<TaxableAmount>20.0</TaxableAmount>
										<CalculatedTax>1.2</CalculatedTax>
									</Tax>
									<Tax taxType="SELLER_USE" taxability="TAXABLE">
										<Situs>DESTINATION</Situs>
										<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
										<Imposition impositionType="Random Tax">Random Tax</Imposition>
										<EffectiveRate>0.02</EffectiveRate>
										<TaxableAmount>20.0</TaxableAmount>
										<CalculatedTax>0.4</CalculatedTax>
									</Tax>
								</Taxes>
							</Discount>
						</PromotionalDiscounts>
					</Merchandise>
				</Pricing>
			</OrderItem>';
		$doc = new TrueAction_Dom_Document();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$node = $doc->documentElement->firstChild;
		$obj  = Mage::getModel('eb2ctax/response_quote', array('node' => $node));
		$this->assertNull($obj->getMerchandiseAmount());
		$this->assertNull($obj->getUnitPrice());
		$this->assertNull($obj->getShippingAmount());
		$this->assertNull($obj->getDutyAmount());
	}

	/**
	 * Test the isSameNodelistElement method. Ensures that each node list has at least one item
	 * and the first item in each list are case-insensitive equal
	 * @param  string $responseValue
	 * @param  string $requestValue
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testCompareNodelistElements($responseValue, $requestValue)
	{
		$dom = new TrueAction_Dom_Document();
		$dom->loadXML('<root><response>' .
			(!is_null($responseValue) ? '<item>' . $responseValue . '</item>' : '') .
			'</response><request>' .
			(!is_null($requestValue) ? '<item>' . $requestValue . '</item>' : '') .
			'</request></root>'
		);

		$responseNodelist = $dom->getElementsByTagName('response')->item(0)->childNodes;
		$requestNodelist  = $dom->getElementsByTagName('request')->item(0)->childNodes;

		$this->assertSame(
			$this->expected('set-%s-%s', $responseValue, $requestValue)->getSame(),
			Mage::getModel('eb2ctax/response')->isSameNodelistElement($responseNodelist, $requestNodelist)
		);
	}

	public function shipGroupXmlProvider()
	{
		return array(
			array('<?xml version="1.0" encoding="UTF-8"?>
				<ShipGroup chargeType="FLATRATE" id="shipgroup_1_FLATRATE" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<DestinationTarget ref="_1"/>
				</ShipGroup>', 1),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<ShipGroup chargeType="FLATRATE" id="shipgroup_1_FLATRATE" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<DestinationTarget ref="_256_virtual"/>
				</ShipGroup>', 256),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<ShipGroup chargeType="FLATRATE" id="shipgroup_1_FLATRATE" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<DestinationTarget ref="_"/>
				</ShipGroup>', null),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<ShipGroup chargeType="FLATRATE" id="shipgroup_1_FLATRATE" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<DestinationTarget ref=""/>
				</ShipGroup>', null),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<ShipGroup chargeType="FLATRATE" id="shipgroup_1_FLATRATE" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<DestinationTarget ref="_a"/>
				</ShipGroup>', null),
		);
	}

	/**
	 * @dataProvider xmlProviderForCheckXml
	 */
	public function testCheckXml($xml, $expected)
	{
		$response = Mage::getModel('eb2ctax/response');
		$val = $this->_reflectMethod($response, '_checkXml')->invoke($response, $xml);
		$this->assertSame($expected, $val);
	}

	/**
	 * @dataProvider xmlProviderForValidateResponseItems
	 */
	public function testValidateResponseItems($pathA, $pathB, $expected)
	{
		$response = Mage::getModel('eb2ctax/response');
		$docA = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docB = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docA->preserveWhiteSpace = false;
		$docB->preserveWhiteSpace = false;
		$docA->loadXML(file_get_contents($pathA));
		$docB->loadXML(file_get_contents($pathB));
		$val = $this->_reflectMethod($response, '_validateResponseItems')->invoke($response, $docA, $docB);
		$this->assertSame($expected, $val);
	}

	public function testValidateResponseItemsWithEmptyDocs()
	{
		$response = $this
			->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->getMock();
		$docA = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docB = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$docA->preserveWhiteSpace = false;
		$docB->preserveWhiteSpace = false;
		$val = $this->_reflectMethod($response, '_validateResponseItems')->invoke($response, $docA, $docB);
		$this->assertSame(false, $val);
	}

	public function xmlProviderForCheckXml()
	{
		return array(
			array('<?xml version="1.0" encoding="UTF-8"?>
				<TaxDutyQuoteResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				</TaxDutyQuoteResponse>', true),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<Fault xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<CreateTimestamp>2011-07-23T20:07:39+00:00</CreateTimestamp>
					<Code>INVALID_XML</Code>
					<Description>The xml submitted for quote request was invalid.</Description>
				</Fault>', false),
			array('<?xml version="1.0" encoding="UTF-8"?>
				<someotherdocument>
				</someotherdocument>', false),
			array('sfslfjslfjlsfdlkfjlsfjl', false),
			array('', false),
			array(null, false),
			array('<fault>
				<faultstring>Incorrect HTTP method used in the request</faultstring>
				<detail>
					<errorcode>26301</errorcode>
					<trace>
						Fault Name: IncorrectHTTPMethod
						Error Type: MethodValidationFailure
						Description: Incorrect HTTP method used in the request
						Service: eb2c
						Endpoint: eb2c_client
						Operation (Client):taxes_quote_client_op
					</trace>
				</detail>
			</fault>', false),
			array('<div>
				<a href="/app/search?op=list&type=50">eins</a>
				<!-- HTML parser error : htmlParseEntityRef: expecting \';\' -->
			</div>', false),
		);
	}

	public function xmlProviderForValidateResponseItems()
	{
		$basePath = __DIR__ . '/ResponseTest/fixtures';
		return array(
			array("{$basePath}/req1.xml", "{$basePath}/res1.xml", true),
			array("{$basePath}/req2.xml", "{$basePath}/res2.xml", true),
			array("{$basePath}/req3.xml", "{$basePath}/res3.xml", false),
			array("{$basePath}/req4.xml", "{$basePath}/res4.xml", true),
			array("{$basePath}/req5.xml", "{$basePath}/res5.xml", false),
			array("{$basePath}/req6.xml", "{$basePath}/res6.xml", false),
			array("{$basePath}/req7.xml", "{$basePath}/res7.xml", false),
		);
	}

	/**
	 * mock up a simple response object
	 * @param  string $xml
	 * @param  TrueAction_Eb2cTax_Model_Request $request
	 * @return TrueAction_Eb2cTax_Model_Response
	 */
	protected function _mockResponse($xml='', $request=null)
	{
		$xml     = $xml ? $xml : self::$respXml;
		$request = $request ? $request : $this->_mockRequest();
		// initial data for the model
		$initData = array('xml' => $xml, 'request' => $request);
		// manually initialize the response
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array('_validateResponseItems', '_validateDestinations'))
			->getMock();
		$response->expects($this->any())
			->method('_validateResponseItems')
			->will($this->returnValue(true));
		$response->expects($this->any())
			->method('_validateDestinations')
			->will($this->returnValue(true));
		$response->setData($initData);
		$this->_reflectMethod($response, '_construct')->invoke($response);
		return $response;
	}

	/**
	 * mock up a simple request object
	 * @param  string $xml
	 * @param  bool $isValid
	 * @return TrueAction_Eb2cTax_Model_Request
	 */
	protected function _mockRequest($xml='', $isValid=true)
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		if ($xml) {
			$doc->loadXML($xml);
		} else {
			$doc->loadXML(self::$reqXml);
		}
		$request = $this->getModelMock('eb2ctax/request', array('isValid', 'getDocument'));
		$request->expects($this->any())
			->method('isValid')
			->will($this->returnValue($isValid));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($doc));
		return $request;
	}
}

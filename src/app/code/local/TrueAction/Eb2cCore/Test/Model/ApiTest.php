<?php
class TrueAction_Eb2cCore_Test_Model_ApiTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
	}

	public function providerApiCall()
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 1, 'itemId' => 'SKU-1234')
		);
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 2, 'itemId' => 'SKU-4321')
		);
		return array(
			array(
				$domDocument,
				'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd',
				'Inventory-Service-Quantity-1.0.xsd'
			),
			array(
				$domDocument,
				'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd',
				'Inventory-Service-Quantity-1.0.xsd'
			),
		);
	}

	/**
	 * testing request() method
	 *
	 * @test
	 * @dataProvider providerApiCall
	 */
	public function testRequest($request, $apiUri, $xsdName)
	{
		$strType = gettype('');

		$configConstraint = new PHPUnit_Framework_Constraint_And();
		$configConstraint->setConstraints(array(
			$this->arrayHasKey('adapter'),
			$this->arrayHasKey('timeout')
		));

		$httpResponse = $this->getMock('Zend_Http_Response', array('isSuccessful', 'getBody'), array(200, array()));
		$httpResponse->expects($this->once())
			->method('isSuccessful')
			->will($this->returnValue(true));
		$httpResponse->expects($this->once())
			->method('getBody')
			->will($this->returnValue('abc'));

		$httpClient = $this->getMock('Varien_Http_Client', array('setHeaders', 'setUri', 'setConfig', 'setRawData', 'setEncType', 'request'));
		$httpClient->expects($this->once())
			->method('setHeaders')
			->with($this->identicalTo('apiKey'), $this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setUri')
			->with($this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setConfig')
			->with($configConstraint)
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setRawData')
			->with($this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setEncType')
			->with($this->identicalTo('text/xml'))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('request')
			->with($this->identicalTo('POST'))
			->will($this->returnValue($httpResponse));

		$api = Mage::getModel('eb2ccore/api')
			->setHttpClient($httpClient)
			->setXsd($xsdName)
			->setUri($apiUri);
		$this->assertNotEmpty($api->request($request));
	}

	/**
	 * Test that request throws exception when xsd is not set.
	 * @expectedException TrueAction_Eb2cCore_Exception
	 * @test
	 */
	public function testRequestNoXsd()
	{
		Mage::getModel('eb2ccore/api')->request(new DOMDocument());
	}

	/**
	 * Test that request throws exception when xsd is not set.
	 * @expectedException TrueAction_Eb2cCore_Exception
	 * @test
	 */
	public function testRequestInvalidXml()
	{
		$doc = new DOMDocument();
		$doc->appendChild($doc->createElement('_'));
		Mage::getModel('eb2ccore/api')->setXsd('Address-Validation-Datatypes-1.0.xsd')->request($doc);
	}
	/**
	 * testing request() method will store the status when the request is not successful
	 *
	 * @test
	 */
	public function testRequestStatus()
	{
		$data = $this->providerApiCall();
		$request = $data[0][0];
		$apiUri = $data[0][1];
		$xsdName = $data[0][2];

		$strType = gettype('');

		$configConstraint = new PHPUnit_Framework_Constraint_And();
		$configConstraint->setConstraints(array(
			$this->arrayHasKey('adapter'),
			$this->arrayHasKey('timeout')
		));

		$httpResponse = $this->getMock('Zend_Http_Response', array('isSuccessful'), array(401, array()));
		$httpResponse->expects($this->once())
			->method('isSuccessful')
			->will($this->returnValue(false));

		$httpClient = $this->getMock('Varien_Http_Client', array('setHeaders', 'setUri', 'setConfig', 'setRawData', 'setEncType', 'request'));
		$httpClient->expects($this->once())
			->method('setHeaders')
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setUri')
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setConfig')
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setRawData')
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setEncType')
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('request')
			->will($this->returnValue($httpResponse));

		$api = Mage::getModel('eb2ccore/api')
			->setHttpClient($httpClient)
			->setXsd($xsdName)
			->setUri($apiUri);
		$this->assertEmpty($api->request($request));
		$this->assertSame(401, $api->getStatus());
	}

	/**
	 * Test setting http client to something silly
	 * @expectedException Exception
	 */
	public function testInvalidSetHttpClientThrowsException()
	{
		$status = false;
		$api = Mage::getModel('eb2ccore/api');
		$api->setHttpClient($api);
	}

	/**
	 * Mock out the config helper.
	 */
	protected function _mockConfig()
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get'))
			->getMock();
		$mockConfig = array(
			array('apiEnvironment', 'prod'),
			array('apiRegion', 'eu'),
			array('apiMajorVersion', '1'),
			array('apiMinorVersion', '10'),
			array('storeId', 'store-123'),
		);
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}
}

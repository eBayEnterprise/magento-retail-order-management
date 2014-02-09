<?php
class TrueAction_Eb2cCore_Test_Model_ApiTest extends EcomDev_PHPUnit_Test_Case
{
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
				'http://example.com/',
				'Inventory-Service-Quantity-1.0.xsd'
			),
		);
	}
	/**
	 * Test that the request method returns a non-empty string on successful response.
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
		$api = Mage::getModel('eb2ccore/api');
		$this->assertSame(0, $api->getStatus());
		$this->assertNotEmpty($api->request($request, $xsdName, $apiUri, 0, 'foo', $httpClient));
		$this->assertSame(200, $api->getStatus());
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
			array('apiMajorVersion', '1'),
			array('apiMinorVersion', '10'),
			array('apiRegion', 'eu'),
			array('apiXsdPath', 'app/code/local/TrueAction/Eb2cCore/xsd'),
			array('storeId', 'store-123'),
		);
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}
}

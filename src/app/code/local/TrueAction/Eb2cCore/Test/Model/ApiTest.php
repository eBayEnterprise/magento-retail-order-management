<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Test_Model_ApiTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2ccore');
		}
		return $this->_helper;
	}

	/**
	 * Get Dom instantiated object.
	 * @todo Why isn't this just a generic core helper?
	 * @return TrueAction_Dom_Document
	 */
	public function getDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}

	public function providerApiCall()
	{
		$domDocument = $this->getDomDocument();
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
			array($domDocument, 'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd'),
			array($domDocument, 'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd'),
		);
	}

	/**
	 * testing request() method
	 *
	 * @test
	 * @dataProvider providerApiCall
	 */
	public function testRequest($request, $apiUri)
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
			->setUri($apiUri);
		$this->assertNotEmpty($api->request($request));
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

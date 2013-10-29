<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = Mage::helper('eb2ccore');
	}

	/**
	 * Test getNewDomDocument - although providerApiCall calls it, does not get noted as covered.
	 *
	 * @test
	 */
	public function testGetNewDomDocument()
	{
		$this->assertInstanceOf('TrueAction_Dom_Document', Mage::helper('eb2ccore')->getNewDomDocument());
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
				$domDocument, 'http://eb2c.edge.mage.tandev.net/GSI%20eb2c%20Web%20Service%20Schemas%20v1.0/Inventory-Service-Quantity-1.0.xsd'
			)
		);
	}

	/**
	 * Mock out the config helper.
	 *
	 * @param  array $mockConfig Config data as nested arrays to be used for a returnValueMap
	 */
	protected function _mockConfig($mockConfig)
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get'))
			->getMock();
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}

	/**
	 * test generating the API URIs
	 * @test
	 */
	public function testApiUriCreation()
	{
		$this->_mockConfig(array(
			array('apiEnvironment', 'api_env'),
			array('apiRegion', 'api_rgn'),
			array('apiMajorVersion', 'M'),
			array('apiMinorVersion', 'm'),
			array('storeId', 'store_id'),
		));
		$helper = Mage::helper('eb2ccore');
		// simplest case - just a service and operation
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/address/validate.xml',
			$helper->getApiUri('address', 'validate')
		);
		// service, operation and params
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/payments/creditcard/auth/VC.xml',
			$helper->getApiUri('payments', 'creditcard', array('auth', 'VC'))
		);
		// service, operation, params and type
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json')
		);
	}

	/**
	 * test generating the API URIs with a non default store.
	 * @test
	 * @loadFixture
	 */
	public function testApiUriCreationNonDefaultStore()
	{
		$helper = Mage::helper('eb2ccore');
		// service, operation, params and type
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id2/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json', 'canada')
		);
	}

	/**
	 * Test checking validity of sftp settings
	 *
	 * @param  string $username SFTP username config setting
	 * @param  string $host     SFTP host/location config setting
	 * @param  string $authType SFTP auth type - password or pub_key config setting
	 * @param  string $password SFTP password config setting
	 * @param  string $pubKey   SFTP public key config setting
	 * @param  string $privKey  SFTP private key config setting
	 *
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testIsValidFtpSettingsWithSftpData($username, $host, $authType, $password, $pubKey, $privKey)
	{
		$this->_mockConfig(array(
			array('sftpUsername', $username),
			array('sftpLocation', $host),
			array('sftpAuthType', $authType),
			array('sftpPassword', $password),
			array('sftpPublicKey', $pubKey),
			array('sftpPrivateKey', $privKey),
		));
		$this->assertSame(
			$this->expected('set-%s-%s-%s-%s-%s-%s', $username, $host, $authType, $password, $pubKey, $privKey)->getIsValid(),
			Mage::helper('eb2ccore')->isValidFtpSettings()
		);
	}

	public function providerXmlToMageLangFrmt()
	{
		return array(
			array('en-US'),
		);
	}

	/**
	 * Test xmlToMageLangFrmt static method
	 *
	 * @param  string $langCode, the language code
	 *
	 * @test
	 * @dataProvider providerXmlToMageLangFrmt
	 */
	public function testXmlToMageLangFrmt($langCode)
	{
		$this->assertSame('en_US', Mage::helper('eb2ccore')->xmlToMageLangFrmt($langCode));
	}

	/**
	 * testing clean method - with re-index disabled
	 *
	 * @test
	 * @loadFixture configDisableReIndex.yaml
	 */
	public function testCleanWithReIndexDisabled()
	{
		$stockStatusModelMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$stockStatusModelMock->expects($this->any())
			->method('rebuild')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'cataloginventory/stock_status', $stockStatusModelMock);

		$this->assertInstanceOf('TrueAction_Eb2cCore_Helper_Data', Mage::helper('eb2ccore')->clean());
	}

	/**
	 * testing clean method - with re-index enabled
	 *
	 * @test
	 * @loadFixture configEnableReIndex.yaml
	 */
	public function testCleanWithReIndexEnabled()
	{
		$stockStatusModelMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$stockStatusModelMock->expects($this->any())
			->method('rebuild')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'cataloginventory/stock_status', $stockStatusModelMock);

		$this->assertInstanceOf('TrueAction_Eb2cCore_Helper_Data', Mage::helper('eb2ccore')->clean());
	}

	/**
	 * testing clean method - when rebuild method throw an exception
	 *
	 * @test
	 * @loadFixture configEnableReIndex.yaml
	 */
	public function testCleanWithExceptionThrowCaught()
	{
		$stockStatusModelMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$stockStatusModelMock->expects($this->any())
			->method('rebuild')
			->will($this->throwException(new Mage_Core_Exception));

		$this->replaceByMock('singleton', 'cataloginventory/stock_status', $stockStatusModelMock);

		$this->assertInstanceOf('TrueAction_Eb2cCore_Helper_Data', Mage::helper('eb2ccore')->clean());
	}

	public function providerExtractNodeVal()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<Product><Item><sku>1234</sku></Item></Product>');
		$xpath = new DOMXPath($doc);
		$items = $xpath->query('//Item');
		$skuNode = null;
		foreach($items as $item) {
			$skuNode = $xpath->query('sku/text()', $item);
		}
		return array(array($skuNode));
	}

	/**
	 * Test extractNodeVal method
	 *
	 * @param DOMNodeList $nodeList
	 *
	 * @test
	 * @dataProvider providerExtractNodeVal
	 */
	public function testExtractNodeVal(DOMNodeList $nodeList)
	{
		$this->assertSame('1234', Mage::helper('eb2ccore')->extractNodeVal($nodeList));
	}

	public function providerExtractNodeAttributeVal()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<Product><Item><sku gsi_client_id="TAN-CLI">1234</sku></Item></Product>');
		$xpath = new DOMXPath($doc);
		$items = $xpath->query('//Item');
		$skuNode = null;
		foreach($items as $item) {
			$skuNode = $xpath->query('sku', $item);
		}
		return array(array($skuNode, 'gsi_client_id'));
	}

	/**
	 * Test extractNodeAttributeVal method
	 *
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 *
	 * @test
	 * @dataProvider providerExtractNodeAttributeVal
	 */
	public function testExtractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		$this->assertSame('TAN-CLI', Mage::helper('eb2ccore')->extractNodeAttributeVal($nodeList, $attributeName));
	}
}

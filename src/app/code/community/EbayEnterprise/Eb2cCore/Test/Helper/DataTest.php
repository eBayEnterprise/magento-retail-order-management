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

class EbayEnterprise_Eb2cCore_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_helper;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = Mage::helper('eb2ccore');

		// suppressing the real session from starting
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);
	}

	/**
	 * Test getNewDomDocument - although providerApiCall calls it, does not get noted as covered.
	 */
	public function testGetNewDomDocument()
	{
		$this->assertInstanceOf('EbayEnterprise_Dom_Document', Mage::helper('eb2ccore')->getNewDomDocument());
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
	 */
	public function testApiUriCreation()
	{
		$this->_mockConfig(array(
			array('apiHostname', 'api.example.com'),
			array('apiMajorVersion', 'M'),
			array('apiMinorVersion', 'm'),
			array('storeId', 'store_id'),
		));
		$helper = Mage::helper('eb2ccore');
		// simplest case - just a service and operation
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/address/validate.xml',
			$helper->getApiUri('address', 'validate')
		);
		// service, operation and params
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/payments/creditcard/auth/VC.xml',
			$helper->getApiUri('payments', 'creditcard', array('auth', 'VC'))
		);
		// service, operation, params and type
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json')
		);
	}

	/**
	 * test generating the API URIs with a non default store.
	 * @loadFixture
	 */
	public function testApiUriCreationNonDefaultStore()
	{
		$this->setCurrentStore('canada');
		$helper = Mage::helper('eb2ccore');
		// service, operation, params and type
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id2/inventory/allocations/delete.json',
			$helper->getApiUri('inventory', 'allocations', array('delete'), 'json')
		);
	}

	/**
	 * Test checking validity of sftp settings
	 *
	 * @param  string $username SFTP username config setting
	 * @param  string $host     SFTP host/location config setting
	 * @param  string $authType SFTP auth type - password or pub_key config setting
	 * @param  string $password SFTP password config setting
	 * @param  string $privKey  SFTP private key config setting
	 *
	 * @dataProvider dataProvider
	 */
	public function testIsValidFtpSettingsWithSftpData($username, $host, $authType, $password, $privKey)
	{
		$this->_mockConfig(array(
			array('sftpUsername', $username),
			array('sftpLocation', $host),
			array('sftpAuthType', $authType),
			array('sftpPassword', $password),
			array('sftpPrivateKey', $privKey),
		));
		$this->assertSame(
			$this->expected('set-%s-%s-%s-%s-%s', $username, $host, $authType, $password, $privKey)->getIsValid(),
			Mage::helper('eb2ccore')->isValidFtpSettings()
		);
	}

	public function providerXmlToMageLangFrmt()
	{
		return array(
			array('en-US'),
		);
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
	 * verify the language code string is converted to EB2C's format.
	 */
	public function testMageToXmlLangFrmt()
	{
		$this->assertSame('en-US', Mage::helper('eb2ccore')->mageToXmlLangFrmt('en_US'));
	}

	/**
	 * Test extractNodeVal method
	 *
	 * @param DOMNodeList $nodeList
	 *
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
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 *
	 * @dataProvider providerExtractNodeAttributeVal
	 */
	public function testExtractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		$this->assertSame('TAN-CLI', Mage::helper('eb2ccore')->extractNodeAttributeVal($nodeList, $attributeName));
	}

	/**
	 * Testing the extractQueryNodeValue method
	 * @loadExpectation
	 */
	public function testExtractQueryNodeValue()
	{
		$e = $this->expected('sample');
		$coreHelper    = Mage::helper('eb2ccore');
		$mySampleQuery = '//MessageHeader/MessageData/MessageId';

		// Good returns the value of the MessageId string:
		$goodDoc = $coreHelper->getNewDomDocument();
		$goodDoc->loadXML($e->getGoodXml());
		$this->assertSame(
			'7',
			$coreHelper->extractQueryNodeValue(new DOMXpath($goodDoc), $mySampleQuery)
		);

		// 'Bad' in this case node not found - returns null
		$badDoc = $coreHelper->getNewDomDocument();
		$badDoc->loadXML($e->getBadXml());
		$this->assertSame(
			null,
			$coreHelper->extractQueryNodeValue(new DOMXpath($badDoc), $mySampleQuery)
		);

		// An empty node should return an empty string ''
		$emptyDoc = $coreHelper->getNewDomDocument();
		$emptyDoc->loadXML($e->getEmptyXml());
		$this->assertSame(
			'',
			$coreHelper->extractQueryNodeValue(new DOMXpath($emptyDoc), $mySampleQuery)
		);
	}
	/**
	 * data provider for self::testParseBool method
	 * @return array
	 */
	public function parseBoolDataProvider()
	{
		return array(
			array(true, true),
			array(false, false),
			array(false, array()),
			array(true, array(range(1, 4))),
			array(true, '1'),
			array(true, 'on'),
			array(true, 't'),
			array(true, 'true'),
			array(true, 'y'),
			array(true, 'yes'),
			array(false, 'false'),
			array(false, 'off'),
			array(false, 'f'),
			array(false, 'n'),
		);
	}
	/**
	 * Test parseBool the feed
	 * @param bool $expect
	 * @param mixed $s
	 * @dataProvider parseBoolDataProvider
	 */
	public function testParseBool($expect, $s)
	{
		$this->assertSame($expect, Mage::helper('eb2ccore')->parseBool($s));
	}
	/**
	 * Testing that the method EbayEnterprise_Eb2cCore_Helper_Data::extractXmlToArray
	 * when called passed in a DOMNode, an array of callback mapping and a DOMXPath
	 * it will extract the data using callbacks and return an array of extracted data.
	 */
	public function testExtractXmlToArray()
	{
		$apiXmlNs = 'http://api.gsicommerce.com/schema/checkout/1.0';
		$sku = '45-HTCT60';
		$qty = 4;
		$xml = '
			<root xmlns="' . $apiXmlNs . '">
				<Order>
					<OrderItems>
						<OrderItem id="12112">
							<ItemId>' . $sku . '</ItemId>
							<Quantity>' . $qty . '</Quantity>
							<Name>Will not be exracted out</Name>
						</OrderItem>
					</OrderItems>
				</Order>
			</root>
		';
		$helper = Mage::helper('eb2ccore');
		$doc = $helper->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = $helper->getNewDomXPath($doc);
		$xpath->registerNamespace('a', $apiXmlNs);
		$nodes = $doc->documentElement;
		$mapping = array(
			'sku' => array(
				'class' => 'ebayenterprise_catalog/map',
				'type' => 'helper',
				'method' => 'extractStringValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:ItemId',
			),
			'qty_ordered' => array(
				'class' => 'ebayenterprise_catalog/map',
				'type' => 'helper',
				'method' => 'extractIntValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:Quantity',
			),
			// Proving that any map field with a 'disabled' type will not be extracted
			'name' => array(
				'class' => 'ebayenterprise_catalog/map',
				'type' => 'disabled',
				'method' => 'extractStringValue',
				'xpath' => 'a:Order/a:OrderItems/a:OrderItem/a:Name',
			),
		);

		$expectData = array('sku' => $sku, 'qty_ordered' => $qty);

		$this->assertSame($expectData, Mage::helper('eb2ccore')->extractXmlToArray(
			$nodes, $mapping, $xpath
		));
	}
	/**
	 * Provide test strings to convert from camelCase to underscores
	 * @return array Args array of strings to convert
	 */
	public function provideStringToUnderscore()
	{
		return array(
			array('nocase', 'nocase'),
			array('camelCase', 'camel_case'),
			array('CamelCase', 'camel_case'),
			array('', ''),
		);
	}
	/**
	 * Test converting a camelCase string to an underscore_delimited string
	 * @param  string $testString   input
	 * @param  string $targetString expected output
	 * @dataProvider provideStringToUnderscore
	 */
	public function testUnderscore($testString, $targetString)
	{
		$this->assertSame(
			$targetString,
			Mage::helper('eb2ccore')->underscoreWords($testString)
		);
	}
	/**
	 * Test invokeCallback method
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testInvokeCallback($provider)
	{
		$iteration = $provider['iteration'];
		$meta = $provider['meta'];
		$expect = $this->expected('expect')->getData($iteration['name']);
		if ($expect === '') {
			$expect = null;
		}
		$mockMethod = (trim($iteration['mock_method']) === '')? array() : array($iteration['mock_method']);

		if (!empty($mockMethod)) {
			switch ($iteration['mock_type']) {
				case 'helper':
					$mock = $this->getHelperMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
				default:
					$mock = $this->getModelMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
			}

			$mock->expects($this->once())
				->method($iteration['mock_method'])
				->will($this->returnValue($iteration['mock_return']));

			$this->replaceByMock($iteration['mock_type'], $meta['class'], $mock);
		}

		$helper = Mage::helper('eb2ccore');
		$this->assertSame($expect, EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, 'invokeCallback', array($meta)));
	}
}

<?php
class TrueAction_Eb2cProduct_Test_Helper_MapTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test extractStringValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractStringValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value extract from the
	 *                DOMNodeList object
	 * @test
	 */
	public function testExtractStringValue()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-2BCEC162</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame(
			'45-2BCEC162',
			Mage::helper('eb2cproduct/map')->extractStringValue(
				$xpath->query('Item/ItemId/ClientItemId', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}

	/**
	 * Test extractBoolValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractBoolValue method with a known
	 *                DOMNodeList object. The method is then expected to return a boolean value extract from the
	 *                DOMNodeList object
	 * Expectation 2: the TrueAction_Eb2cProduct_Helper_Data::parseBool method is expected to be given, the extract value
	 *                from the DOMNodeList object object and return a boolean representative of the passed in string
	 * @mock TrueAction_Eb2cProduct_Helper_Data::parseBool
	 * @test
	 */
	public function testExtractBoolValue()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<IsDropShipped>true</IsDropShipped>
					</BaseAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = new DOMXPath($doc);

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('parseBool')
			->will($this->returnValueMap(array(
				array('true', true)
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$this->assertSame(
			true,
			Mage::helper('eb2cproduct/map')->extractBoolValue(
				$xpath->query('Item/BaseAttributes/IsDropShipped', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}

	/**
	 * Test extractIntValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractIntValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an integer value
	 *                DOMNodeList object
	 * @test
	 */
	public function testExtractIntValue()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Buyer>
							<BuyerId>999</BuyerId>
						</Buyer>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame(
			999,
			Mage::helper('eb2cproduct/map')->extractIntValue(
				$xpath->query('Item/ExtendedAttributes/Buyer/BuyerId', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}

	/**
	 * Test extractFloatValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractFloatValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an float value
	 *                DOMNodeList object
	 * @test
	 */
	public function testExtractFloatValue()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Price>3.98</Price>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame(
			3.98,
			Mage::helper('eb2cproduct/map')->extractFloatValue(
				$xpath->query('Item/ExtendedAttributes/Price', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}

	/**
	 * Test extractStatusValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractStatusValue method with a known
	 *                DOMNodeList object the method is then expected to return a value for enabling magento product by first
	 *                extracting the first value of the DOMNodeList and then checking if it is equal to 'active' and then returning
	 *                the Mage_Catalog_Model_Product_Status::STATUS_ENABLED constant
	 * @test
	 */
	public function testExtractStatusValueWhenActive()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<Items>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<ItemStatus>Active</ItemStatus>
					</BaseAttributes>
				</Item>
			</Items>'
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame(
			Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
			Mage::helper('eb2cproduct/map')->extractStatusValue(
				$xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}

	/**
	 * @see testExtractStatusValueWhenActive test, now testing when the extract string is not equal to 'active'
	 * @test
	 */
	public function testExtractStatusValueWhenNotActive()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<Items>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<ItemStatus>Disabled</ItemStatus>
					</BaseAttributes>
				</Item>
			</Items>'
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame(
			Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
			Mage::helper('eb2cproduct/map')->extractStatusValue(
				$xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Provide an expected catalog class from the feed and the Magento visibility
	 * it should map to.
	 */
	public function provideCatalogClassAndVisibility()
	{
		return array(
			array('regular', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH),
			array('always', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH),
			array('nosale', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE),
		);
	}
	/**
	 * Test extractVisibilityValue for the following expectation
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Map::extractVisibilityValue when invoked
	 *                with a now nodelist object will extract the first nodevalue and will determine
	 *                if the value equal to 'regular' or 'always' in order to return
	 *                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH contstant otherwise
	 *                return Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE constant
	 * @test
	 * @dataProvider provideCatalogClassAndVisibility
	 */
	public function testExtractVisibilityValueWhenVisibilityBoth($catalogClass, $visibility)
	{
		$nodes = $this->getMockBuilder('DOMNodeList')
			->disableOriginalConstructor()
			->getMock();

		$helper = $this->getHelperMock('eb2ccore/data', array('extractNodeVal'));
		$helper->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($catalogClass));

		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$this->assertSame(
			$visibility,
			Mage::helper('eb2cproduct/map')->extractVisibilityValue($nodes)
		);
	}

	/**
	 * return whatever got pass as parameter
	 * @test
	 */
	public function  testPassThrough()
	{
		$x = 'anything';
		$this->assertSame(
			$x,
			Mage::helper('eb2cproduct/map')->passThrough(
				$x, Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Test extracting product links. Should return a serialized array of
	 * product links in the feed.
	 * @test
	 */
	public function testExtractProductLinks()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<ProductLink link_type="ES_Accessory" operation_type="Add">
					<LinkToUniqueId>45-12345</LinkToUniqueId>
				</ProductLink>
				<ProductLink link_type="ES_CrossSelling" operation_type="Delete">
					<LinkToUniqueId>45-23456</LinkToUniqueId>
				</ProductLink>
			</root>'
		);
		$nodes = $doc->getElementsByTagName('ProductLink');

		$links = array(
			array('link_type' => 'related', 'operation_type' => 'Add', 'link_to_unique_id' => '45-12345'),
			array('link_type' => 'crosssell', 'operation_type' => 'Delete', 'link_to_unique_id' => '45-23456'),
		);

		$map = $this->getHelperMock('eb2cproduct/map', array('_convertToMagentoLinkType'));
		$map->expects($this->exactly(2))
			->method('_convertToMagentoLinkType')
			->will($this->returnValueMap(array(
				array('ES_Accessory', 'related'),
				array('ES_CrossSelling', 'crosssell')
			)));

		$this->assertSame(
			serialize($links),
			$map->extractProductLinks(
				$nodes, Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * @test
	 */
	public function testExtractProductLinksUnknownLink()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<root><ProductLink link_type="NO_CLUE_WHAT_THIS_IS" operation_type="Add"><LinkToUniqueId>45-23456</LinkToUniqueId></ProductLink></root>');
		$nodes = $doc->getElementsByTagName('ProductLink');

		$links = array();

		$map = $this->getHelperMock('eb2cproduct/map', array('_convertToMagentoLinkType'));
		$map->expects($this->once())
			->method('_convertToMagentoLinkType')
			->with($this->identicalTo('NO_CLUE_WHAT_THIS_IS'))
			->will($this->throwException(new Mage_Core_Exception()));

		$this->assertSame(
			serialize($links),
			$map->extractProductLinks(
				$nodes, Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Test mapping related product link types through the config registry.
	 * @test
	 */
	public function testConvertToMagentoLinkType()
	{
		$linkTypes = array('ES_Accessory' => 'related', 'ES_CrossSelling' => 'crosssell', 'ES_UpSelling' => 'upsell');

		$configRegistry = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$configRegistry->expects($this->any())
			->method('getConfig')
			->will($this->returnValueMap(array(
				array('link_types_es_accessory', null, 'related'),
				array('link_types_es_crossselling', null, 'crosssell'),
				array('link_types_es_upselling', null, 'upsell'),
			)));

		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$prodHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($configRegistry));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$helper = Mage::helper('eb2cproduct/map');
		foreach ($linkTypes as $ebcLink => $magentoLink) {
			$this->assertSame(
				$magentoLink,
				EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_convertToMagentoLinkType', array($ebcLink))
			);
		}
	}

	/**
	 * Test extractSkuValue method with the following expectations
	 * Expectation 1: when this test invoked this method TrueAction_Eb2cProduct_Helper_Map::extractSkuValue
	 *                with a node list that the extract value doesn't have the catalog id, the called to
	 *                the method TrueAction_Eb2cCore_Helper_Data::normalizeSku will prepend the catalog
	 *                to the extract value
	 * @test
	 */
	public function testExtractSkuValue()
	{
		$nodes = new DOMNodeList();
		$catalogId = '85';
		$sku = '847499';
		$result = $catalogId . '-' . $sku;

		$prodHelper = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$prodHelper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'catalogId' => $catalogId
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal', 'normalizeSku'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($sku));
		$coreHelperMock->expects($this->once())
			->method('normalizeSku')
			->with($this->identicalTo($sku), $this->identicalTo($catalogId))
			->will($this->returnValue($result));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$this->assertSame($result, Mage::helper('eb2cproduct/map')->extractSkuValue($nodes));
	}

	/**
	 * Test extractProductTypeValue method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map::extractProductTypeValue with
	 *                a DOMNodeList object and a Mage_Catalog_Model_Product object it will extract the type value
	 *                and then call the mocked method TrueAction_Eb2cProduct_Helper_Map::_isValidProductType method
	 *                to make sure the value extracted match with the know type of magento product type
	 * Expectation 2: the given product object set type id will be set with the extract method and also the
	 *                the product object set type instance will be set with the return value of the
	 *                static call to the product factory
	 */
	public function testExtractProductTypeValue()
	{
		$value = 'configurable';
		$nodes = new DOMNodeList();

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('setTypeId', 'setTypeInstance'))
			->getMock();
		$product->expects($this->once())
			->method('setTypeId')
			->with($this->identicalTo($value))
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('setTypeInstance')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product_Type_Abstract'), $this->identicalTo(true))
			->will($this->returnSelf());

		$mapMock = $this->getHelperMockBuilder('eb2cproduct/map')
			->disableOriginalConstructor()
			->setMethods(array('_isValidProductType'))
			->getMock();
		$mapMock->expects($this->once())
			->method('_isValidProductType')
			->with($this->identicalTo($value))
			->will($this->returnValue(true));

		$this->assertSame($value, $mapMock->extractProductTypeValue($nodes, $product));
	}

	/**
	 * Test _isValidProductType method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map::_isValidProductType
	 *                with a given value it will check if the value in the six possible Magento product type
	 *                it will return true if value match otherwise false
	 */
	public function testIsValidProductType()
	{
		$testData = array(
			array('expect' => true, 'value' => 'simple'),
			array('expect' => false, 'value' => 'wrong'),
		);

		$map = Mage::helper('eb2cproduct/map');

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$map,
				'_isValidProductType',
				array($data['value'])
			));
		}
	}

	/**
	 * Test extractHtsCodesValue method for the following expectations
	 * Expectation 1: this test will invoke the method TrueAction_Eb2cProduct_Helper_Map::extractHtsCodesValue given
	 *                a DOMNodeList object, the test then expect the nodelist object to be loop through
	 *                and build an array containing key extract data and the return value would be  serialize string
	 *                of such array.
	 */
	public function testExtractHtsCodesValue()
	{
		$data = serialize(array(
			array('mfn_duty_rate' => '10', 'destination_country' => 'AU', 'restricted' => 'N', 'hts_code' => '6114.2'),
			array('mfn_duty_rate' => '12', 'destination_country' => 'AT', 'restricted' => 'N', 'hts_code' => '6114.20')
		));

		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');

		$doc->loadXML(
			'<root>
				<htscodes>
					<HTSCode mfn_duty_rate="10" destination_country="AU" restricted="N">6114.2</HTSCode>
					<HTSCode mfn_duty_rate="12" destination_country="AT" restricted="N">6114.20</HTSCode>
				</htscodes>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$this->assertSame($data, Mage::helper('eb2cproduct/map')->extractHtsCodesValue($xpath->query(
			'htscodes/HTSCode', $doc->documentElement
		)));
	}
}

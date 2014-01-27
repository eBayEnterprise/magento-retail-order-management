<?php
class TrueAction_Eb2cProduct_Test_Helper_MapTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test extractStringValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractStringValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value extract from the
	 *                DOMNodeList object
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
		$this->assertSame('45-2BCEC162', Mage::helper('eb2cproduct/map')->extractStringValue($xpath->query(
			'Item/ItemId/ClientItemId', $doc->documentElement)
		));
	}

	/**
	 * Test extractBoolValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractBoolValue method with a known
	 *                DOMNodeList object. The method is then expected to return a boolean value extract from the
	 *                DOMNodeList object
	 * Expectation 2: the TrueAction_Eb2cProduct_Helper_Data::parseBool method is expected to be given, the extract value
	 *                from the DOMNodeList object object and return a boolean representative of the passed in string
	 * @mock TrueAction_Eb2cProduct_Helper_Data::parseBool
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

		$this->assertSame(true, Mage::helper('eb2cproduct/map')->extractBoolValue($xpath->query(
			'Item/BaseAttributes/IsDropShipped', $doc->documentElement)
		));
	}

	/**
	 * Test extractIntValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractIntValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an integer value
	 *                DOMNodeList object
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
		$this->assertSame(999, Mage::helper('eb2cproduct/map')->extractIntValue($xpath->query(
			'Item/ExtendedAttributes/Buyer/BuyerId', $doc->documentElement)
		));
	}

	/**
	 * Test extractFloatValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractFloatValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an float value
	 *                DOMNodeList object
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
		$this->assertSame(3.98, Mage::helper('eb2cproduct/map')->extractFloatValue($xpath->query(
			'Item/ExtendedAttributes/Price', $doc->documentElement)
		));
	}

	/**
	 * Test extractStatusValue method for the following expectations
	 * Expectation 1: this test is expected to call the TrueAction_Eb2cProduct_Helper_Map::extractStatusValue method with a known
	 *                DOMNodeList object the method is then expected to return a value for enabling magento product by first
	 *                extracting the first value of the DOMNodeList and then checking if it is equal to 'active' and then returning
	 *                the Mage_Catalog_Model_Product_Status::STATUS_ENABLED constant
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
			Mage::helper('eb2cproduct/map')->extractStatusValue($xpath->query(
				'Item/BaseAttributes/ItemStatus', $doc->documentElement)
			)
		);
	}

	/**
	 * @see testExtractStatusValueWhenActive test, now testing when the extract string is not equal to 'active'
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
			Mage::helper('eb2cproduct/map')->extractStatusValue($xpath->query(
				'Item/BaseAttributes/ItemStatus', $doc->documentElement)
			)
		);
	}

	/**
	 * Test extractVisibilityValue for the following expectation
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Map::extractVisibilityValue when invoked
	 *                with a now nodelist object will extract the first nodevalue and will determine
	 *                if the value equal to 'regular' or 'always' in order to return
	 *                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH contstant otherwise
	 *                return Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE constant
	 */
	public function testExtractVisibilityValueWhenVisibilityBoth()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<Items>
				<Item>
					<BaseAttributes>
						<CatalogClass>regular</CatalogClass>
					</BaseAttributes>
				</Item>
			</Items>'
		);

		$xpath = new DOMXPath($doc);
		$this->assertSame(
			Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
			Mage::helper('eb2cproduct/map')->extractVisibilityValue($xpath->query(
				'Item/BaseAttributes/CatalogClass', $doc->documentElement)
			)
		);
	}

	/**
	 * @see testExtractVisibilityValueWhenVisibilityBoth testing the case where the constant
	 *      Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE will be returned from
	 *      the extracted data
	 */
	public function testExtractVisibilityValueWhenNotVisible()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<Items>
				<Item>
					<BaseAttributes>
						<CatalogClass>nosale</CatalogClass>
					</BaseAttributes>
				</Item>
			</Items>'
		);

		$xpath = new DOMXPath($doc);
		$this->assertSame(
			Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
			Mage::helper('eb2cproduct/map')->extractVisibilityValue($xpath->query(
				'Item/BaseAttributes/CatalogClass', $doc->documentElement)
			)
		);
	}

	/**
	 * return whatever got pass as parameter
	 * @test
	 */
	public function  testPassThrough()
	{
		$x = 'anything';
		$this->assertSame($x, Mage::helper('eb2cproduct/map')->passThrough($x));
	}
}

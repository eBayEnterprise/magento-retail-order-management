<?php
class TrueAction_Eb2cProduct_Test_Helper_Map_GiftcardTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test _getGiftCardMap method with the following expectations
	 * Expectation 1: when this test invoked this method TrueAction_Eb2cProduct_Helper_Map_Giftcard::_getGiftCardMap
	 *                will set the class property TrueAction_Eb2cProduct_Helper_Map_Giftcard::_giftcardMap with an
	 *                array of eb2c giftcard tender type key map to Magento giftcard type
	 */
	public function testGetGiftCardMap()
	{
		$mapData = array(
			'SD' => 'virtual',
			'SP' => 'physical',
			'ST' => 'combined',
			'SV' => 'virtual',
			'SX' => 'combined'
		);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cCore_Helper_Feed::GIFTCARD_TENDER_CONFIG_PATH))
			->will($this->returnValue($mapData));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$giftcard = Mage::helper('eb2cproduct/map_giftcard');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($giftcard, '_giftcardMap', array());

		$this->assertSame($mapData, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcard,
			'_getGiftCardMap',
			array()
		));
	}

	/**
	 * Test _getGiftCardType method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Giftcard::_getGiftCardType
	 *                with string of each giftcard constant type it will return the gift card constant value
	 */
	public function testGetGiftCardType()
	{
		$testData = array(
			array(
				'expect' => Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL,
				'type' => TrueAction_Eb2cProduct_Helper_Map_Giftcard::GIFTCARD_VIRTUAL
			),
			array(
				'expect' => Enterprise_GiftCard_Model_Giftcard::TYPE_PHYSICAL,
				'type' => TrueAction_Eb2cProduct_Helper_Map_Giftcard::GIFTCARD_PHYSICAL
			),
			array(
				'expect' => Enterprise_GiftCard_Model_Giftcard::TYPE_COMBINED,
				'type' => TrueAction_Eb2cProduct_Helper_Map_Giftcard::GIFTCARD_COMBINED
			),
		);
		$giftcard = Mage::helper('eb2cproduct/map_giftcard');
		foreach ($testData as $data) {
			$this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$giftcard,
				'_getGiftCardType',
				array($data['type'])
			));
		}
	}

	/**
	 * Test extractGiftcardTenderValue method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderValue with
	 *                a DOMNodeList object it will extract the giftcard tender type value
	 *                and then call the mocked method TrueAction_Eb2cCore_Helper_Feed::getConfigData method
	 *                which will return an array of key tender type map to actual magento gift card type
	 *                and then the method TrueAction_Eb2cProduct_Helper_Map_Giftcard::_getGiftCardType will be given the value
	 *                from the tender type key in the map data which will then return the constant value for the specific gift card type
	 */
	public function testExtractGiftcardTenderValue()
	{
		$value = 'SV';
		$mapValue = 'virtual';
		$mapData = array('SV' => $mapValue);
		$nodes = new DOMNodeList();
		$data = array(
			'use_config_lifetime' => true,
			'use_config_is_redeemable' => true,
			'use_config_email_template' => true
		);

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('addData'))
			->getMock();
		$product->expects($this->once())
			->method('addData')
			->with($this->identicalTo($data))
			->will($this->returnSelf());

		$returnValue = Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL;

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('_getGiftCardType', '_getGiftCardMap'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('_getGiftCardType')
			->with($this->identicalTo($mapValue))
			->will($this->returnValue($returnValue));
		$giftcardHelperMock->expects($this->once())
			->method('_getGiftCardMap')
			->will($this->returnValue($mapData));

		$this->assertSame($returnValue, $giftcardHelperMock->extractGiftcardTenderValue($nodes, $product));
	}

	/**
	 * @see testExtractGiftcardTenderValue this test will invoke the method
	 *      TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderTypeValue and expect
	 *      the extracted tender type code not found in the configuration array map hence expecting
	 *      the return value to be null
	 */
	public function testExtractGiftcardTenderValueWhenTenderTypeNotFoundInConfigurationMap()
	{
		$value = 'SC';
		$mapValue = 'virtual';
		$mapData = array('SV' => $mapValue);
		$nodes = new DOMNodeList();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$returnValue = null;

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cCore_Helper_Feed::GIFTCARD_TENDER_CONFIG_PATH))
			->will($this->returnValue($mapData));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($returnValue, $giftcardHelperMock->extractGiftcardTenderValue($nodes, $product));
	}

	/**
	 * Test extractIsRedeemable method for the following expectations
	 * Expectation 1: when the TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractIsRedeemable method invoked
	 *                with the given DOMNodeList it will call the TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderValue
	 *                given the DOMNodeList and return some value if the value is not null than the extractIsRedeemable
	 *                will return true otherwise false
	 */
	public function testExtractIsRedeemable()
	{
		$nodes = new DOMNodeList();
		$value = 2;
		$storeId = 5;
		$returnValue = true;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getUseConfigIsRedeemable', 'getStoreId'))
			->getMock();
		$product->expects($this->once())
			->method('getUseConfigIsRedeemable')
			->will($this->returnValue(true));
		$product->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreConfigFlag'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getStoreConfigFlag')
			->with(
				$this->identicalTo(Enterprise_GiftCard_Model_Giftcard::XML_PATH_IS_REDEEMABLE),
				$this->identicalTo($storeId)
			)
			->will($this->returnValue($returnValue));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractIsRedeemable($nodes, $product));
	}

	/**
	 * @see testExtractIsRedeemable testing when  extracted value is null and getUseConfigIsRedeemable
	 *      return false we then expect this the given product object method getIsRedeemable to be invoked
	 */
	public function testExtractIsRedeemableWhenTenderValueIsNull()
	{
		$nodes = new DOMNodeList();
		$value = null;
		$returnValue = false;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getIsRedeemable'))
			->getMock();
		$product->expects($this->once())
			->method('getIsRedeemable')
			->will($this->returnValue($value));

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractIsRedeemable($nodes, $product));
	}

	/**
	 * Test extractLifetime method for the following expectations
	 * Expectation 1: when the TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractLifetime method invoked
	 *                with the given DOMNodeList it will call the TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderValue
	 *                given the DOMNodeList and return some value if the value is not null than the extractLifetime
	 *                will return integer value of zero otherwise null
	 */
	public function testExtractLifetime()
	{
		$nodes = new DOMNodeList();
		$value = 2;
		$storeId = 7;
		$returnValue = 5;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getUseConfigLifetime', 'getStoreId'))
			->getMock();
		$product->expects($this->once())
			->method('getUseConfigLifetime')
			->will($this->returnValue(true));
		$product->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreConfig'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getStoreConfig')
			->with($this->identicalTo(Enterprise_GiftCard_Model_Giftcard::XML_PATH_LIFETIME), $this->identicalTo($storeId))
			->will($this->returnValue($returnValue));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractLifetime($nodes, $product));
	}

	/**
	 * @see testExtractLifetime but this test will when the extracted tender type is null
	 *      and the given product object method getUseConfigLifetime return false
	 *      and expecting the only the product object getLifetime to be invoked
	 */
	public function testExtractLifetimeWhenExtractedValueIsNull()
	{
		$nodes = new DOMNodeList();
		$value = null;
		$returnValue = 0;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getLifetime'))
			->getMock();
		$product->expects($this->once())
			->method('getLifetime')
			->will($this->returnValue($returnValue));

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractLifetime($nodes, $product));
	}

	/**
	 * Test extractEmailTemplate method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractEmailTemplate when invoked by this test
	 *                will be given a DOMNodeList object and a Mage_Catalog_Model_Product object and with this
	 *                given parameter it will call the TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderValue
	 *                which will return a non null value and the method getUseConfigEmailTemplate will be invoked in the
	 *                given product object which will return a known boolean true value
	 * Expectation 2: the method TrueAction_Eb2cCore_Helper_Data::getStoreConfig will be invoked and given
	 *                the constant path from the class Enterprise_GiftCard_Model_Giftcard::XML_PATH_EMAIL_TEMPLATE
	 *                and the return value from calling the given product object getStoreId method
	 */
	public function testExtractEmailTemplate()
	{
		$nodes = new DOMNodeList();
		$value = 2;
		$returnValue = '3';
		$storeId = 9;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getUseConfigEmailTemplate', 'getStoreId'))
			->getMock();
		$product->expects($this->once())
			->method('getUseConfigEmailTemplate')
			->will($this->returnValue(true));
		$product->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreConfig'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getStoreConfig')
			->with($this->identicalTo(Enterprise_GiftCard_Model_Giftcard::XML_PATH_EMAIL_TEMPLATE), $this->identicalTo($storeId))
			->will($this->returnValue($returnValue));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractEmailTemplate($nodes, $product));
	}

	/**
	 * @see testExtractEmailTemplate this test will mock calling
	 *      TrueAction_Eb2cProduct_Helper_Map_Giftcard::extractGiftcardTenderValue return null
	 *      and the mocked Mage_Catalog_Model_Product::getUseConfigEmailTemplate return false
	 *      expecting the return value to be from calling the mocked product
	 *      Mage_Catalog_Model_Product::getEmailTemplate method
	 */
	public function testExtractEmailTemplateWhenGiftcardTenderTypeIsNull()
	{
		$nodes = new DOMNodeList();
		$value = null;
		$returnValue = null;

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getEmailTemplate'))
			->getMock();
		$product->expects($this->once())
			->method('getEmailTemplate')
			->will($this->returnValue($returnValue));

		$giftcardHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_giftcard')
			->disableOriginalConstructor()
			->setMethods(array('extractGiftcardTenderValue'))
			->getMock();
		$giftcardHelperMock->expects($this->once())
			->method('extractGiftcardTenderValue')
			->with($this->identicalTo($nodes), $this->identicalTo($product))
			->will($this->returnValue($value));

		$this->assertSame($returnValue, $giftcardHelperMock->extractEmailTemplate($nodes, $product));
	}
}

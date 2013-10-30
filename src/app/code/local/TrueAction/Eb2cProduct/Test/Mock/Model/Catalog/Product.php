<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithValidProductId()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithInvalidProductId()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithInvalidProductException()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithValidProductException()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWhereDeleteThrowException()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithValidProductIdSetColorDescriptionThrowException()
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData', 'setGroupedLinkData', 'getResource', 'getTypeId', 'getConfigurableColorData', 'setStoreId', 'setColorDescription',
				'addData', 'load',
			))
			->getMock();

		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleOptionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setBundleSelectionsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMass')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setMsrp')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDropShipped')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setTaxCode')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierNumber')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDropShipSupplierPart')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setUseConfigGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setStockData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCountryOfManufacture')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableProductsData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setConfigurableAttributesData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCanSaveConfigurableAttributes')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnValue(10));
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setGroupedLinkData')
			->will($this->returnSelf());

		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavModelConfigMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->any())
			->method('getEntityType')
			->will($this->returnValue($eavModelConfigMock));

		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnValue($catalogResourceModelProductMock));
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$catalogModelProductMock->expects($this->any())
			->method('getConfigurableColorData')
			->will($this->returnValue(json_encode(array(array(
				'code' => 'Red',
				'description' => array(
					array('lang' => 'en', 'description' => 'Red'),
					array('lang' => 'fr', 'description' => 'Rouge')
				))
			))));
		$catalogModelProductMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setColorDescription')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}

	/**
	 * replacing by mock of the catalog/product model class
	 *
	 * @return void
	 */
	public function replaceByMockCatalogModelProduct()
	{
		$this->replaceByMock('model', 'catalog/product', $this->buildCatalogModelProductWithValidProductId());
	}

	/**
	 * replacing by mock of the catalog/product_collection model class
	 *
	 * @return void
	 */
	public function replaceByMockCatalogModelProductCollection()
	{
		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'getSelect', 'where', 'load', 'getFirstItem'))
			->getMock();

		$catalogResourceModelProductMock->expects($this->any())
			->method('addAttributeToSelect')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('getSelect')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('where')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('getFirstItem')
			->will($this->returnValue($this->buildCatalogModelProductWithValidProductId()));

		$this->replaceByMock('resource_model', 'catalog/product_collection', $catalogResourceModelProductMock);
	}
}

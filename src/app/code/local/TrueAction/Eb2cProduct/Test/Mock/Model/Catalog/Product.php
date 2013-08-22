<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
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
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData'
			)
		);

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

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithInvalidProductId()
	{
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData'
			)
		);

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

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithInvalidProductException()
	{
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData'
			)
		);

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

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWithValidProductException()
	{
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData'
			)
		);

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

		return $catalogModelProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProductWhereDeleteThrowException()
	{
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'getId', 'setTypeId', 'setId', 'setSku', 'setStatus', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData', 'setMass',
				'setMsrp', 'setPrice', 'setIsDropShipped', 'setTaxCode', 'setDropShipSupplierName', 'setDropShipSupplierNumber',
				'setDropShipSupplierPart', 'setGiftMessageAvailable', 'setUseConfigGiftMessageAvailable', 'setStockData',
				'setCountryOfManufacture', 'setConfigurableProductsData', 'setConfigurableAttributesData', 'setCanSaveConfigurableAttributes',
				'getData', 'setData'
			)
		);

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

		return $catalogModelProductMock;
	}
}

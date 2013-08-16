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
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData'
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
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData'
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
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData'
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
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData'
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
				'setShortDescription', 'save', 'delete', 'setBundleOptionsData', 'setBundleSelectionsData'
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

		return $catalogModelProductMock;
	}
}

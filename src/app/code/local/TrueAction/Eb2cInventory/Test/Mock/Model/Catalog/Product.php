<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cInventory_Test_Mock_Model_Catalog_Product extends EcomDev_PHPUnit_Test_Case
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
				'loadByAttribute', 'getId'
			)
		);

		$catalogModelProductMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

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
				'loadByAttribute', 'getId'
			)
		);

		$catalogModelProductMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));

		return $catalogModelProductMock;
	}
}

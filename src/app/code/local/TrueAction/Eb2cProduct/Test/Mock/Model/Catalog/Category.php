<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Category extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithValidCategoryId()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array('getId', 'addData', 'save')
		);

		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithInvalidCategoryId()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array('getId', 'addData', 'save')
		);

		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithInvalidCategoryException()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array('getId', 'addData', 'save')
		);

		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithValidCategoryException()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array('getId', 'addData', 'save')
		);

		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWhereDeleteThrowException()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array('getId', 'addData', 'save', 'delete')
		);

		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->throwException(new Mage_Core_Exception));

		return $catalogModelCategoryMock;
	}
}

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
		$catalogModelCategoryMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'delete', 'setAttributeSetId', 'setStoreId', 'getId'))
			->getMock();

		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithInvalidCategoryId()
	{
		$catalogModelCategoryMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'delete', 'setAttributeSetId', 'setStoreId', 'getId'))
			->getMock();

		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithInvalidCategoryException()
	{
		$catalogModelCategoryMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'delete', 'setAttributeSetId', 'setStoreId', 'getId'))
			->getMock();

		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWithValidCategoryException()
	{
		$catalogModelCategoryMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'delete', 'setAttributeSetId', 'setStoreId', 'getId'))
			->getMock();

		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategoryWhereDeleteThrowException()
	{
		$catalogModelCategoryMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'delete', 'setAttributeSetId', 'setStoreId', 'getId'))
			->getMock();

		$catalogModelCategoryMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('delete')
			->will($this->throwException(new Mage_Core_Exception));
		$catalogModelCategoryMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('setStoreId')
			->will($this->returnSelf());
		$catalogModelCategoryMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		return $catalogModelCategoryMock;
	}
}

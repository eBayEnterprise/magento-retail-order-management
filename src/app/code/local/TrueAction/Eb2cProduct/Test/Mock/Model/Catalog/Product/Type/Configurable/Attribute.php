<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Catalog_Model_Product_Type_Configurable_Attribute class
	 *
	 * @return Mock_Mage_Catalog_Model_Product_Type_Configurable_Attribute
	 */
	public function buildCatalogModelProductTypeConfigurableAttribute()
	{
		$catalogModelProductTypeConfigurableAttributeMock = $this->getModelMockBuilder('catalog/product_type_configurable_attribute')
			->disableOriginalConstructor()
			->setMethods(array('setProductAttribute', 'getId', 'getLabel'))
			->getMock();

		$catalogModelProductTypeConfigurableAttributeMock->expects($this->any())
			->method('setProductAttribute')
			->will($this->returnSelf());
		$catalogModelProductTypeConfigurableAttributeMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(null));
		$catalogModelProductTypeConfigurableAttributeMock->expects($this->any())
			->method('getLabel')
			->will($this->returnValue('Color'));

		return $catalogModelProductTypeConfigurableAttributeMock;
	}
}

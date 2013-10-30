<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Eav_Model_Entity_Attribute class
	 *
	 * @return Mock_Mage_Eav_Model_Entity_Attribute
	 */
	public function buildEavModelEntityAttribute()
	{
		$eavModelEntityAttributeMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'getPosition', 'getId', 'getAttributeCode', 'getFrontend', 'getLabel'))
			->getMock();

		$eavModelEntityAttributeMock->expects($this->any())
			->method('loadByCode')
			->will($this->returnSelf());
		$eavModelEntityAttributeMock->expects($this->any())
			->method('getPosition')
			->will($this->returnValue(1));
		$eavModelEntityAttributeMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$eavModelEntityAttributeMock->expects($this->any())
			->method('getAttributeCode')
			->will($this->returnValue('color'));
		$eavModelEntityAttributeMock->expects($this->any())
			->method('getFrontend')
			->will($this->returnSelf());
		$eavModelEntityAttributeMock->expects($this->any())
			->method('getLabel')
			->will($this->returnValue('color'));

		return $eavModelEntityAttributeMock;
	}
}

<?php
class TrueAction_Eb2cProduct_Test_Model_Resource_SetupTest
	extends TrueAction_Eb2cCore_Test_Base
{

	public function testGetAttributeSetCollection()
	{
		$mock  = $this->getResourceModelMockBuilder('eav/entity_attribute_set_collection')
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('resource_model' ,'eav/entity_attribute_set_collection', $mock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$val   = $this->_reflectMethod($model, '_getAttributeSetCollection')->invoke($model);
		$this->assertInstanceOf('Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection', $val);
	}
}

<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Eav_Model_Entity_Attribute_Source_Table class
	 *
	 * @return Mock_Mage_Eav_Model_Entity_Attribute_Source_Table
	 */
	public function buildEavModelEntityAttributeSourceTable()
	{
		$eavModelEntityAttributeSourceTableMock = $this->getModelMockBuilder('eav/entity_attribute_source_table')
			->disableOriginalConstructor()
			->setMethods(array('getAllOptions'))
			->getMock();

		$eavModelEntityAttributeSourceTableMock->expects($this->any())
			->method('getAllOptions')
			->will($this->returnValue(
				array(
					array('label' => '', 'value' => ''),
					array('value' => '9', 'label' => 'Black'),
					array('value' => '10', 'label' => 'Blue'),
					array('value' => '11', 'label' => 'Brown'),
					array('value' => '12', 'label' => 'Gray'),
					array('value' => '13', 'label' => 'Green'),
					array('value' => '14', 'label' => 'Magenta'),
					array('value' => '15', 'label' => 'Pink'),
					array('value' => '16', 'label' => 'Red'),
					array('value' => '17', 'label' => 'Silver'),
					array('value' => '18', 'label' => 'White'),
				)
			));

		return $eavModelEntityAttributeSourceTableMock;
	}

	/**
	 * return a mock of the Mage_Eav_Model_Config class
	 *
	 * @return Mock_Mage_Eav_Model_Config
	 */
	public function buildEavModelConfig()
	{
		$eavModelConfigMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute', 'getId', 'getSource'))
			->getMock();

		$eavModelConfigMock->expects($this->any())
			->method('getAttribute')
			->will($this->returnSelf());
		$eavModelConfigMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$eavModelConfigMock->expects($this->any())
			->method('getSource')
			->will($this->returnValue($this->buildEavModelEntityAttributeSourceTable()));

		return $eavModelConfigMock;
	}
}

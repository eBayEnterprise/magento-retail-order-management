<?php
class TrueAction_Eb2cProduct_Test_Model_Resource_SetupTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function testSetupIntegration()
	{
		$attrInfo = Mage::getModel('eb2cproduct/attributes');
		$setup = new TrueAction_Eb2cProduct_Model_Resource_Eav_Entity_Setup('core_setup');
		$setup->applyToAllSets($attrInfo);

		$attr = Mage::getModel('eav/entity_attribute');
		$attr->loadByCode(Mage::getSingleton('eav/config')->getEntityType('catalog_product'), 'price_is_vat_inclusive');
		$this->assertNotNull($attr->getId());
		$this->assertSame('0', $attr->getIsGlobal());
		$this->assertSame('int', $attr->getBackendType());
		$this->assertSame('0', $attr->getIsUnique());
		$this->assertSame('boolean', $attr->getFrontendInput());
		$this->assertSame('simple,configurable,virtual,bundle,downloadable,giftcard', $attr->getApplyTo());
	}

	/**
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testApplyToAllSets($expectation, $validEntityTypes, $existingAttributeId)
	{
		$e = $this->expected($expectation);
		$attributesData = $e->getAttributeData();
		$attributeData = $attributesData['tax_code'];
		$attrInfo = $this->getModelMock('eb2cproduct/attributes', array(
			'getTargetEntityTypeIds',
			'getAttributesData'
		));
		$attrInfo->expects($this->once())
			->method('getTargetEntityTypeIds')
			->will($this->returnValue($validEntityTypes));
		$attrInfo->expects($this->once())
			->method('getAttributesData')
			->will($this->returnValue($attributesData));

		$setup = $this->getMockBuilder('TrueAction_Eb2cProduct_Model_Resource_Eav_Entity_Setup')
			->disableOriginalConstructor()
			->setMethods(array('addAttribute', 'getAttribute', '_logWarn'))
			->getMock();
		$setup->expects($this->once())
			->method('addAttribute')
			->with(
				$this->identicalTo($e->getEntityId()),
				$this->identicalTo($e->getAttributeCode()),
				$this->identicalTo($attributeData)
			)
			->will($this->returnSelf());
		$setup->expects($this->once())
			->method('getAttribute')
			->with(
				$this->identicalTo($e->getEntityId()),
				$this->identicalTo($e->getAttributeCode()),
				$this->identicalTo('attribute_id')
			)
			->will($this->returnValue($existingAttributeId));

		$logCalls = $e->getLogWarnCalled() ? $this->once() : $this->never();
		$setup->expects($logCalls)
			->method('_logWarn');
		$setup->applyToAllSets($attrInfo);
	}
}

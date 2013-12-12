<?php
class TrueAction_Eb2cPayment_Test_Block_Overrides_Adminhtml_System_Config_TabsTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function tearDown()
	{
		parent::tearDown();
		Mage::unregister('_helper/eb2cpayment');
	}

	/**
	 * @test
	 * verify the isConfigSuppressed function is called with the correct parameters.
	 */
	public function testCheckSectionPermissionsSuppressionCall()
	{
		$sectionName = 'giftcard';
		$testModel = $this->getBlockMockBuilder('adminhtml/system_config_tabs')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Tabs',
			$testModel,
			'message'
		);

		$session = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(array('isAllowed'))
			->getMock();
		$session->expects($this->any())
			->method('isAllowed')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'admin/session', $session);

		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'isConfigSuppressed',
		));
		$suppression->expects($this->once())
			->method('isConfigSuppressed')
			->with($this->identicalTo($sectionName));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);

		$testModel->checkSectionPermissions('giftcard');
	}
}

<?php
class TrueAction_Eb2cCore_Test_Model_System_Config_Backend_Language_CodeTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/*
	 * @test
	 * Test that our method trims spaces and lower-cases its value
	 */
	public function testLanguageCodeToLower()
	{
		/*
		 * We've only overridden the _beforeSave method. We want to make sure it thinks
		 * there's been a change so we return true on 'isValueChanged'.
	 	 */
		$backendMock = $this->getModelMockBuilder('eb2ccore/system_config_backend_language_code')
			->setMethods(array( 'isValueChanged',))
			->getMock();
		$backendRefl = new ReflectionObject($backendMock);
		$beforeSave = $backendRefl->getMethod('_beforeSave');
		$beforeSave->setAccessible(true);
		$backendMock->expects($this->once())
			->method('isValueChanged')
			->will($this->returnValue(true));

		$this->assertSame('en-us',
			$backendMock->setValue("\r\n\t EN-US")->_beforeSave()->getValue());
	}
}

<?php
/**
 * Feed Interface Test
 *
 */
class TrueAction_Eb2cCore_Test_Model_Feed_InterfaceTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Really the only test you can do with an interface, I think.
	 *
	 * @test
	 */
	public function testInterfaceExists()
	{
		$this->assertTrue(interface_exists('TrueAction_Eb2cCore_Model_Feed_Interface'));
	}
}

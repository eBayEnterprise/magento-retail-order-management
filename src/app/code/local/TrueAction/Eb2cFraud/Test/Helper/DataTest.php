<?php
class TrueAction_Eb2cFraud_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;
	protected $_jsModuleName;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = new TrueAction_Eb2cFraud_Helper_Data();
		$this->_jsModuleName = TrueAction_Eb2cFraud_Helper_Data::JSC_JS_PATH;
	}

	/**
	 * Make sure we get back some config data
	 * @test
	 * @loadFixture testConfig
	 */
	public function testGetConfig()
	{
		$config = $this->_helper->getConfig();
		$this->assertSame('TAN-EB2CJS', $config->clientId);
		$this->assertSame(null, $config->developerMode);
	}

	/**
	 * Get back sensible URL
	 * @test
	 */
	public function testGetJscUrl()
	{
		$url = $this->_helper->getJscUrl();
		$this->assertStringEndsWith($this->_jsModuleName, $url);
	}

}

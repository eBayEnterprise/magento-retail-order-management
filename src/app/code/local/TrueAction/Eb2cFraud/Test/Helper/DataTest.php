<?php
/**
 *
 *
 */
class TrueAction_Eb2cFraud_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;
	protected $_jsModuleName;

	/**
	 * setUp method
	 */
	public function setUp() {
		parent::setUp();
		$this->_helper = new TrueAction_Eb2cFraud_Helper_Data();
		$this->_jsModuleName = TrueAction_Eb2cFraud_Helper_Data::JSC_JS_PATH;
	}

	/**
	 * Make sure we get back some config data
	 * @test
	 * @loadFixture testConfig
	 */
	public function testGetConfig( )
	{
		$config = $this->_helper->getConfig();
		$this->assertSame($config->clientId, 'TAN-EB2CJS');
		$this->assertSame($config->developerMode, '1');
	}

	/**
	 * Make sure we get back an instance of TrueAction_Eb2cCore_Helper_Data
	 * @test
	 */
	public function testGetCoreHelper()
	{
		$coreHelper = $this->_helper->getCoreHelper();
		$this->assertInstanceOf('TrueAction_Eb2cCore_Helper_Data', $coreHelper);
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

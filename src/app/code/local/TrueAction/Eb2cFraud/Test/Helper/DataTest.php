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

	/**
	 * Get back random Jsc set (as an array), make sure the js file exists
	 * @test
	 */
	public function testGetJsc() {
		$jsc = $this->_helper->getJsc();
		$this->assertArrayHasKey('function', $jsc );
		$this->assertArrayHasKey('formfield', $jsc );
		$this->assertArrayHasKey('filename', $jsc );
		$this->assertArrayHasKey('url', $jsc );
		$this->assertArrayHasKey('fullpath', $jsc );
		
		$this->assertFileExists($jsc['fullpath'], $message = $jsc['fullpath'] . ' not found [are JSCs installed?].');
	}

	/**
	 * Test the 3 functions that put out necessary html/ javascript we need to inject into the checkout form
	 * @test
	 */
	public function testBuildHtml() {
		$this->_helper->getJsc();
		$scriptTagHtml = $this->_helper->getJscScriptTag();
		$scriptTagMatcher = array('tag' => 'script');
		$this->assertTag( $scriptTagMatcher, $scriptTagHtml, 'Script Tag Error');

		$formFieldHtml = $this->_helper->getJscFormField();
		$formFieldTagMatcher = array('tag' => 'input');
		$this->assertTag( $formFieldTagMatcher, $formFieldHtml, 'Form Field Tag Error');

		$jscFunctionCall = $this->_helper->getJscFunctionCall();
		$this->assertStringEndsWith(");", $jscFunctionCall);
	}
}

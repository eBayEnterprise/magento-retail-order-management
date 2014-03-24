<?php
class TrueAction_Eb2cFraud_Test_Helper_DataTest extends TrueAction_Eb2cCore_Test_Base
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
	public function testGetJavaScriptFraudData()
	{
		$request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
			->disableOriginalConstructor()
			->setMethods(array('getPost'))
			->getMock();
		$request->expects($this->exactly(2))
			->method('getPost')
			->will($this->returnValueMap(array(
				array('eb2cszyvl', '', 'random_field_name'),
				array('random_field_name', '', 'javascript_data'),
			)));
		$this->assertSame(
			'javascript_data',
			Mage::helper('eb2cfraud')->getJavaScriptFraudData($request)
		);
	}
}

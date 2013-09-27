<?php
/**
 */
class TrueAction_Eb2cTax_Test_Helper_Overrides_DataTest
	extends TrueAction_Eb2cCore_Test_Base
{

	public function setUp()
	{
		parent::setUp();
		// make sure there's a fresh instance of the tax helper for each test
		Mage::unregister('_helper/tax');
	}

	/**
	 * Mock out the core config registry used to retrieve config values in the helper.
	 * $config arg will be used as the returnValueMap for the config_registry's
	 * magic __get method.
	 * @param array $config
	 * @return Mock_Eb2cCore_Model_Config_Registry
	 */
	protected function _mockConfig($config)
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get', 'addConfigModel', 'setStore'))
			->getMock();
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($config));
		$mock->expects($this->any())
			->method('addConfigModel')
			->will($this->returnSelf());
		// ensure the config model's "store" gets set before retrieving the value
		$mock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(null))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
		return $mock;
	}

	/**
	 * @test
	 */
	public function testRewrite()
	{
		$this->assertInstanceOf('TrueAction_Eb2cTax_Overrides_Helper_Data', Mage::helper('tax'));
	}

	/**
	 * Test the retrieval of the tax caluculation sequence config value. Expecting true
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testGetApplyTaxAfterDiscount($configValue)
	{
		$this->_mockConfig(array(
			array('taxApplyAfterDiscount', $configValue),
		));
		$val = Mage::helper('tax')->getApplyTaxAfterDiscount();
		$this->assertSame($configValue, $val);
	}

	/**
	 * @test
	 */
	public function testNamespaceUri()
	{
		$this->_mockConfig(array(
			array('apiNamespace', 'http://api.gsicommerce.com/schema/checkout/1.0'),
		));
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			Mage::helper('tax')->getNamespaceUri()
		);
	}

	/**
	 * @test
	 * @loadFixture sendRequestConfig.yaml
	 */
	public function testSendRequest()
	{
		$requestDocument = new TrueAction_Dom_Document();
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($requestDocument));

		$apiModelMock = $this->getModelMock('eb2ccore/api', array('request', 'setUri'));
		$apiModelMock->expects($this->once())
			->method('setUri')
			->with($this->identicalTo('https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/taxes/quote.xml'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->identicalTo($requestDocument))
			->will($this->returnValue('<foo>something</foo>'));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$taxHelper = Mage::helper('tax');

		$this->assertInstanceOf(
			'TrueAction_Eb2cTax_Model_Response',
			$taxHelper->sendRequest($request)
		);

	}

	/**
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @loadFixture sendRequestConfig.yaml
	 */
	public function testSendRequestWithExceptionThrown()
	{
		$requestDocument = new TrueAction_Dom_Document();

		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue($requestDocument));

		$apiModelMock = $this->getMock('TrueAction_Eb2cCore_Model_Api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->with($this->identicalTo('https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/taxes/quote.xml'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->with($requestDocument)
			->will($this->throwException(new Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$taxHelper = Mage::helper('tax');
		$this->assertInstanceOf(
			'TrueAction_Eb2cTax_Model_Response',
			$taxHelper->sendRequest($request)
		);
	}

	/**
	 * @test
	 */
	public function testGetVatInclusivePricingFlag()
	{
		$this->_mockConfig(array(
			array('taxVatInclusivePricing', false),
		));
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertFalse($val);
	}

	/**
	 * @test
	 */
	public function testGetVatInclusivePricingFlagEnabled()
	{
		$this->_mockConfig(array(
			array('taxVatInclusivePricing', true),
		));
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertTrue($val);
	}

	/**
	 * @test
	 */
	public function testTaxDutyRateCode()
	{
		$code = 'eb2c-duty-amount';
		$this->_mockConfig(array(
			array('taxDutyRateCode', $code),
		));
		$val = Mage::helper('tax')->taxDutyAmountRateCode();
		$this->assertSame($code, $val);
	}
}

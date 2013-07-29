<?php
/**
 */
class TrueAction_Eb2cTax_Test_Helper_Overrides_DataTest extends EcomDev_PHPUnit_Test_Case
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
		$hlpr = Mage::helper('tax');
		$this->assertSame(
			'TrueAction_Eb2cTax_Overrides_Helper_Data',
			get_class($hlpr)
		);
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
	 */
	public function testSendRequest()
	{
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue(new TrueAction_Dom_Document()));

		$apiModelMock = $this->getMock('TrueAction_Eb2cCore_Model_Api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will(
				$this->returnValue('<foo>something</foo>')
			);

		$taxHelper = Mage::helper('tax');
		$taxReflector = new ReflectionObject($taxHelper);
		$apiModel = $taxReflector->getProperty('_apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($taxHelper, $apiModelMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cTax_Model_Response',
			$taxHelper->sendRequest($request)
		);

		// let cover getApiModel
		$apiModel->setValue($taxHelper, null);
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Api',
			$taxHelper->getApiModel()
		);
	}

	/**
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testSendRequestWithExceptionThrown()
	{
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue(new TrueAction_Dom_Document()));

		$apiModelMock = $this->getMock('TrueAction_Eb2cCore_Model_Api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will(
				$this->throwException(new Exception)
			);

		$taxHelper = Mage::helper('tax');
		$taxReflector = new ReflectionObject($taxHelper);
		$apiModel = $taxReflector->getProperty('_apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($taxHelper, $apiModelMock);

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
	 * @loadFixture vatInclusivePricingEnabled.yaml
	 */
	public function testGetVatInclusivePricingFlagEnabled()
	{
		$this->_mockConfig(array(
			array('taxVatInclusivePricing', true),
		));
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertTrue($val);
	}
}
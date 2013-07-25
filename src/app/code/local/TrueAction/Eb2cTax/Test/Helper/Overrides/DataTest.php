<?php
/**
 */
class TrueAction_Eb2cTax_Test_Helper_Overrides_DataTest extends EcomDev_PHPUnit_Test_Case
{
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

	public function testGetCalculationSequence()
	{
		$this->markTestINcomplete();
		$val = Mage::helper('tax')->getCalculationSequence();
		$this->assertTrue($val);
	}

	public function testGetCalculationSequenceFalse()
	{
		$this->markTestIncomplete();
		$val = Mage::helper('tax')->getCalculationSequence();
		$this->assertFalse($val);
	}

	/**
	 * @test
	 */
	public function testNamespaceUri()
	{
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
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertFalse($val);
	}

	/**
	 * @test
	 * @loadFixture vatInclusivePricingEnabled.yaml
	 */
	public function testGetVatInclusivePricingFlagEnabled()
	{
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertTrue($val);
	}
}
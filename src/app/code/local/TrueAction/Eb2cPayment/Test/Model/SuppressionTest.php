<?php
class TrueAction_Eb2cPayment_Test_Model_SuppressionTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getAllowedPaymentConfigGroups method
	 * @test
	 */
	public function testGetAllowedPaymentConfigGroups()
	{
		$this->assertSame(
			array('pbridge', 'pbridge_eb2cpayment_cc'),
			Mage::getModel('eb2cpayment/suppression')->getAllowedPaymentConfigGroups()
		);
	}

	/**
	 * Test _getStoreId method
	 * @test
	 */
	public function testGetStoreId()
	{
		$coreModelStoreMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$coreModelStoreMock->expects($this->at(0))
			->method('getId')
			->will($this->returnValue(null));
		$coreModelStoreMock->expects($this->at(1))
			->method('getId')
			->will($this->returnValue(1));
		$coreModelStoreMock->expects($this->at(2))
			->method('getId')
			->will($this->returnValue(2));
		$coreModelStoreMock->expects($this->at(3))
			->method('getId')
			->will($this->returnValue(3));

		$testData = array(
			array('expect' => null, 'parameter' => array('store' => null)),
			array('expect' => null, 'parameter' => array('store' => $coreModelStoreMock)),
			array('expect' => 1, 'parameter' => array('store' => $coreModelStoreMock)),
			array('expect' => 2, 'parameter' => array('store' => $coreModelStoreMock)),
			array('expect' => 3, 'parameter' => array('store' => $coreModelStoreMock)),
		);
		foreach ($testData as $data) {
			$suppression = Mage::getModel('eb2cpayment/suppression', $data['parameter']);
			$this->assertSame($data['expect'], $this->_reflectMethod($suppression, '_getStoreId')->invoke($suppression));
		}
	}

	/**
	 * Test saveEb2CPaymentMethods method
	 * @test
	 */
	public function testSaveEb2CPaymentMethods()
	{
		$coreModelConfigMock = $this->getModelMockBuilder('core/config')
			->disableOriginalConstructor()
			->setMethods(array('saveConfig', 'reinit'))
			->getMock();
		$coreModelConfigMock->expects($this->at(0))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge/active'), $this->equalTo(0), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(1))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge_eb2cpayment_cc/active'), $this->equalTo(0), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(2))
			->method('reinit')
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(3))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge/active'), $this->equalTo(1), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(4))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge_eb2cpayment_cc/active'), $this->equalTo(1), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(5))
			->method('saveConfig')
			->with($this->equalTo('payment/free/active'), $this->equalTo(1), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(6))
			->method('reinit')
			->will($this->returnSelf());

		$suppression = Mage::getModel('eb2cpayment/suppression');

		$this->_reflectProperty($suppression, '_configModel')->setValue($suppression, $coreModelConfigMock);

		foreach (array(0, 1) as $enabled) {
			$this->assertInstanceOf('TrueAction_Eb2cPayment_Model_Suppression', $suppression->saveEb2CPaymentMethods($enabled));
		}
	}


}

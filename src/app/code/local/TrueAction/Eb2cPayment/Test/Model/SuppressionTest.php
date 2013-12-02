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

	/**
	 * Test _getWebsites method
	 * @test
	 */
	public function testGetWebsites()
	{
		$suppression = Mage::getModel('eb2cpayment/suppression');
		$this->assertSame(
			array(),
			$this->_reflectMethod($suppression, '_getWebsites')->invoke($suppression)
		);
	}

	/**
	 * Test disableNonEb2CPaymentMethods method
	 * @test
	 */
	public function testDisableNonEb2CPaymentMethods()
	{
		$coreModelStoreMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$coreModelStoreMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));

		$coreModelAppMock = $this->getModelMockBuilder('core/app')
			->disableOriginalConstructor()
			->setMethods(array('getStores'))
			->getMock();
		$coreModelAppMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue(array($coreModelStoreMock)));

		$coreModelWebsiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'getGroups'))
			->getMock();
		$coreModelWebsiteMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$coreModelWebsiteMock->expects($this->once())
			->method('getGroups')
			->will($this->returnValue(array($coreModelAppMock)));

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('getActivePaymentMethods', '_disablePaymentMethods', '_getWebsites'))
			->getMock();
		$suppressionModelMock->expects($this->at(0))
			->method('getActivePaymentMethods')
			->with($this->equalTo(null))
			->will($this->returnValue(array()));
		$suppressionModelMock->expects($this->at(1))
			->method('_disablePaymentMethods')
			->with($this->isType('array'), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnValue(null));
		$suppressionModelMock->expects($this->at(2))
			->method('_getWebsites')
			->will($this->returnValue(array($coreModelWebsiteMock)));
		$suppressionModelMock->expects($this->at(3))
			->method('getActivePaymentMethods')
			->with($this->isInstanceOf('Mage_Core_Model_Website'))
			->will($this->returnValue(array()));
		$suppressionModelMock->expects($this->at(4))
			->method('_disablePaymentMethods')
			->with($this->isType('array'), $this->equalTo('websites'), $this->equalTo(1))
			->will($this->returnValue(null));
		$suppressionModelMock->expects($this->at(5))
			->method('getActivePaymentMethods')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue(array()));
		$suppressionModelMock->expects($this->at(6))
			->method('_disablePaymentMethods')
			->with($this->isType('array'), $this->equalTo('stores'), $this->equalTo(1))
			->will($this->returnValue(null));

		$coreModelConfigMock = $this->getModelMockBuilder('core/config')
			->disableOriginalConstructor()
			->setMethods(array('reinit'))
			->getMock();
		$coreModelConfigMock->expects($this->exactly(2))
			->method('reinit')
			->will($this->returnSelf());

		$this->_reflectProperty($suppressionModelMock, '_configModel')->setValue($suppressionModelMock, $coreModelConfigMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Suppression',
			$suppressionModelMock->disableNonEb2CPaymentMethods()
		);
	}

	/**
	 * Test _disablePaymentMethods method
	 * @test
	 */
	public function testDisablePaymentMethods()
	{
		$testData = array(
			array(
				'methodConfigs' => array(
					'pbridge' => array('active' => 1),
					'pbridge_eb2cpayment_cc' => array('active' => 1),
					'paypal_express' => array('active' => 1),
					'free' => array('active' => 1)
				),
				'scope' => 'default',
				'scopeId' => 0
			),
			array(
				'methodConfigs' => array(
					'pbridge' => array('active' => 1),
					'pbridge_eb2cpayment_cc' => array('active' => 1),
					'paypal_express' => array('active' => 1),
					'free' => array('active' => 1)
				),
				'scope' => 'websites',
				'scopeId' => 1
			),
			array(
				'methodConfigs' => array(
					'pbridge' => array('active' => 1),
					'pbridge_eb2cpayment_cc' => array('active' => 1),
					'paypal_express' => array('active' => 1),
					'free' => array('active' => 1)
				),
				'scope' => 'stores',
				'scopeId' => 2
			)
		);

		$coreModelConfigMock = $this->getModelMockBuilder('core/config')
			->disableOriginalConstructor()
			->setMethods(array('saveConfig'))
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
			->method('saveConfig')
			->with($this->equalTo('payment/paypal_express/active'), $this->equalTo(0), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(3))
			->method('saveConfig')
			->with($this->equalTo('payment/free/active'), $this->equalTo(0), $this->equalTo('default'), $this->equalTo(0))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(4))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge/active'), $this->equalTo(0), $this->equalTo('websites'), $this->equalTo(1))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(5))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge_eb2cpayment_cc/active'), $this->equalTo(0), $this->equalTo('websites'), $this->equalTo(1))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(6))
			->method('saveConfig')
			->with($this->equalTo('payment/paypal_express/active'), $this->equalTo(0), $this->equalTo('websites'), $this->equalTo(1))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(7))
			->method('saveConfig')
			->with($this->equalTo('payment/free/active'), $this->equalTo(0), $this->equalTo('websites'), $this->equalTo(1))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(8))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge/active'), $this->equalTo(0), $this->equalTo('stores'), $this->equalTo(2))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(9))
			->method('saveConfig')
			->with($this->equalTo('payment/pbridge_eb2cpayment_cc/active'), $this->equalTo(0), $this->equalTo('stores'), $this->equalTo(2))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(10))
			->method('saveConfig')
			->with($this->equalTo('payment/paypal_express/active'), $this->equalTo(0), $this->equalTo('stores'), $this->equalTo(2))
			->will($this->returnSelf());
		$coreModelConfigMock->expects($this->at(11))
			->method('saveConfig')
			->with($this->equalTo('payment/free/active'), $this->equalTo(0), $this->equalTo('stores'), $this->equalTo(2))
			->will($this->returnSelf());

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('isMethodAllowed'))
			->getMock();
		$suppressionModelMock->expects($this->exactly(12))
			->method('isMethodAllowed')
			->will($this->returnValue(false));

		$this->_reflectProperty($suppressionModelMock, '_configModel')->setValue($suppressionModelMock, $coreModelConfigMock);

		foreach ($testData as $data) {
			$this->_reflectMethod($suppressionModelMock, '_disablePaymentMethods')
				->invoke($suppressionModelMock, $data['methodConfigs'], $data['scope'], $data['scopeId']);
		}
	}

	/**
	 * Test isEbcPaymentConfigured method
	 * @test
	 */
	public function testIsEbcPaymentConfigured()
	{
		$coreModelConfigRegistryMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'addConfigModel'))
			->getMock();
		$coreModelConfigRegistryMock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$coreModelConfigRegistryMock->expects($this->once())
			->method('addConfigModel')
			->with($this->isInstanceOf('TrueAction_Eb2cPayment_Model_Method_Config'))
			->will($this->returnValue((object) array(
				'pbridgeActive' => '1',
				'pbridgeMerchantCode' => 'Fake-Merch',
				'pbridgeMerchantKey' => 'Fake-Merch-Key',
				'pbridgeGatewayUrl' => 'https://fake-url.com',
				'pbridgeTransferKey' => 'Fake-trans-key',
				'ebcPbridgeActive' => '1',
				'ebcPbridgeTitle' => 'eBay Enterprise Credit Cards'
			)));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $coreModelConfigRegistryMock);

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('_getStoreId'))
			->getMock();
		$suppressionModelMock->expects($this->once())
			->method('_getStoreId')
			->will($this->returnValue(true));

		$this->assertSame(true, $suppressionModelMock->isEbcPaymentConfigured());
	}

	/**
	 * Test _getStores method
	 * @test
	 */
	public function testGetStores()
	{
		$suppression = Mage::getModel('eb2cpayment/suppression');
		$this->assertSame(
			array(),
			$this->_reflectMethod($suppression, '_getStores')->invoke($suppression)
		);
	}

	/**
	 * Test isAnyNonEb2CPaymentMethodEnabled method, return true
	 * @test
	 */
	public function testIsAnyNonEb2CPaymentMethodEnabledReturnTrue()
	{
		$coreModelStoreMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('getActivePaymentMethods', 'isMethodAllowed', '_getStores'))
			->getMock();
		$suppressionModelMock->expects($this->once())
			->method('getActivePaymentMethods')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue(array('pbridge' => array('active' => 1))));
		$suppressionModelMock->expects($this->once())
			->method('isMethodAllowed')
			->with($this->equalTo('pbridge'))
			->will($this->returnValue(false));
		$suppressionModelMock->expects($this->once())
			->method('_getStores')
			->will($this->returnValue(array($coreModelStoreMock)));

		$this->assertSame(true, $suppressionModelMock->isAnyNonEb2CPaymentMethodEnabled());
	}

	/**
	 * Test isAnyNonEb2CPaymentMethodEnabled method, return false
	 * @test
	 */
	public function testIsAnyNonEb2CPaymentMethodEnabledReturnFalse()
	{
		$coreModelStoreMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('getActivePaymentMethods', 'isMethodAllowed', '_getStores'))
			->getMock();
		$suppressionModelMock->expects($this->once())
			->method('getActivePaymentMethods')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue(array('pbridge' => array('active' => 1))));
		$suppressionModelMock->expects($this->once())
			->method('isMethodAllowed')
			->with($this->equalTo('pbridge'))
			->will($this->returnValue(true));
		$suppressionModelMock->expects($this->once())
			->method('_getStores')
			->will($this->returnValue(array($coreModelStoreMock)));

		$this->assertSame(false, $suppressionModelMock->isAnyNonEb2CPaymentMethodEnabled());
	}

	/**
	 * Test getActivePaymentMethods method
	 * @test
	 */
	public function testGetActivePaymentMethods()
	{
		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('getPaymentMethods'))
			->getMock();
		$suppressionModelMock->expects($this->once())
			->method('getPaymentMethods')
			->will($this->returnValue(array(
				'pbridge' => array('active' => '1'),
				'pbridge_eb2cpayment_cc' => array('active' => '0'),
				'paypal_express' => array('active' => '0'),
				'free' => array('active' => '1')
			)));

		$this->assertSame(
			array('pbridge' => array('active' => '1'), 'free' => array('active' => '1')),
			$suppressionModelMock->getActivePaymentMethods(null)
		);
	}

	/**
	 * Test getPaymentMethods method
	 * @test
	 */
	public function testGetPaymentMethods()
	{
		$coreModelStoreMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$coreModelStoreMock->expects($this->once())
			->method('getConfig')
			->with($this->equalTo('payment'))
			->will($this->returnValue(array(
				'pbridge' => array('active' => '1'),
				'pbridge_eb2cpayment_cc' => array('active' => '0'),
				'paypal_express' => array('active' => '0'),
				'free' => array('active' => '1')
			)));

		$suppressionModelMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->setMethods(array('getStore'))
			->getMock();
		$suppressionModelMock->expects($this->once())
			->method('getStore')
			->will($this->returnValue($coreModelStoreMock));

		$this->assertSame(
			array(
				'pbridge' => array('active' => '1'),
				'pbridge_eb2cpayment_cc' => array('active' => '0'),
				'paypal_express' => array('active' => '0'),
				'free' => array('active' => '1')
			),
			$suppressionModelMock->getPaymentMethods(null)
		);
	}

	/**
	 * Test isMethodAllowed method
	 * @test
	 */
	public function testIsMethodAllowed()
	{
		$testData = array(
			array('expect' => true, 'paymentMethodName' => 'pbridge'),
			array('expect' => true, 'paymentMethodName' => 'pbridge_eb2cpayment_cc'),
			array('expect' => false, 'paymentMethodName' => 'authorizenet'),
			array('expect' => true, 'paymentMethodName' => 'paypal_express'),
			array('expect' => false, 'paymentMethodName' => 'ccsave'),
			array('expect' => false, 'paymentMethodName' => 'firstdata'),
			array('expect' => true, 'paymentMethodName' => 'free'),
		);

		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				Mage::getModel('eb2cpayment/suppression')->isMethodAllowed($data['paymentMethodName'])
			);
		}
	}
}

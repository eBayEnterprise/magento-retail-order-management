<?php
class TrueAction_Eb2cPayment_Test_Helper_DataTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @test
	 */
	public function testGetXmlNs()
	{
		$hlpr = Mage::helper('eb2cpayment');
		$this->assertSame('http://api.gsicommerce.com/schema/checkout/1.0', $hlpr->getXmlNs());
		$this->assertSame('http://api.gsicommerce.com/schema/payment/1.0', $hlpr->getPaymentXmlNs());
	}
	/**
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getApiUri'))
			->getMock();
		$coreHelperMock->expects($this->at(0))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('storedvalue/balance/GS'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml'));
		$coreHelperMock->expects($this->at(1))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('storedvalue/redeem/GS'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'));
		$coreHelperMock->expects($this->at(2))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('storedvalue/redeemvoid/GS'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml'));
		$coreHelperMock->expects($this->at(3))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('paypal/doAuth'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/paypal/doAuth.xml'));
		$coreHelperMock->expects($this->at(4))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('paypal/doExpress'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/paypal/doExpress.xml'));
		$coreHelperMock->expects($this->at(5))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('paypal/void'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/paypal/void.xml'));
		$coreHelperMock->expects($this->at(6))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('paypal/getExpress'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/paypal/getExpress.xml'));
		$coreHelperMock->expects($this->at(7))
			->method('getApiUri')
			->with($this->equalTo('payments'), $this->equalTo('paypal/setExpress'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/paypal/setExpress.xml'));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$hlpr = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$hlpr->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'apiService' => 'payments',
				'apiOptStoredValueBalance' => 'storedvalue/balance/GS',
				'apiOptStoredValueRedeem' => 'storedvalue/redeem/GS',
				'apiOptStoredValueRedeemVoid' => 'storedvalue/redeemvoid/GS',
				'apiOptPaypalDoAuthorization' => 'paypal/doAuth',
				'apiOptPaypalDoExpressCheckout' => 'paypal/doExpress',
				'apiOptPaypalDoVoid' => 'paypal/void',
				'apiOptPaypalGetExpressCheckout' => 'paypal/getExpress',
				'apiOptPaypalSetExpressCheckout' => 'paypal/setExpress',
			)));
		$this->_reflectProperty($hlpr, '_operation')->setValue($hlpr, array(
			'get_gift_card_balance' => 'storedvalue/balance/GS',
			'get_gift_card_redeem' => 'storedvalue/redeem/GS',
			'get_gift_card_redeem_void' => 'storedvalue/redeemvoid/GS',
			'get_paypal_do_authorization' => 'paypal/doAuth',
			'get_paypal_do_express_checkout' => 'paypal/doExpress',
			'get_paypal_do_void' => 'paypal/void',
			'get_paypal_get_express_checkout' => 'paypal/getExpress',
			'get_paypal_set_express_checkout' => 'paypal/setExpress',
		));

		$testData = array(
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml', 'optIndex' => 'get_gift_card_balance'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml', 'optIndex' => 'get_gift_card_redeem'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml', 'optIndex' => 'get_gift_card_redeem_void'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/paypal/doAuth.xml', 'optIndex' => 'get_paypal_do_authorization'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/paypal/doExpress.xml', 'optIndex' => 'get_paypal_do_express_checkout'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/paypal/void.xml', 'optIndex' => 'get_paypal_do_void'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/paypal/getExpress.xml', 'optIndex' => 'get_paypal_get_express_checkout'),
			array('expect' => 'https://api.example.com/vM.m/stores/storeId/payments/paypal/setExpress.xml', 'optIndex' => 'get_paypal_set_express_checkout'),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $hlpr->getOperationUri($data['optIndex']));
		}
	}

	/**
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider dataProvider
	 */
	public function testGetRequestId($incrementId)
	{
		$this->assertSame('clientId-storeId-100000060', Mage::helper('eb2cpayment')->getRequestId($incrementId));
	}
	/**
	 * Test that we return the correct SVC url for the given PAN
	 * @test
	 */
	public function testGetSvcUri()
	{
		$hlpr = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getTenderType', 'getOperationUri'))
			->getMock();
		$hlpr->expects($this->exactly(13))
			->method('getTenderType')
			->will($this->returnCallback(function($pan) {
					switch ($pan) {
						case '15':
							return 'GS';
						case '25':
							return 'SP';
						case '35':
							return 'SV';
						case '45':
							return 'VL';
						default:
							return '';
						}
				}));
		$hlpr->expects($this->exactly(12))
			->method('getOperationUri')
			->will($this->returnCallback(function($optIndex) {
					switch ($optIndex) {
						case 'get_gift_card_balance':
							return 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml';
						case 'get_gift_card_redeem':
							return 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml';
						case 'get_gift_card_redeem_void':
							return 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml';
						default:
							return '';
						}
				}));

		$testData = array(
			array(
				'optIndex' => 'get_gift_card_balance',
				'pan' => '15',
				'tenderType' => 'GS',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml'
			),
			array(
				'optIndex' => 'get_gift_card_balance',
				'pan' => '25',
				'tenderType' => 'SP',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/SP.xml'
			),
			array(
				'optIndex' => 'get_gift_card_balance',
				'pan' => '35',
				'tenderType' => 'SV',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/SV.xml'
			),
			array(
				'optIndex' => 'get_gift_card_balance',
				'pan' => '45',
				'tenderType' => 'VL',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/VL.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem',
				'pan' => '15',
				'tenderType' => 'GS',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem',
				'pan' => '25',
				'tenderType' => 'SP',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/SP.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem',
				'pan' => '35',
				'tenderType' => 'SV',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/SV.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem',
				'pan' => '45',
				'tenderType' => 'VL',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/VL.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem_void',
				'pan' => '15',
				'tenderType' => 'GS',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem_void',
				'pan' => '25',
				'tenderType' => 'SP',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/SP.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem_void',
				'pan' => '35',
				'tenderType' => 'SV',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/SV.xml'
			),
			array(
				'optIndex' => 'get_gift_card_redeem_void',
				'pan' => '45',
				'tenderType' => 'VL',
				'expect' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/VL.xml'
			),
			array('optIndex' => 'get_gift_card_balance', 'pan' => '65', 'tenderType' => 'GS', 'expect' => ''),
			// testing when $pan is out of range.
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $hlpr->getSvcUri($data['optIndex'], $data['pan']));
		}
	}

	/**
	 * Test getTenderType method
	 * @test
	 */
	public function testGetTenderType()
	{
		$registryModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$registryModelMock->expects($this->exactly(14))
			->method('getConfig')
			->will($this->returnCallback(function($cfg) {
					switch ($cfg) {
						case 'svc_bin_range_GS':
							return '0-15';
						case 'svc_bin_range_SP':
							return '16-25';
						case 'svc_bin_range_SV':
							return '26-35';
						case 'svc_bin_range_VL':
							return '36-45';
						default:
							return '';
						}
				}));

		$hlpr = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$hlpr->expects($this->exactly(5))
			->method('getConfigModel')
			->will($this->returnValue($registryModelMock));

		$testData = array(
			array('pan' => '15', 'expect' => 'GS'),
			array('pan' => '25', 'expect' => 'SP'),
			array('pan' => '35', 'expect' => 'SV'),
			array('pan' => '45', 'expect' => 'VL'),
			array('pan' => '65', 'expect' => '', 'expect' => ''),
			// testing when $pan is out of range.
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $hlpr->getTenderType($data['pan']));
		}
	}
}

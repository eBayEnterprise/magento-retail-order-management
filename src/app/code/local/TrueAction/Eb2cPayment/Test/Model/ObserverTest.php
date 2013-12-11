<?php
class TrueAction_Eb2cPayment_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_observer;
	protected $_mockObject;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_observer = Mage::getModel('eb2cpayment/observer');
		$this->_mockObject = new TrueAction_Eb2cPayment_Test_Mock_Model_Observer();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
	}

	public function providerRedeemGiftCard()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getId', 'getGiftCards')
		);
		$quoteMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getGiftCards')
			->will($this->returnValue(
				serialize(array(array(
					'i' => 1, 'c' => '4111111ak4idq1111', 'a' => 50.00, 'ba' => 150.00, 'pan' => '4111111ak4idq1111', 'pin' => '5344'
				)))
			));

		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuote')
		);
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		return array(
			array($observerMock)
		);
	}

	/**
	 * testing redeeming gifcard observer method - successful redeem response
	 *
	 * @test
	 * @dataProvider providerRedeemGiftCard
	 * @loadFixture loadConfig.yaml
	 */
	public function testRedeemGiftCard($observer)
	{
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/stored_value_redeem')
			->setMethods(array('getRedeem', 'parseResponse'))
			->getMock();

		$redeemMock->expects($this->any())
			->method('getRedeem')
			->will($this->returnValue('<foo></foo>'));

		$redeemMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'Success', 'pan' => '4111111ak4idq1111', 'pin' => '5344')));

		$this->replaceByMock('model', 'eb2cpayment/stored_value_redeem', $redeemMock);

		$this->assertNull(
			$this->_observer->redeemGiftCard($observer)
		);
	}

	/**
	 * testing redeemGiftCard unsucessful redeem response
	 *
	 * @test
	 * @dataProvider providerRedeemGiftCard
	 * @loadFixture loadConfig.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testRedeemGiftCardFailReponse($observer)
	{
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/stored_value_redeem')
			->setMethods(array('getRedeem', 'parseResponse'))
			->getMock();

		$redeemMock->expects($this->any())
			->method('getRedeem')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'fail', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);
		$this->replaceByMock('model', 'eb2cpayment/stored_value_redeem', $redeemMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'removeFromCart'))
			->getMock();

		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $giftCardAccountMock);

		$this->assertNull(
			$this->_observer->redeemGiftCard($observer)
		);
	}

	public function providerRedeemVoidGiftCard()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getId', 'getGiftCards')
		);
		$quoteMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getGiftCards')
			->will($this->returnValue(
				serialize(array(array(
					'i' => 1, 'c' => '4111111ak4idq1111', 'a' => 50.00, 'ba' => 150.00, 'pan' => '4111111ak4idq1111', 'pin' => '5344'
				)))
			));

		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuote')
		);
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		return array(
			array($observerMock)
		);
	}

	/**
	 * testing RedeemVoiding gifcard observer method - with successful response from eb2c
	 *
	 * @test
	 * @dataProvider providerRedeemVoidGiftCard
	 * @loadFixture loadConfig.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testRedeemVoidGiftCard($observer)
	{

		$redeemVoidMock = $this->getModelMockBuilder('eb2cpayment/stored_value_redeem_void')
			->setMethods(array('getRedeemVoid', 'parseResponse'))
			->getMock();

		$redeemVoidMock->expects($this->any())
			->method('getRedeemVoid')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'success', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$this->replaceByMock('model', 'eb2cpayment/stored_value_redeem_void', $redeemVoidMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'removeFromCart'))
			->getMock();

		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $giftCardAccountMock);

		$this->assertNull(
			$this->_observer->redeemVoidGiftCard($observer)
		);
	}

	/**
	 * testing RedeemVoiding gifcard observer method - with fialure response from eb2c
	 *
	 * @test
	 * @dataProvider providerRedeemVoidGiftCard
	 * @loadFixture loadConfig.yaml
	 */
	public function testRedeemVoidGiftCardWithfailureResponseFromEb2c($observer)
	{
		$redeemVoidMock = $this->getModelMockBuilder('eb2cpayment/stored_value_redeem_void')
			->setMethods(array('getRedeemVoid', 'parseResponse'))
			->getMock();

		$redeemVoidMock->expects($this->any())
			->method('getRedeemVoid')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'fail', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$this->replaceByMock('model', 'eb2cpayment/stored_value_redeem_void', $redeemVoidMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'removeFromCart'))
			->getMock();

		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $giftCardAccountMock);

		$this->assertNull(
			$this->_observer->redeemVoidGiftCard($observer)
		);
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_observer;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_observer = Mage::getModel('eb2cpayment/observer');
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
		$redeemMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem',
			array('getRedeem', 'parseResponse')
		);
		$redeemMock->expects($this->any())
			->method('getRedeem')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'Success', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$observerReflector = new ReflectionObject($this->_observer);

		// before we mock stored value redeem class in the observer let check it's object just to cover the code.
		$getStoredValueRedeem = $observerReflector->getMethod('_getStoredValueRedeem');
		$getStoredValueRedeem->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem',
			$getStoredValueRedeem->invoke($this->_observer)
		);

		$storedValueRedeem = $observerReflector->getProperty('_storedValueRedeem');
		$storedValueRedeem->setAccessible(true);
		$storedValueRedeem->setValue($this->_observer, $redeemMock);

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
		$redeemMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem',
			array('getRedeem', 'parseResponse')
		);
		$redeemMock->expects($this->any())
			->method('getRedeem')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'fail', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$observerReflector = new ReflectionObject($this->_observer);
		$storedValueRedeem = $observerReflector->getProperty('_storedValueRedeem');
		$storedValueRedeem->setAccessible(true);
		$storedValueRedeem->setValue($this->_observer, $redeemMock);

		// before we mock gift accound class in the observer let check it's object just to cover the code.
		$getGiftCardAccount = $observerReflector->getMethod('_getGiftCardAccount');
		$getGiftCardAccount->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$getGiftCardAccount->invoke($this->_observer)
		);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'removeFromCart')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$giftCardAccount = $observerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_observer, $giftCardAccountMock);

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

		$redeemVoidMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void',
			array('getRedeemVoid', 'parseResponse')
		);
		$redeemVoidMock->expects($this->any())
			->method('getRedeemVoid')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'success', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$observerReflector = new ReflectionObject($this->_observer);

		// before we mock redeem void class in the observer let check it's object just to cover the code.
		$getStoredValueRedeemVoid = $observerReflector->getMethod('_getStoredValueRedeemVoid');
		$getStoredValueRedeemVoid->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void',
			$getStoredValueRedeemVoid->invoke($this->_observer)
		);

		$storedValueRedeemVoid = $observerReflector->getProperty('_storedValueRedeemVoid');
		$storedValueRedeemVoid->setAccessible(true);
		$storedValueRedeemVoid->setValue($this->_observer, $redeemVoidMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'removeFromCart')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$giftCardAccount = $observerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_observer, $giftCardAccountMock);

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

		$redeemVoidMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void',
			array('getRedeemVoid', 'parseResponse')
		);
		$redeemVoidMock->expects($this->any())
			->method('getRedeemVoid')
			->will($this->returnValue('<foo></foo>')
			);
		$redeemVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('responseCode' => 'fail', 'pan' => '4111111ak4idq1111', 'pin' => '5344'))
			);

		$observerReflector = new ReflectionObject($this->_observer);

		// before we mock redeem void class in the observer let check it's object just to cover the code.
		$getStoredValueRedeemVoid = $observerReflector->getMethod('_getStoredValueRedeemVoid');
		$getStoredValueRedeemVoid->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void',
			$getStoredValueRedeemVoid->invoke($this->_observer)
		);

		$storedValueRedeemVoid = $observerReflector->getProperty('_storedValueRedeemVoid');
		$storedValueRedeemVoid->setAccessible(true);
		$storedValueRedeemVoid->setValue($this->_observer, $redeemVoidMock);

		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'removeFromCart')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('removeFromCart')
			->will($this->returnSelf()
			);

		$giftCardAccount = $observerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_observer, $giftCardAccountMock);

		$this->assertNull(
			$this->_observer->redeemVoidGiftCard($observer)
		);
	}
}

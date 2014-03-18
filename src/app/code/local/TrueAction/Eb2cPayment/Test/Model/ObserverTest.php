<?php
class TrueAction_Eb2cPayment_Test_Model_ObserverTest
	extends TrueAction_Eb2cCore_Test_Base
{
	protected $_observer;
	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 * @return void
	 */
	public function replaceByMockCheckoutSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addSuccess', 'addError', 'addException', 'getQuoteId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('addSuccess')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addException')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getQuoteId')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
	}

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_observer = Mage::getModel('eb2cpayment/observer');
		$this->replaceByMockCheckoutSessionModel();
	}

	public function providerRedeemGiftCard()
	{
		$quote = $this->getModelMock('sales/quote', array('getId', 'save'));
		$quote
			->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$quote->setGiftCards(serialize(array(array(
			'a' => 50.00,
			'ba' => 150.00,
			'c' => '4111111ak4idq1111',
			'i' => 1,
			'pan' => '4111111ak4idq1111',
			'pin' => '5344',
		))));
		$eventMock = $this->getMock('Varien_Event', array('getQuote'));
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
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
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('getRedeem', 'parseResponse'))
			->getMock();
		$redeemReply = '<foo></foo>';
		$redeemMock->expects($this->any())
			->method('getRedeem')
			->with($this->identicalTo('4111111ak4idq1111'), $this->identicalTo('5344'), $this->identicalTo(1))
			->will($this->returnValue($redeemReply));
		$expectedPanToken = 'panToken123';
		$redeemMock->expects($this->any())
			->method('parseResponse')
			->with($this->identicalTo($redeemReply))
			->will($this->returnValue(array('responseCode' => 'Success', 'pan' => '4111111ak4idq1111', 'pin' => '5344', 'paymentAccountUniqueId' => $expectedPanToken)));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);
		$this->_observer->redeemGiftCard($observer);
		// use getData to override mock.
		$allResultGiftCards = unserialize($observer->getEvent()->getQuote()->getGiftCards());
		$firstResultGiftCard = array_shift($allResultGiftCards);
		$this->assertSame($expectedPanToken, $firstResultGiftCard['panToken']);
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
		$redeemMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
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
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem', $redeemMock);

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

		$redeemVoidMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem_void')
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

		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem_void', $redeemVoidMock);

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
		$redeemVoidMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem_void')
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

		$this->replaceByMock('model', 'eb2cpayment/storedvalue_redeem_void', $redeemVoidMock);

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
	 * Test TrueAction_Eb2cPayment_Model_Observer::suppressPaymentModule method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cPayment_Model_Observer::suppressPaymentModule will be invoked by this
	 *                test given a mocked Varien_Event_Observer object in which the method
	 *                Varien_Event_Observer::getEvent will be invoked and return a mocked Varien_Event object
	 *                in which the mehods Varien_Event::getStore, getWebsite will be invoked once and return null
	 *                so that the methods TrueAction_Eb2cCore_Helper_Data::getDefaultStore, and getDefaultWebsite will
	 *                will return mocked Mage_Core_Model_Store and Mage_Core_Model_Website respectively
	 * Expectation 2: the method TrueAction_Eb2cPayment_Helper_Data::getConfigModel will be invoked given the mocked
	 *                Mage_Core_Model_Store object in which it will return the mocked TrueAction_Eb2cCore_Model_Config_Registry
	 *                object with the magic property 'isPaymentEnabled' set to true, which will allowed the following methods
	 *                to be invoked TrueAction_Eb2cPayment_Model_Suppression::disableNonEb2CPaymentMethods, and saveEb2CPaymentMethods
	 *                given the value 1
	 */
	public function testSuppressPaymentModule()
	{
		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getStore', 'getWebsite'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getStore')
			->will($this->returnValue(null));
		$eventMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue(null));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultStore', 'getDefaultWebsite'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getDefaultStore')
			->will($this->returnValue($storeMock));
		$coreHelperMock->expects($this->once())
			->method('getDefaultWebsite')
			->will($this->returnValue($websiteMock));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeMock))
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'isPaymentEnabled' => true
			))));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$suppressionMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->disableOriginalConstructor()
			->setMethods(array('disableNonEb2CPaymentMethods', 'saveEb2CPaymentMethods'))
			->getMock();
		$suppressionMock->expects($this->once())
			->method('disableNonEb2CPaymentMethods')
			->will($this->returnSelf());
		$suppressionMock->expects($this->once())
			->method('saveEb2CPaymentMethods')
			->with($this->identicalTo(1))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppressionMock);

		$oMock = $this->getModelMockBuilder('eb2cpayment/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($oMock, $oMock->suppressPaymentModule($observerMock));
	}

	/**
	 * @see self::testSuppressPaymentModule except this time we are testing when the payment is disabled
	 *      and when the Varien_Event::getstore, and getWebsite methods return a mock Mage_Core_Model_Store object
	 *      and a mock Mage_Core_Model_Website object respectively.
	 */
	public function testSuppressPaymentModuleWhenPaymentIsDisabled()
	{
		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getStore', 'getWebsite'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getStore')
			->will($this->returnValue($storeMock));
		$eventMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeMock))
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'isPaymentEnabled' => false
			))));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$suppressionMock = $this->getModelMockBuilder('eb2cpayment/suppression')
			->disableOriginalConstructor()
			->setMethods(array('disableNonEb2CPaymentMethods', 'saveEb2CPaymentMethods'))
			->getMock();
		$suppressionMock->expects($this->once())
			->method('disableNonEb2CPaymentMethods')
			->will($this->returnSelf());
		$suppressionMock->expects($this->once())
			->method('saveEb2CPaymentMethods')
			->with($this->identicalTo(0))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppressionMock);

		$oMock = $this->getModelMockBuilder('eb2cpayment/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($oMock, $oMock->suppressPaymentModule($observerMock));
	}
}

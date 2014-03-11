<?php
class TrueAction_Eb2cPayment_Test_Model_Overrides_ObserverTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::processOrderCreationData method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cPayment_Overrides_Model_Observer::processOrderCreationData will be
	 *                invoked by this test given a mock Varien_Event_Observer object in which the mock
	 *                Varien_Event_Observer::getEvent method will be invoked once and return a Mock Varien_Event object
	 *                in which the method Varien_Event::getRequest will be invoked once which will return an array
	 */
	public function testProcessOrderCreationData()
	{
		$requestData = array();

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getRequest'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($requestData));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_processGiftcardAdd', '_processGiftcardRemove'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_processGiftcardAdd')
			->with($this->identicalTo($observerMock), $this->identicalTo($requestData))
			->will($this->returnSelf());
		$giftcardAccountObserverMock->expects($this->once())
			->method('_processGiftcardRemove')
			->with($this->identicalTo($observerMock), $this->identicalTo($requestData))
			->will($this->returnSelf());

		$this->assertSame(
			$giftcardAccountObserverMock,
			$giftcardAccountObserverMock->processOrderCreationData($observerMock)
		);
	}

	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::_processGiftcardRemove method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cPayment_Overrides_Model_Observer::_processGiftcardRemove will be invoked
	 *                by this test and given a mock object of Varien_Event_Observer and an array with key 'giftcard_remove'
	 *                it will invoked the parent method Enterprise_GiftCardAccount_Model_Observer::processOrderCreationData
	 *                given the mock Varien_Event_Observer object
	 * @see Enterprise_GiftCardAccount_Model_Observer::processOrderCreationData
	 * @mock Enterprise_GiftCardAccount_Model_Observer::_getModel
	 * @mock Varien_Event::getOrderCreateModel()
	 * @mock Mage_Sales_Model_Order::getQuote()
	 * @mock Enterprise_GiftCardAccount_Model_Giftcardaccount::loadByCode
	 * @mock Enterprise_GiftCardAccount_Model_Giftcardaccount::removeFromCart
	 */
	public function testProcessGiftcardRemove()
	{
		$requestData = array('giftcard_remove' => '000000000000000000');
		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getOrderCreateModel', 'getRequest'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getOrderCreateModel')
			->will($this->returnValue($orderMock));
		$eventMock->expects($this->once())
			->method('getRequest')
			->will($this->returnValue($requestData));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->exactly(2))
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$giftcardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'removeFromCart'))
			->getMock();
		$giftcardAccountMock->expects($this->once())
			->method('loadByCode')
			->with($this->identicalTo($requestData['giftcard_remove']))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('removeFromCart')
			->with($this->identicalTo(false), $this->identicalTo($quoteMock))
			->will($this->returnSelf());

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_getModel'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getModel')
			->with($this->identicalTo('enterprise_giftcardaccount/giftcardaccount'))
			->will($this->returnValue($giftcardAccountMock));

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_processGiftcardRemove', array($observerMock, $requestData)
		));
	}

	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::_processGiftcardAdd method for the following expectations
	 * Expectation 1: this test will invoke the method TrueAction_Eb2cPayment_Overrides_Model_Observer::_processGiftcardAdd
	 *                given a Varien_Event_Observer object and an array of keys 'giftcard_add' and 'giftcard_pin'
	 *                the method Varien_Event_Observer::getEvent will be called once which will return a mock of
	 *                Varien_Event object in which the method Varien_Event::getOrderCreateModel will be invoked once and
	 *                return a mock Mage_Sales_Model_Order object, then the method Mage_Sales_Model_Order::getQuote will
	 *                be invoked and return a Mage_Sales_Model_Quote object, which will then call the method
	 *                TrueAction_Eb2cCore_Helper_Data::getWebsiteByStoreId which will return a mock object of
	 *                Mage_Core_Model_Website class.
	 * Expectation 2: the website id will be given from calling the method Mage_Core_Model_Website::getId with will be
	 *                pass to the method Enterprise_Giftcardaccount_Model_Giftcardaccount::setWebsiteId and the pan and pin
	 *                will then be given to Enterprise_Giftcardaccount_Model_Giftcardaccount::loadByPanPin method
	 */
	public function testProcessGiftcardAdd()
	{
		$requestData = array('giftcard_add' => '000000000000000000', 'giftcard_pin' => '9884');
		$storeId = 5;
		$websiteId = 1;

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$websiteMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($websiteId));

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getWebsite'))
			->getMock();
		$storeMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$appMock = $this->getModelMockBuilder('core/app')
			->disableOriginalConstructor()
			->setMethods(array('getStore'))
			->getMock();
		$appMock->expects($this->once())
			->method('getStore')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeMock));

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getOrderCreateModel'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getOrderCreateModel')
			->will($this->returnValue($orderMock));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$giftcardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->disableOriginalConstructor()
			->setMethods(array('loadByPanPin', 'addToCart', 'setWebsiteId'))
			->getMock();
		$giftcardAccountMock->expects($this->once())
			->method('loadByPanPin')
			->with($this->identicalTo($requestData['giftcard_add']), $this->identicalTo($requestData['giftcard_pin']))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('setWebsiteId')
			->with($this->identicalTo($websiteId))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('addToCart')
			->with($this->identicalTo(true), $this->identicalTo($quoteMock))
			->will($this->returnSelf());

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_getModel', '_getApp'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getModel')
			->with($this->identicalTo('enterprise_giftcardaccount/giftcardaccount'))
			->will($this->returnValue($giftcardAccountMock));
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getApp')
			->will($this->returnValue($appMock));

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_processGiftcardAdd', array($observerMock, $requestData)
		));
	}

	/**
	 * @see self::testProcessGiftcardAdd except this time will test when Enterprise_GiftCardAccount_Model_Observer::addToCart
	 *      method throw Mage_Core_Exception
	 */
	public function testProcessGiftcardAddWhenAddToCartThrowMageCoreException()
	{
		$requestData = array('giftcard_add' => '000000000000000000', 'giftcard_pin' => '9884');
		$storeId = 5;
		$websiteId = 1;

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$websiteMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($websiteId));

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getWebsite'))
			->getMock();
		$storeMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$appMock = $this->getModelMockBuilder('core/app')
			->disableOriginalConstructor()
			->setMethods(array('getStore'))
			->getMock();
		$appMock->expects($this->once())
			->method('getStore')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeMock));

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getOrderCreateModel'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getOrderCreateModel')
			->will($this->returnValue($orderMock));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$exceptionMessage = 'Unittest add giftcard to cart throw Mage_Core_Exception';

		$sessionQuoteMock = $this->getModelMockBuilder('adminhtml/session_quote')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$sessionQuoteMock->expects($this->once())
			->method('addError')
			->with($this->identicalTo($exceptionMessage))
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'adminhtml/session_quote', $sessionQuoteMock);

		$giftcardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->disableOriginalConstructor()
			->setMethods(array('loadByPanPin', 'addToCart', 'setWebsiteId'))
			->getMock();
		$giftcardAccountMock->expects($this->once())
			->method('loadByPanPin')
			->with($this->identicalTo($requestData['giftcard_add']), $this->identicalTo($requestData['giftcard_pin']))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('setWebsiteId')
			->with($this->identicalTo($websiteId))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('addToCart')
			->with($this->identicalTo(true), $this->identicalTo($quoteMock))
			->will($this->throwException(new Mage_Core_Exception($exceptionMessage)));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_getModel', '_getApp'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getModel')
			->with($this->identicalTo('enterprise_giftcardaccount/giftcardaccount'))
			->will($this->returnValue($giftcardAccountMock));
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getApp')
			->will($this->returnValue($appMock));

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_processGiftcardAdd', array($observerMock, $requestData)
		));
	}

	/**
	 * @see self::testProcessGiftcardAdd except this time will test when Enterprise_GiftCardAccount_Model_Observer::addToCart
	 *      method throw Exception
	 */
	public function testProcessGiftcardAddWhenAddToCartThrowException()
	{
		$requestData = array('giftcard_add' => '000000000000000000', 'giftcard_pin' => '9884');
		$storeId = 5;
		$websiteId = 1;

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$websiteMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($websiteId));

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getWebsite'))
			->getMock();
		$storeMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$appMock = $this->getModelMockBuilder('core/app')
			->disableOriginalConstructor()
			->setMethods(array('getStore'))
			->getMock();
		$appMock->expects($this->once())
			->method('getStore')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeMock));

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getOrderCreateModel'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getOrderCreateModel')
			->will($this->returnValue($orderMock));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$exceptionMessage = 'Cannot apply Gift Card';

		$giftcardAccountHelperMock = $this->getHelperMockBuilder('enterprise_giftcardaccount/data')
			->disableOriginalConstructor()
			->setMethods(array('__'))
			->getMock();
		$giftcardAccountHelperMock->expects($this->once())
			->method('__')
			->with($this->identicalTo($exceptionMessage))
			->will($this->returnValue($exceptionMessage));
		$this->replaceByMock('helper', 'enterprise_giftcardaccount', $giftcardAccountHelperMock);

		try {
			$exception = new Exception($exceptionMessage);
		} catch (Exception $e) {
			$exception = $e;
		}

		$sessionQuoteMock = $this->getModelMockBuilder('adminhtml/session_quote')
			->disableOriginalConstructor()
			->setMethods(array('addException'))
			->getMock();
		$sessionQuoteMock->expects($this->once())
			->method('addException')
			->with($this->identicalTo($exception), $this->identicalTo($exceptionMessage))
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'adminhtml/session_quote', $sessionQuoteMock);

		$giftcardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->disableOriginalConstructor()
			->setMethods(array('loadByPanPin', 'addToCart', 'setWebsiteId'))
			->getMock();
		$giftcardAccountMock->expects($this->once())
			->method('loadByPanPin')
			->with($this->identicalTo($requestData['giftcard_add']), $this->identicalTo($requestData['giftcard_pin']))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('setWebsiteId')
			->with($this->identicalTo($websiteId))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('addToCart')
			->with($this->identicalTo(true), $this->identicalTo($quoteMock))
			->will($this->throwException($exception));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_getModel', '_getApp'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getModel')
			->with($this->identicalTo('enterprise_giftcardaccount/giftcardaccount'))
			->will($this->returnValue($giftcardAccountMock));
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getApp')
			->will($this->returnValue($appMock));

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_processGiftcardAdd', array($observerMock, $requestData)
		));
	}

	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::paymentDataImport method for the following expectations
	 * Expectation 1: this test will invoked the method TrueAction_Eb2cPayment_Overrides_Model_Observer::paymentDataImport
	 *                given a mock object of class Varien_Event_Observer in which the method Varien_Event_Observer::getEvent
	 *                will be called once and return a mocked object of class Varien_Event in which the method
	 *                Varien_Event::getPayment will return Mage_Sales_Model_Quote_Payment mocked object, then the method
	 *                Mage_Sales_Model_Quote_Payment::getQuote will be invoked which will return a mock object of class
	 *                Mage_Sales_Model_Quote
	 */
	public function testPaymentDataImport()
	{
		$customerId = 8;

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getCustomerId'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getCustomerId')
			->will($this->returnValue($customerId));

		$paymentMock = $this->getModelMockBuilder('sales/quote_payment')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$paymentMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getPayment'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getPayment')
			->will($this->returnValue($paymentMock));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_validateGiftcardsInQuote', '_applyGiftCardDataToQuote'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_validateGiftcardsInQuote')
			->with($this->identicalTo($quoteMock))
			->will($this->returnSelf());
		$giftcardAccountObserverMock->expects($this->once())
			->method('_applyGiftCardDataToQuote')
			->with($this->identicalTo($quoteMock), $this->identicalTo($eventMock))
			->will($this->returnSelf());

		$this->assertSame(
			$giftcardAccountObserverMock,
			$giftcardAccountObserverMock->paymentDataImport($observerMock)
		);
	}

	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::_validateGiftcardsInQuote method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cPayment_Overrides_Model_Observer::_validateGiftcardsInQuote get invoked in this
	 *                test given a mocked quote object of class Mage_Sales_Model_Quote, will be passed to the method
	 *                Enterprise_Giftcardaccount_Helper_Data::getCards which will return an array of array of giftcard data
	 *                it will then loop through all the gift cards data and validate them
	 */
	public function testValidateGiftcardsInQuote()
	{
		$giftcards = array(array('pan' => '9999999999999999', 'pin' => '1111'));
		$storeId = 7;
		$websiteId = 9;

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));

		$giftcardAccountHelperMock = $this->getHelperMockBuilder('enterprise_giftcardaccount/data')
			->disableOriginalConstructor()
			->setMethods(array('getCards'))
			->getMock();
		$giftcardAccountHelperMock->expects($this->once())
			->method('getCards')
			->with($this->identicalTo($quoteMock))
			->will($this->returnValue($giftcards));

		$websiteMock = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$websiteMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($websiteId));

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getWebsite'))
			->getMock();
		$storeMock->expects($this->once())
			->method('getWebsite')
			->will($this->returnValue($websiteMock));

		$appMock = $this->getModelMockBuilder('core/app')
			->disableOriginalConstructor()
			->setMethods(array('getStore'))
			->getMock();
		$appMock->expects($this->once())
			->method('getStore')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeMock));

		$giftcardAccountMock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->disableOriginalConstructor()
			->setMethods(array('loadByPanPin', 'setWebsiteId', 'isValid'))
			->getMock();
		$giftcardAccountMock->expects($this->once())
			->method('loadByPanPin')
			->with($this->identicalTo($giftcards[0]['pan']), $this->identicalTo($giftcards[0]['pin']))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('setWebsiteId')
			->with($this->identicalTo($websiteId))
			->will($this->returnSelf());
		$giftcardAccountMock->expects($this->once())
			->method('isValid')
			->with($this->identicalTo(true), $this->identicalTo(true), $this->identicalTo($websiteMock))
			->will($this->returnValue(true));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(array('_getHelper', '_getApp', '_getModel'))
			->getMock();
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getHelper')
			->with($this->identicalTo('enterprise_giftcardaccount'))
			->will($this->returnValue($giftcardAccountHelperMock));
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getApp')
			->will($this->returnValue($appMock));
		$giftcardAccountObserverMock->expects($this->once())
			->method('_getModel')
			->with($this->identicalTo('enterprise_giftcardaccount/giftcardaccount'))
			->will($this->returnValue($giftcardAccountMock));

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_validateGiftcardsInQuote', array($quoteMock)
		));
	}

	/**
	 * Test TrueAction_Eb2cPayment_Overrides_Model_Observer::_applyGiftCardDataToQuote for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cPayment_Overrides_Model_Observer::_applyGiftCardDataToQuote will invoked
	 *                by this test and will be given mocked quote object and a mocked Varien_Event object, the method
	 *                Mage_Sales_Model_Quote::getBaseGiftCardsAmountUsed will be invoked once and will return a known value
	 *                then the method Mage_Sales_Model_Quote::setGiftCardAccountApplied will be called once, given the
	 *                constant value true, the the method Varien_Event::getInput will be called once in which will return a
	 *                Varien_Object
	 */
	public function testApplyGiftCardDataToQuote()
	{
		$amountUsed = 87.88;
		$method = null;

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getBaseGiftCardsAmountUsed', 'setGiftCardAccountApplied'))
			->getMock();
		$quoteMock->expects($this->once())
			->method('getBaseGiftCardsAmountUsed')
			->will($this->returnValue($amountUsed));
		$quoteMock->expects($this->once())
			->method('setGiftCardAccountApplied')
			->with($this->identicalTo(true))
			->will($this->returnSelf());

		$objectMock = $this->getMockBuilder('Varien_Object')
			->disableOriginalConstructor()
			->setMethods(array('getMethod', 'setMethod'))
			->getMock();
		$objectMock->expects($this->once())
			->method('getMethod')
			->will($this->returnValue($method));
		$objectMock->expects($this->once())
			->method('setMethod')
			->with($this->identicalTo('free'))
			->will($this->returnSelf());

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getInput'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getInput')
			->will($this->returnValue($objectMock));

		$giftcardAccountObserverMock = $this->getModelMockBuilder('enterprise_giftcardaccount/observer')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($giftcardAccountObserverMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$giftcardAccountObserverMock, '_applyGiftCardDataToQuote', array($quoteMock, $eventMock)
		));
	}
}

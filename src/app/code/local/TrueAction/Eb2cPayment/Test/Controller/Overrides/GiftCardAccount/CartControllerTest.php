<?php
include(Mage::getBaseDir('app') . DS . 'code/local/TrueAction/Eb2cPayment/controllers/Overrides/GiftCardAccount/CartController.php');
class TrueAction_Eb2cPayment_Test_Controller_Overrides_GiftCardAccount_CartControllerTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	public function getCallback()
	{
		$args = func_get_args();
		return ($args[0] === 'giftcard_code')? '4111111ak4idq1111' : '5344';
	}

	public function mockRequest()
	{
		$requestedRouteMock = $this->getMock(
			'Mage_Core_Controller_Request_Http',
			array('getRequestedRouteName', 'getRequestedControllerName', 'getRequestedActionName', 'getRequest', 'getPost', 'getParam')
		);
		$requestedRouteMock->expects($this->any())
			->method('getRequestedRouteName')
			->will($this->returnValue('no_route'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedControllerName')
			->will($this->returnValue('index'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedActionName')
			->will($this->returnValue('front'));
		$requestedRouteMock->expects($this->any())
			->method('getRequest')
			->will($this->returnSelf());
		$requestedRouteMock->expects($this->any())
			->method('getPost')
			->will($this->returnValue(array('giftcard_code' => '4111111ak4idq1111', 'giftcard_pin' => '5344')));
		$requestedRouteMock->expects($this->any())
			->method('getParam')
			->will($this->returnCallback(array($this, 'getCallback')));

		return $requestedRouteMock;
	}

	public function mockRequestWithPostVarExceedPanMaximum()
	{
		// mock request this time add a giftcard_code that exceed maximum pan length.
		$requestedRouteMock = $this->getMock(
			'Mage_Core_Controller_Request_Http',
			array('getRequestedRouteName', 'getRequestedControllerName', 'getRequestedActionName', 'getRequest', 'getPost')
		);
		$requestedRouteMock->expects($this->any())
			->method('getRequestedRouteName')
			->will($this->returnValue('no_route'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedControllerName')
			->will($this->returnValue('index'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedActionName')
			->will($this->returnValue('front'));
		$requestedRouteMock->expects($this->any())
			->method('getRequest')
			->will($this->returnSelf());
		$requestedRouteMock->expects($this->any())
			->method('getPost')
			->will($this->returnValue(array('giftcard_code' => '000000000000000000000000000000000000000000000000000000000000', 'giftcard_pin' => '5344')));

		return $requestedRouteMock;
	}

	public function mockRequestWithPostVarExceedPinMaximum()
	{
		// mock request this time add a giftcard_pin that exceed maximum pin length.
		$requestedRouteMock = $this->getMock(
			'Mage_Core_Controller_Request_Http',
			array('getRequestedRouteName', 'getRequestedControllerName', 'getRequestedActionName', 'getRequest', 'getPost')
		);
		$requestedRouteMock->expects($this->any())
			->method('getRequestedRouteName')
			->will($this->returnValue('no_route'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedControllerName')
			->will($this->returnValue('index'));
		$requestedRouteMock->expects($this->any())
			->method('getRequestedActionName')
			->will($this->returnValue('front'));
		$requestedRouteMock->expects($this->any())
			->method('getRequest')
			->will($this->returnSelf());
		$requestedRouteMock->expects($this->any())
			->method('getPost')
			->will($this->returnValue(array('giftcard_code' => '4111111ak4idq1111', 'giftcard_pin' => '00000000000000000000000000000000000000')));

		return $requestedRouteMock;
	}

	public function mockResponse()
	{
		$responsedRouteMock = $this->getMock(
			'Mage_Core_Controller_Response_Http',
			array('appendBody', 'sendHeaders', 'sendResponse', 'setRedirect', 'getResponse')
		);
		$responsedRouteMock->expects($this->any())
			->method('appendBody')
			->will($this->returnValue(''));
		$responsedRouteMock->expects($this->any())
			->method('sendHeaders')
			->will($this->returnSelf());
		$responsedRouteMock->expects($this->any())
			->method('sendResponse')
			->will($this->returnSelf());
		$responsedRouteMock->expects($this->any())
			->method('setRedirect')
			->will($this->returnSelf());
		$responsedRouteMock->expects($this->any())
			->method('getResponse')
			->will($this->returnSelf());

		$elementMock = $this->getMock(
			'Mage_Core_Model_Layout_Update',
			array('addHandle', 'load', 'asSimplexml')
		);
		$elementMock->expects($this->any())
			->method('addHandle')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('load')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('asSimplexml')
			->will($this->returnValue(''));

		return $responsedRouteMock;
	}

	public function mockBlock()
	{
		$elementMock = $this->getMock(
			'Mage_Core_Model_Layout_Update',
			array('setSubscriber', 'addHandle', 'load', 'asSimplexml')
		);
		$elementMock->expects($this->any())
			->method('setSubscriber')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('addHandle')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('load')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('asSimplexml')
			->will($this->returnValue(''));

		$updateMock = $this->getMock(
			'Mage_Core_Model_Layout_Update',
			array('addHandle', 'addMessages', 'setEscapeMessageFlag', 'addStorageType')
		);
		$updateMock->expects($this->any())
			->method('addHandle')
			->will($this->returnValue('this is addHandle method'));
		$updateMock->expects($this->any())
			->method('addMessages')
			->will($this->returnValue('this is addMessages method'));
		$updateMock->expects($this->any())
			->method('setEscapeMessageFlag')
			->will($this->returnValue('this is setEscapeMessageFlag method'));
		$updateMock->expects($this->any())
			->method('addStorageType')
			->will($this->returnValue('this is addStorageType method'));

		$blockMock = $this->getMock(
			'Mage_Core_Model_Layout',
			array('getBlock', 'getUpdate', 'getMessagesBlock')
		);
		$blockMock->expects($this->any())
			->method('getBlock')
			->will($this->returnValue($elementMock));

		$blockMock->expects($this->any())
			->method('getUpdate')
			->will($this->returnValue($updateMock));

		$blockMock->expects($this->any())
			->method('getMessagesBlock')
			->will($this->returnValue($updateMock));

		return $blockMock;
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceByMockGiftCardAccountModel()
	{
		$mockGiftCardAccount = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'isValid', 'addToCart'))
			->getMock();

		$mockGiftCardAccount->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf());
		$mockGiftCardAccount->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$mockGiftCardAccount->expects($this->any())
			->method('addToCart')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $mockGiftCardAccount);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceByMockGiftCardAccountModelAddToCartThrowException()
	{
		$mockGiftCardAccount = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'isValid', 'addToCart'))
			->getMock();

		$mockGiftCardAccount->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf());
		$mockGiftCardAccount->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$mockGiftCardAccount->expects($this->any())
			->method('addToCart')
			->will($this->throwException(new Exception));

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $mockGiftCardAccount);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceByMockGiftCardAccountModelIsValidThrowException()
	{
		$mockGiftCardAccount = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(array('loadByPanPin', 'isValid', 'addToCart', 'unsetData'))
			->getMock();

		$mockGiftCardAccount->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf());
		$mockGiftCardAccount->expects($this->any())
			->method('isValid')
			->will($this->throwException(new Mage_Core_Exception));
		$mockGiftCardAccount->expects($this->any())
			->method('addToCart')
			->will($this->returnSelf());
		$mockGiftCardAccount->expects($this->any())
			->method('unsetData')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $mockGiftCardAccount);
	}

	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 *
	 * @return void
	 */
	public function replaceByMockCheckoutSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addSuccess', 'addError', 'addException'))
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
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
	}

	/**
	 * replacing by mock of the Mage_Core_Model_Session class
	 *
	 * @return void
	 */
	public function replaceByMockCoreSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);
	}

	/**
	 * replacing by mock of the Mage_Core_Model_Url class
	 *
	 * @return void
	 */
	public function replaceByMockCoreUrlModel()
	{
		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('checkout/cart'));

		$this->replaceByMock('singleton', 'core/url', $urlMock);
	}

	/**
	 * replacing by mock of the Mage_Core_Model_Layout class
	 *
	 * @return void
	 */
	public function replaceByMockCoreLayoutModel()
	{
		$this->replaceByMock('singleton', 'core/layout', $this->mockBlock());
	}

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	/**
	 * testing addAction method - successfully adding gift card to quote
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddAction()
	{
		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModel();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->addAction());
	}

	/**
	 * testing addAction method - when giftcard code exceed maximum length
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddActionExceedPanMaximum()
	{
		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequestWithPostVarExceedPanMaximum(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModel();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->addAction());
	}

	/**
	 * testing addAction method - exceed pin maximum length
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddActionExceedPinMaximum()
	{
		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequestWithPostVarExceedPinMaximum(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModel();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->addAction());
	}

	/**
	 * testing addAction method - make gift card add to cart throw exception
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddActionAddThrowException()
	{
		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModelAddToCartThrowException();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->addAction());
	}

	/**
	 * testing quickCheckAction method
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testQuickCheckAction()
	{
		Mage::unregister('current_giftcardaccount');

		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModel();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreLayoutModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->quickCheckAction());
	}

	/**
	 * testing quickCheckAction method - with validation exception thrown
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testQuickCheckActionWithException()
	{
		Mage::unregister('current_giftcardaccount');

		$cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$this->replaceByMockGiftCardAccountModelIsValidThrowException();
		$this->replaceByMockCheckoutSessionModel();
		$this->replaceByMockCoreLayoutModel();
		$this->replaceByMockCoreSessionModel();
		$this->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->quickCheckAction());
	}

}

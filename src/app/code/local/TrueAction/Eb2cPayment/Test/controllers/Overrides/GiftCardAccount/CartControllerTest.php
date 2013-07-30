<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
include(Mage::getBaseDir('app') . DS . 'code/local/TrueAction/Eb2cPayment/controllers/Overrides/GiftCardAccount/CartController.php');
class TrueAction_Eb2cPayment_Test_controllers_Overrides_GiftCardAccount_CartControllerTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_cartController;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

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
			->will($this->returnCallback(array($this, 'getCallback'))
		);

		return $requestedRouteMock;
	}

	public function mockResponse()
	{
		$responsedRouteMock = $this->getMock(
			'Mage_Core_Controller_Response_Http',
			array('appendBody')
		);
		$responsedRouteMock->expects($this->any())
			->method('appendBody')
			->will($this->returnValue(''));

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
	 * testing addAction method - successfully adding gift card to quote
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddAction()
	{
		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$cartControllerReflector = new ReflectionObject($this->_cartController);
		$getGiftCardAccountMethod = $cartControllerReflector->getMethod('_getGiftCardAccount');
		$getGiftCardAccountMethod->setAccessible(true);

		// before we set the giftcard account class with a mock let's call it just to cover it.
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$getGiftCardAccountMethod->invoke($this->_cartController)
		);

		// let's mock the enterprise gift card
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'addToCart')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('addToCart')
			->will($this->returnSelf()
			);

		$giftCardAccount = $cartControllerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_cartController, $giftCardAccountMock);

		$this->assertNull($this->_cartController->addAction());
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

		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$requestedRouteMock,
			$this->mockResponse(),
			array()
		);

		$this->assertNull($this->_cartController->addAction());
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

		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$requestedRouteMock,
			$this->mockResponse(),
			array()
		);

		$this->assertNull($this->_cartController->addAction());
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
		// let mock giftcardaccount class to throw  an exception when adding gift card to cart.
		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$cartControllerReflector = new ReflectionObject($this->_cartController);
		// let's mock the enterprise gift card class so that removeFromCart method don't thrown an exception
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'addToCart')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('addToCart')
			->will($this->throwException(new Exception)
			);

		$giftCardAccount = $cartControllerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_cartController, $giftCardAccountMock);

		$this->assertNull($this->_cartController->addAction());
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

		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		// before we set the layout with a mock let's call it just to cover it.
		$this->assertInstanceOf(
			'Mage_Core_Model_Layout',
			$this->_cartController->getLayout()
		);

		$cartControllerReflector = new ReflectionObject($this->_cartController);
		$layout = $cartControllerReflector->getProperty('_layout');
		$layout->setAccessible(true);
		$layout->setValue($this->_cartController, $this->mockBlock());

		// let's mock the enterprise gift card
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'isValid')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('isValid')
			->will($this->returnSelf()
			);

		$giftCardAccount = $cartControllerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_cartController, $giftCardAccountMock);

		$this->assertNull($this->_cartController->quickCheckAction());
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

		$this->_cartController = new TrueAction_Eb2cPayment_Overrides_GiftCardAccount_CartController(
			$this->mockRequest(),
			$this->mockResponse(),
			array()
		);

		$cartControllerReflector = new ReflectionObject($this->_cartController);
		$layout = $cartControllerReflector->getProperty('_layout');
		$layout->setAccessible(true);
		$layout->setValue($this->_cartController, $this->mockBlock());

		// let's mock the enterprise gift card class so that isValid method don't thrown an exception
		$giftCardAccountMock = $this->getMock(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			array('loadByPanPin', 'isValid')
		);
		$giftCardAccountMock->expects($this->any())
			->method('loadByPanPin')
			->will($this->returnSelf()
			);
		$giftCardAccountMock->expects($this->any())
			->method('isValid')
			->will($this->throwException(new Mage_Core_Exception)
			);

		$giftCardAccount = $cartControllerReflector->getProperty('_giftCardAccount');
		$giftCardAccount->setAccessible(true);
		$giftCardAccount->setValue($this->_cartController, $giftCardAccountMock);

		$this->assertNull($this->_cartController->quickCheckAction());
	}
}

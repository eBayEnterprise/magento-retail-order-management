<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
include(Mage::getBaseDir('app') . DS . 'code/local/TrueAction/Eb2cPayment/controllers/Overrides/GiftCardAccount/CartController.php');
class TrueAction_Eb2cPayment_Test_Controller_Overrides_GiftCardAccount_CartControllerTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_mockObject;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_mockObject = new TrueAction_Eb2cPayment_Test_Mock_Controller_GiftCardAccount_CartController();
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
			$this->_mockObject->mockRequest(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModel();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

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
			$this->_mockObject->mockRequestWithPostVarExceedPanMaximum(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModel();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

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
			$this->_mockObject->mockRequestWithPostVarExceedPinMaximum(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModel();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

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
			$this->_mockObject->mockRequest(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModelAddToCartThrowException();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

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
			$this->_mockObject->mockRequest(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModel();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreLayoutModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

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
			$this->_mockObject->mockRequest(),
			$this->_mockObject->mockResponse(),
			array()
		);

		$this->_mockObject->replaceByMockGiftCardAccountModelIsValidThrowException();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
		$this->_mockObject->replaceByMockCoreLayoutModel();
		$this->_mockObject->replaceByMockCoreSessionModel();
		$this->_mockObject->replaceByMockCoreUrlModel();

		$this->assertNull($cartController->quickCheckAction());
	}

}

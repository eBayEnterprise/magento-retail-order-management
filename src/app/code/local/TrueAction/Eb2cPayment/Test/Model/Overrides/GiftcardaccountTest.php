<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_giftCardAccount;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');

		$balance = Mage::getModel('eb2cpayment/stored_value_balance');

		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$balanceReflector = new ReflectionObject($balance);
		$helper = $balanceReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($balance, $paymentHelper);

		$giftCardAccountReflector = new ReflectionObject($this->_giftCardAccount);
		$storedValueBalance = $giftCardAccountReflector->getProperty('_storedValueBalance');
		$storedValueBalance->setAccessible(true);
		$storedValueBalance->setValue($this->_giftCardAccount, $balance);
	}

	public function providerLoadByCode()
	{
		return array(
			array('4111111ak4idq1111')
		);
	}

	/**
	 * testing loadByCode method
	 *
	 * @test
	 * @medium
	 * @dataProvider providerLoadByCode
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testLoadByCode($code)
	{
		// because we are setting the gift card class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);
		$getStoredValueBalance = $giftCardAccountReflector->getMethod('_getStoredValueBalance');
		$getStoredValueBalance->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Balance',
			$getStoredValueBalance->invoke($giftCardAccount)
		);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$this->_giftCardAccount->loadByCode($code)
		);
	}

	public function providerLoadByPanPin()
	{
		return array(
			array('4111111ak4idq1111', '5344')
		);
	}

	/**
	 * testing loadByPanPin method - when there's no prior gift card account in the magento enterprrise database.
	 *
	 * @test
	 * @medium
	 * @dataProvider providerLoadByPanPin
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture emptyEnterpriseGiftCardAccount.yaml
	 */
	public function testLoadByPanPin($pan, $pin)
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$this->_giftCardAccount->loadByPanPin($pan, $pin)
		);
	}

	public function providerAddToCart()
	{
		return array(
			array(true, null)
		);
	}

	/**
	 * testing addToCart method
	 *
	 * @test
	 * @medium
	 * @dataProvider providerAddToCart
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddToCart($saveQuote=true, $quote=null)
	{
		$this->_giftCardAccount->loadByPanPin('4111111ak4idq1111', '5344');

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$this->_giftCardAccount->addToCart($saveQuote, $quote)
		);
	}

	/**
	 * testing addToCart method - make it throw exception when gift card already exist in the cart
	 *
	 * @test
	 * @dataProvider providerAddToCart
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testAddToCartWithException($saveQuote=true, $quote=null)
	{
		$this->_giftCardAccount->loadByPanPin('4111111ak4idq1111', '5344');

		$cardSerialized = array(array('i' => 1));

		$mockHelper = $this->getMock('Enterprise_GiftCardAccount_Helper_Data', array('getCards'));
		$mockHelper->expects($this->any())
			->method('getCards')
			->will($this->returnValue($cardSerialized));

		$giftCardAccountReflector = new ReflectionObject($this->_giftCardAccount);
		$helperProperty = $giftCardAccountReflector->getProperty('_helper');
		$helperProperty->setAccessible(true);
		$helperProperty->setValue($this->_giftCardAccount, $mockHelper);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$this->_giftCardAccount->addToCart($saveQuote, $quote)
		);
	}
}

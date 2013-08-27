<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing _getStoredValueBalance method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 */
	public function testGetStoredValueBalance()
	{
		$giftCardAccount = new TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount();
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);

		$getStoredValueBalance = $giftCardAccountReflector->getMethod('_getStoredValueBalance');
		$getStoredValueBalance->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Balance',
			$getStoredValueBalance->invoke($giftCardAccount)
		);
	}

	/**
	 * testing _getHelper method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 */
	public function testGetHelper()
	{
		$giftCardAccount = new TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount();
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);

		$getHelper = $giftCardAccountReflector->getMethod('_getHelper');
		$getHelper->setAccessible(true);

		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Helper_Data',
			$getHelper->invoke($giftCardAccount)
		);
	}

	/**
	 * testing _filterGiftCardByPanPin method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 */
	public function testFilterGiftCardByPanPin()
	{
		$giftCardAccount = new TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount();
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);

		$filterGiftCardByPanPin = $giftCardAccountReflector->getMethod('_filterGiftCardByPanPin');
		$filterGiftCardByPanPin->setAccessible(true);

		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection',
			$filterGiftCardByPanPin->invoke($giftCardAccount)
		);
	}

	/**
	 * testing _getGiftCardAccountModel method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 */
	public function testGetGiftCardAccountModel()
	{
		$giftCardAccount = new TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount();
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);

		$getGiftCardAccountModel = $giftCardAccountReflector->getMethod('_getGiftCardAccountModel');
		$getGiftCardAccountModel->setAccessible(true);

		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$getGiftCardAccountModel->invoke($giftCardAccount)
		);
	}

	/**
	 * testing giftCardPinByPan method
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testGiftCardPinByPan()
	{
		$giftCardAccount = new TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount();
		$this->assertSame('5344',	$giftCardAccount->giftCardPinByPan('4111111ak4idq1111'));
	}

	/**
	 * testing loadByCode method - With GiftCard Data to be updated
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testLoadByCodeWithGiftData()
	{
		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMock();

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->loadByCode('4111111ak4idq1111')
		);
	}

	/**
	 * testing loadByCode method - Without GiftCard Data to be updated
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testLoadByCodeWithoutGiftData()
	{
		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMockWithoutGiftCardData();

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->loadByCode('4111111ak4idq1111')
		);
	}

	/**
	 * testing addToCart method
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddToCart()
	{
		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMock();

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->addToCart(true, null)
		);
	}

	/**
	 * testing addToCart method - with exception
	 *
	 * @test
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testAddToCartWithException()
	{
		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMockWithException();

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->addToCart(true, null)
		);
	}
}

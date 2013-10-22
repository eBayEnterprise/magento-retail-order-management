<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * testing _filterGiftCardByPanPin method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 */
	public function testFilterGiftCardByPanPin()
	{
		$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
		$giftCardAccountReflector = new ReflectionObject($giftCardAccount);

		$filterGiftCardByPanPin = $giftCardAccountReflector->getMethod('_filterGiftCardByPanPin');
		$filterGiftCardByPanPin->setAccessible(true);

		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection',
			$filterGiftCardByPanPin->invoke($giftCardAccount)
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
		$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
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
		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mockPaymentModelStoredValueBalance->replaceByMockStoredValueBalanceModel();

		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMock();
		$mockGiftCardAccount->replaceEnterpriseGiftCardAccountByMock();

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
		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mockPaymentModelStoredValueBalance->replaceByMockStoredValueBalanceModel();

		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMockWithoutGiftCardData();
		$mockGiftCardAccount->replaceEnterpriseGiftCardAccountByMock();

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
		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mockPaymentModelStoredValueBalance->replaceByMockStoredValueBalanceModel();

		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMock();
		$mockGiftCardAccount->replaceEnterpriseGiftCardAccountByMock();

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
		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mockPaymentModelStoredValueBalance->replaceByMockStoredValueBalanceModel();

		$mockGiftCardAccount = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount();
		$mockGiftCardAccount->replaceGiftCardAccountByMockWithException();
		$mockGiftCardAccount->replaceEnterpriseGiftCardAccountByMock();

		$mockHelper = new TrueAction_Eb2cPayment_Test_Mock_Helper_Data();
		$mockHelper->replaceByMockEnterpriseGiftCardAccountHelper();

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->addToCart(true, null)
		);
	}
}

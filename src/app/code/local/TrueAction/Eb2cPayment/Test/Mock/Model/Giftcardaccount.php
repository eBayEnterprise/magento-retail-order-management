<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceGiftCardAccountByMock()
	{
		$mock = $this->getModelMockBuilder('eb2cpaymentoverrides/giftcardaccount')
			->setMethods(array('_getStoredValueBalance', '_getHelper', '_filterGiftCardByPanPin', '_getCheckoutSession', '_getGiftCardAccountModel', 'isValid'))
			->getMock();

		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mock->expects($this->any())
			->method('_getStoredValueBalance')
			->will($this->returnValue($mockPaymentModelStoredValueBalance->buildEb2cPaymentModelStoredValueBalance()));

		$mockGiftCardAccountHelper = new TrueAction_Eb2cPayment_Test_Mock_Helper_Giftcardaccount_Data();
		$mock->expects($this->any())
			->method('_getHelper')
			->will($this->returnValue($mockGiftCardAccountHelper->buildGiftCardAccountHelper()));

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollection()));

		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));
		$mock->expects($this->any())
			->method('_getGiftCardAccountModel')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelGiftcardaccount()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceGiftCardAccountByMockWithoutGiftCardData()
	{
		$mock = $this->getModelMockBuilder('eb2cpaymentoverrides/giftcardaccount')
			->setMethods(array('_getStoredValueBalance', '_getHelper', '_filterGiftCardByPanPin',  '_getGiftCardAccountModel', 'isValid'))
			->getMock();

		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mock->expects($this->any())
			->method('_getStoredValueBalance')
			->will($this->returnValue($mockPaymentModelStoredValueBalance->buildEb2cPaymentModelStoredValueBalance()));

		$mockGiftCardAccountHelper = new TrueAction_Eb2cPayment_Test_Mock_Helper_Giftcardaccount_Data();
		$mock->expects($this->any())
			->method('_getHelper')
			->will($this->returnValue($mockGiftCardAccountHelper->buildGiftCardAccountHelper()));

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollectionNoCollection()));

		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));

		$mock->expects($this->any())
			->method('_getGiftCardAccountModel')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelGiftcardaccount()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceGiftCardAccountByMockWithException()
	{
		$mock = $this->getModelMockBuilder('eb2cpaymentoverrides/giftcardaccount')
			->setMethods(array('_getStoredValueBalance', '_getHelper', '_filterGiftCardByPanPin', '_getCheckoutSession', '_getGiftCardAccountModel', 'isValid', 'getId'))
			->getMock();

		$mockPaymentModelStoredValueBalance = new TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance();
		$mock->expects($this->any())
			->method('_getStoredValueBalance')
			->will($this->returnValue($mockPaymentModelStoredValueBalance->buildEb2cPaymentModelStoredValueBalance()));

		$mockGiftCardAccountHelper = new TrueAction_Eb2cPayment_Test_Mock_Helper_Giftcardaccount_Data();
		$mock->expects($this->any())
			->method('_getHelper')
			->will($this->returnValue($mockGiftCardAccountHelper->buildGiftCardAccountHelperWithData()));

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollection()));

		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));
		$mock->expects($this->any())
			->method('_getGiftCardAccountModel')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelGiftcardaccount()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$mock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}
}

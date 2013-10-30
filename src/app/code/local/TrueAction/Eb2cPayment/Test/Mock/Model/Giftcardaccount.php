<?php
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
			->setMethods(array('_filterGiftCardByPanPin', '_getCheckoutSession', 'isValid'))
			->getMock();

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollection()));
		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));
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
			->setMethods(array('_filterGiftCardByPanPin',  '_getCheckoutSession', 'isValid'))
			->getMock();

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollectionNoCollection()));

		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));

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
			->setMethods(array('_filterGiftCardByPanPin', '_getCheckoutSession', 'isValid', 'getId'))
			->getMock();

		$mockGiftCardAccountModelCollection = new TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection();
		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($mockGiftCardAccountModelCollection->buildGiftCardAccountModelResourceGiftcardaccountCollection()));
		$mockCheckoutModelSession = new TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($mockCheckoutModelSession->buildCheckoutModelSession()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$mock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}

	/**
	 * replacing by mock of the enterprise_giftcardaccount/giftcardaccount class
	 *
	 * @return void
	 */
	public function replaceEnterpriseGiftCardAccountByMock()
	{
		$mock = $this->getModelMockBuilder('enterprise_giftcardaccount/giftcardaccount')
			->setMethods(
				array(
					'getGiftcardaccountId', 'setGiftcardaccountId', 'setCode', 'setEb2cPan', 'setEb2cPin', 'setStatus',
					'setState', 'setBalance', 'setIsRedeemable', 'setWebsiteId', 'setDateExpires', 'save', 'load'
				))
			->getMock();

		$mock->expects($this->any())
			->method('getGiftcardaccountId')
			->will($this->returnValue(1));
		$mock->expects($this->any())
			->method('setGiftcardaccountId')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setCode')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setEb2cPan')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setEb2cPin')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setState')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setBalance')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setIsRedeemable')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setWebsiteId')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('setDateExpires')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'enterprise_giftcardaccount/giftcardaccount', $mock);
	}
}

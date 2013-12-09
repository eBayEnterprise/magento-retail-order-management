<?php
class TrueAction_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * return a mock of the Enterprise_GiftCardAccount_Model_Giftcardaccount class
	 *
	 * @return Mock_Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function buildGiftCardAccountModelGiftcardaccount()
	{
		$giftCardAccountModelGiftcardaccount = $this->getMock(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			array(
				'load', 'getEb2cPin', 'getGiftcardaccountId', 'setCode', 'setEb2cPan', 'setEb2cPin', 'setStatus', 'setState',
				'setBalance', 'setIsRedeemable', 'setWebsiteId', 'setDateExpires', 'save', 'setGiftcardaccountId',
				'setDateCreated'
			)
		);

		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('getEb2cPin')
			->will($this->returnValue('1234'));
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('getGiftcardaccountId')
			->will($this->returnValue(1));
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setCode')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setEb2cPan')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setEb2cPin')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setState')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setBalance')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setIsRedeemable')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setWebsiteId')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setDateExpires')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setGiftcardaccountId')
			->will($this->returnSelf());
		$giftCardAccountModelGiftcardaccount->expects($this->any())
			->method('setDateCreated')
			->will($this->returnSelf());

		return $giftCardAccountModelGiftcardaccount;
	}

	/**
	 * return a mock of the Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection class
	 *
	 * @return Mock_Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection
	 */
	public function buildGiftCardAccountModelResourceGiftcardaccountCollection()
	{
		$giftCardAccountModelResourceGiftcardaccountCollectionMock = $this->getMock(
			'Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection',
			array(
				'getSelect', 'where', 'load', 'getFirstItem', '_initSelect', 'count',
			)
		);
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('getSelect')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('where')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('getFirstItem')
			->will($this->returnValue($this->buildGiftCardAccountModelGiftcardaccount()));
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('_initSelect')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('count')
			->will($this->returnValue(1));

		return $giftCardAccountModelResourceGiftcardaccountCollectionMock;
	}

	/**
	 * return a mock of the Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection class
	 *
	 * @return Mock_Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection
	 */
	public function buildGiftCardAccountModelResourceGiftcardaccountCollectionNoCollection()
	{
		$giftCardAccountModelResourceGiftcardaccountCollectionMock = $this->getMock(
			'Enterprise_GiftCardAccount_Model_Resource_Giftcardaccount_Collection',
			array(
				'getSelect', 'where', 'load', 'getFirstItem', '_initSelect', 'count',
			)
		);
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('getSelect')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('where')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('getFirstItem')
			->will($this->returnValue($this->buildGiftCardAccountModelGiftcardaccount()));
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('_initSelect')
			->will($this->returnSelf());
		$giftCardAccountModelResourceGiftcardaccountCollectionMock->expects($this->any())
			->method('count')
			->will($this->returnValue(0));

		return $giftCardAccountModelResourceGiftcardaccountCollectionMock;
	}

	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 * @return Mock_Mage_Checkout_Model_Session
	 */
	public function buildCheckoutModelSession()
	{
		$salesModelQuoteMock = $this->getModelMockBuilder('sales/quote')
			->setMethods(array('getStoreId', 'save'))
			->getMock();

		$salesModelQuoteMock->expects($this->any())
			->method('getStoreId')
			->will($this->returnValue(1));
		$salesModelQuoteMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		$checkoutModelSessionMock = $this->getModelMockBuilder('checkout/session')
			->setMethods(array('getQuote'))
			->disableOriginalConstructor()
			->getMock();

		$checkoutModelSessionMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($salesModelQuoteMock));

		return $checkoutModelSessionMock;
	}

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

		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($this->buildGiftCardAccountModelResourceGiftcardaccountCollection()));

		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($this->buildCheckoutModelSession()));
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

		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($this->buildGiftCardAccountModelResourceGiftcardaccountCollectionNoCollection()));

		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($this->buildCheckoutModelSession()));

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

		$mock->expects($this->any())
			->method('_filterGiftCardByPanPin')
			->will($this->returnValue($this->buildGiftCardAccountModelResourceGiftcardaccountCollection()));

		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($this->buildCheckoutModelSession()));
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

	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Stored_Value_Balance class
	 * @return Mock_TrueAction_Eb2cPayment_Model_Stored_Value_Balance
	 */
	public function buildEb2cPaymentModelStoredValueBalance()
	{
		$paymentModelStoredValueBalanceMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Balance',
			array('getBalance', 'parseResponse')
		);

		$paymentModelStoredValueBalanceMock->expects($this->any())
			->method('getBalance')
			->will($this->returnValue(file_get_contents(__DIR__ . '/GiftcardaccountTest/fixtures/StoredValueBalanceReply.xml')));
		$paymentModelStoredValueBalanceMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('pin' => '1234', 'paymentAccountUniqueId' => '4111111ak4idq1111', 'balanceAmount' => 50.00)));

		return $paymentModelStoredValueBalanceMock;
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_do_void class
	 * @return void
	 */
	public function replaceByMockStoredValueBalanceModel()
	{
		$storedValueBalanceMock = $this->getModelMockBuilder('eb2cpayment/stored_value_balance')
			->disableOriginalConstructor()
			->setMethods(array('getBalance', 'parseResponse'))
			->getMock();
		$storedValueBalanceMock->expects($this->any())
			->method('getBalance')
			->will($this->returnValue(file_get_contents(__DIR__ . '/GiftcardaccountTest/fixtures/StoredValueBalanceReply.xml')));
		$storedValueBalanceMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('pin' => '1234', 'paymentAccountUniqueId' => '4111111ak4idq1111', 'balanceAmount' => 50.00)));
		$this->replaceByMock('model', 'eb2cpayment/stored_value_balance', $storedValueBalanceMock);
	}

	/**
	 * testing _filterGiftCardByPanPin method - the reason for this test is because the method will be replace by a mock on all the other tests
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
		$this->replaceByMockStoredValueBalanceModel();

		$this->replaceGiftCardAccountByMock();
		$this->replaceEnterpriseGiftCardAccountByMock();

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
		$this->replaceByMockStoredValueBalanceModel();

		$this->replaceGiftCardAccountByMockWithoutGiftCardData();
		$this->replaceEnterpriseGiftCardAccountByMock();

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
		$this->replaceByMockStoredValueBalanceModel();

		$this->replaceGiftCardAccountByMock();
		$this->replaceEnterpriseGiftCardAccountByMock();

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
		$this->replaceByMockStoredValueBalanceModel();

		$this->replaceGiftCardAccountByMockWithException();
		$this->replaceEnterpriseGiftCardAccountByMock();

		$enterpriseGiftCardAccountHelperMock = $this->getHelperMockBuilder('enterprise_giftcardaccount/data')
			->disableOriginalConstructor()
			->setMethods(array('getCards', 'setCards'))
			->getMock();
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('getCards')
			->will($this->returnValue(array(array('i' => 1))));
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('setCards')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'enterprise_giftcardaccount', $enterpriseGiftCardAccountHelperMock);

		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertInstanceOf(
			'Enterprise_GiftCardAccount_Model_Giftcardaccount',
			$giftCardAccount->addToCart(true, null)
		);
	}
}

<?php
class TrueAction_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify the model rewrite works
	 */
	public function testRewrite()
	{
		$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Giftcardaccount',
			$giftCardAccount
		);
	}

	/**
	 * verify the collection is setup with the correct filters
	 */
	public function testFilterGiftCardByPanPin()
	{
		$collection = $this->getResourceModelMockBuilder('enterprise_giftcardaccount/giftcardaccount_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'load'))
			->getMock();
		$collection->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$collection->expects($this->at(0))
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('eb2c_pan'),
				$this->identicalTo(array('eq' => false))
			)
			->will($this->returnSelf());
		$collection->expects($this->at(1))
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('eb2c_pin'),
				$this->identicalTo(array('eq' => false))
			)
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'enterprise_giftcardaccount/giftcardaccount_collection', $collection);

		$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
		$this->assertSame(
			$collection,
			$this->_reflectMethod($giftCardAccount, '_filterGiftCardByPanPin')->invoke($giftCardAccount)
		);
	}

	/**
	 * verify the collection is used correctly to get the pin
	 */
	public function testGiftCardPinByPan()
	{
		$pan = '4111111ak4idq1111';
		$pin = '5344';
		$record = $this->getMock('Varien_Object', array('getEb2cPin'));
		$record->expects($this->once())
			->method('getEb2cPin')
			->will($this->returnValue($pin));
		$collection = $this->getResourceModelMockBuilder('enterprise_giftcardaccount/giftcardaccount_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'getFirstItem'))
			->getMock();
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('eb2c_pan'),
				$this->identicalTo(array('eq' => $pan))
			)
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('getFirstItem')
			->will($this->returnValue($record));
		$this->replaceByMock('resource_model', 'enterprise_giftcardaccount/giftcardaccount_collection', $collection);

		$giftCardAccount = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');
		$this->assertSame($pin,	$giftCardAccount->giftCardPinByPan($pan));
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

<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Giftcardaccount_Collection extends EcomDev_PHPUnit_Test_Case
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
}

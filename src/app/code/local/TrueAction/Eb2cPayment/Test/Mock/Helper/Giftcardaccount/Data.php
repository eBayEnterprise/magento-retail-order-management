<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Helper_Giftcardaccount_Data extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Enterprise_GiftCardAccount_Helper_Data class
	 *
	 * @return Mock_Enterprise_GiftCardAccount_Helper_Data
	 */
	public function buildGiftCardAccountHelper()
	{
		$helperMock = $this->getMockBuilder('Enterprise_GiftCardAccount_Helper_Data', array('getCards', 'setCards'))
			->disableOriginalConstructor()
			->getMock();
		$helperMock->expects($this->any())
			->method('getCards')
			->will($this->returnValue(array()));
		$helperMock->expects($this->any())
			->method('setCards')
			->will($this->returnValue(null));

		return $helperMock;
	}

	/**
	 * return a mock of the Enterprise_GiftCardAccount_Helper_Data class
	 *
	 * @return Mock_Enterprise_GiftCardAccount_Helper_Data
	 */
	public function buildGiftCardAccountHelperWithData()
	{
		$helperMock = $this->getMockBuilder('Enterprise_GiftCardAccount_Helper_Data', array('getCards', 'setCards'))
			->disableOriginalConstructor()
			->getMock();
		$helperMock->expects($this->any())
			->method('getCards')
			->will($this->returnValue(array(array('i' => 1))));
		$helperMock->expects($this->any())
			->method('setCards')
			->will($this->returnValue(null));

		return $helperMock;
	}
}

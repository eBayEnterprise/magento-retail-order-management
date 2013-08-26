<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Checkout_Session extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 *
	 * @return Mock_Mage_Checkout_Model_Session
	 */
	public function buildCheckoutModelSession()
	{
		$checkoutModelSessionMock = $this->getModelMockBuilder('checkout/session')
			->setMethods(array('getQuote'))
			->disableOriginalConstructor()
			->getMock();

		$mockSalesModelQuote = new TrueAction_Eb2cPayment_Test_Mock_Model_Sales_Quote();
		$checkoutModelSessionMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($mockSalesModelQuote->buildSalesModelQuote()));

		return $checkoutModelSessionMock;
	}
}

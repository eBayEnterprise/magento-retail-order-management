<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Sales_Quote extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the Mage_Sales_Model_Quote class
	 *
	 * @return Mock_Mage_Sales_Model_Quote
	 */
	public function buildSalesModelQuote()
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

		return $salesModelQuoteMock;
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_observer;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_observer = Mage::getModel('eb2cpayment/observer');
	}

	public function providerRedeemGiftCard()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getId', 'getGiftCards')
		);
		$quoteMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getGiftCards')
			->will($this->returnValue(
				serialize(array(array(
					'i' => 1, 'c' => '4111111ak4idq1111', 'a' => 50.00, 'ba' => 150.00, 'pan' => '4111111ak4idq1111', 'pin' => '5344'
				)))
			));

		$observerMock = $this->getMock(
			'Varien_Event',
			array('getEvent', 'getQuote')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnSelf());
		$observerMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		return array(
			array($observerMock)
		);
	}

	/**
	 * testing when eb2c quantity check is out of stock
	 *
	 * @test
	 * @dataProvider providerRedeemGiftCard
	 * @loadFixture loadConfig.yaml
	 */
	public function testRedeemGiftCard($observer)
	{
		$this->assertNull(
			$this->_observer->redeemGiftCard($observer)
		);
	}
}

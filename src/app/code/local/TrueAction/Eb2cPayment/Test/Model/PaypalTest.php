<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_PaypalTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_paypal;

	/**
	 * setUp method
	 **/
	public function setUp()
	{
		parent::setUp();
		$this->_paypal = Mage::getModel('eb2cpayment/paypal');
	}

	/**
	 * empty paypal table
	 *
	 * @test
	 * @loadFixture paypalTableEmpty.yaml
	 */
	public function testPaypalTableEmpty()
	{
		$collection = $this->_paypal->getCollection();

		$this->assertEquals(
			0,
			$collection->count()
		);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal',
			$this->_paypal->loadByQuoteId(1)
		);
	}

	/**
	 * Retrieves list of paypal ids for some purpose
	 *
	 * @test
	 * @loadFixture paypalTableList.yaml
	 */
	public function testPaypalTableList()
	{
		$collection = $this->_paypal->getCollection();
		// Check that number of items the same as expected value
		$this->assertEquals(
			2,
			$collection->count()
		);
	}
}

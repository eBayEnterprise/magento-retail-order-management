<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * tests the tax response orderItem class.
 */
class TrueAction_Eb2cTax_Test_Model_Response_OrderItemTest extends EcomDev_PHPUnit_Test_Case
{

	protected $_orderItem;

	public function setUp()
	{
		parent::setUp();
		$this->_orderItem = Mage::getModel('eb2ctax/response_orderitem');
	}

	/**
	 * Testing _validate method
	 *
	 * @test
	 */
	public function testValidate()
	{
		$orderItemReflector = new ReflectionObject($this->_orderItem);
		$validateMethod = $orderItemReflector->getMethod('_validate');
		$validateMethod->setAccessible(true);

		$this->_orderItem->unsSku()->unsLineNumber();

		$this->assertNull(
			$validateMethod->invoke($this->_orderItem)
		);
	}
}

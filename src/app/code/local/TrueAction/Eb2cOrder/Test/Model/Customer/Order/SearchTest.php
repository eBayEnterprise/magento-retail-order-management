<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cOrder_Test_Model_Customer_Order_SearchTest
	extends TrueAction_Eb2cCore_Test_Base
{
	protected $_search;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_search = Mage::getModel('eb2corder/customer_order_search');
	}
}

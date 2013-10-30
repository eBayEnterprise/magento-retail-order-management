<?php
class TrueAction_Eb2cOrder_Test_Block_Overrides_Order_RecentTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test overriding sales/order_recent block
	 * @test
	 */
	public function testConstruct()
	{
		$customerOrderSearchMock = $this->getModelMockBuilder('eb2corder/customer_order_search')
			->disableOriginalConstructor()
			->setMethods(array('requestOrderSummary', 'parseResponse'))
			->getMock();
		$customerOrderSearchMock->expects($this->once())
			->method('requestOrderSummary')
			->with($this->equalTo(1))
			->will($this->returnValue('<foo></foo>'));
		$customerOrderSearchMock->expects($this->once())
			->method('parseResponse')
			->with($this->equalTo('<foo></foo>'))
			->will($this->returnValue(array('100000038' => new Varien_Object(array(
				'id' => 'order-123',
				'order_type' => 'SALES',
				'test_type' => 'TEST_ORDER',
				'modified_time' => '2011-02-22T22:09:56+00:00',
				'customer_order_id' => '100000038',
				'customer_id' => '1',
				'order_date' => '2011-02-22T22:09:56+00:00',
				'dashboard_rep_id' => 'admin',
				'status' => 'Cancelled',
				'order_total' => 0.0,
				'source' => 'Web',
			)))));
		$this->replaceByMock('model', 'eb2corder/customer_order_search', $customerOrderSearchMock);

		$sessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'customer/session', $sessionMock);

		$orderObject = Mage::getModel('sales/order');
		$orderObject->addData(array(
			'real_order_id' => '100000038',
			'status' => 'pending',
		));

		$newCollection = new Varien_Data_Collection();
		$newCollection->addItem($orderObject);

		$orderRecentBlockMock = $this->getBlockMockBuilder('sales/order_recent')
			->disableOriginalConstructor()
			->setMethods(array('getOrders', 'setOrders', '__construct'))
			->getMock();
		$orderRecentBlockMock->expects($this->any())
			->method('getOrders')
			->will($this->returnValue($newCollection));
		$orderRecentBlockMock->expects($this->any())
			->method('setOrders')
			->with($this->isInstanceOf('Varien_Data_Collection'));
		$orderRecentBlockMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$this->replaceByMock('block', 'sales/order_recent', $orderRecentBlockMock);

		$construct = $this->_reflectMethod($orderRecentBlockMock, '__construct');
		$construct->invoke($orderRecentBlockMock);
	}
}

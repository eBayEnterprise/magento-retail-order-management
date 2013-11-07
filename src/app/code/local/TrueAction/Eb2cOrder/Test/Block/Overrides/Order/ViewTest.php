<?php
class TrueAction_Eb2cOrder_Test_Block_Overrides_Order_ViewTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test overriding sales/order_view block
	 * @test
	 */
	public function testPrepareLayout()
	{
		$coreBlockTemplateMock = $this->getBlockMockBuilder('core/template')
			->disableOriginalConstructor()
			->getMock();

		$paymentHelperMock = $this->getHelperMockBuilder('payment/data')
			->disableOriginalConstructor()
			->setMethods(array('getInfoBlock', 'parseResponse'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getInfoBlock')
			->will($this->returnValue($coreBlockTemplateMock));
		$this->replaceByMock('helper', 'payment', $paymentHelperMock);

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

		$paymentInfoObject = Mage::getModel('sales/order_payment');

		$orderObject = Mage::getModel('sales/order');
		$orderObject->addData(array(
			'real_order_id' => '100000038',
			'status' => 'pending',
			'payment' => $paymentInfoObject,
		));
		$orderObject->setPayment($paymentInfoObject);

		$layoutMock = $this->getModelMockBuilder('core/layout')
			->disableOriginalConstructor()
			->setMethods(array('getBlock', 'setTitle'))
			->getMock();
		$layoutMock->expects($this->any())
			->method('getBlock')
			->with($this->equalTo('head'))
			->will($this->returnSelf());
		$layoutMock->expects($this->any())
			->method('setTitle')
			->with($this->equalTo('Order # 100000038'));

		$orderViewBlockMock = $this->getBlockMockBuilder('sales/order_view')
			->disableOriginalConstructor()
			->setMethods(array('getLayout', 'setChild', 'getOrder', 'setOrders', '__construct'))
			->getMock();
		$orderViewBlockMock->expects($this->any())
			->method('getLayout')
			->will($this->returnValue($layoutMock));
		$orderViewBlockMock->expects($this->any())
			->method('setChild')
			->with($this->equalTo('payment_info'), $this->isInstanceOf('Mage_Core_Block_Template'));
		$orderViewBlockMock->expects($this->any())
			->method('getOrder')
			->will($this->returnValue($orderObject));
		$orderViewBlockMock->expects($this->any())
			->method('setOrders')
			->with($this->isInstanceOf('Varien_Data_Collection'));
		$orderViewBlockMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$this->replaceByMock('block', 'sales/order_view', $orderViewBlockMock);

		$prepareLayout = $this->_reflectMethod($orderViewBlockMock, '_prepareLayout');
		$prepareLayout->invoke($orderViewBlockMock);
	}
}

<?php
class TrueAction_Eb2cOrder_Test_Helper_Overrides_Sales_GuestTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test overriding sales/guest helper, where when there's no post data the parent validator method return false
	 * @test
	 */
	public function testLoadValidOrderParentMethodReturnFalse()
	{
		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('http://exmple.com'));

		$this->replaceByMock('model', 'core/url', $urlMock);

		$coreSessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$coreSessionMock->expects($this->any())
			->method('addError')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'core/session', $coreSessionMock);

		$customerSessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId', 'isLoggedIn'))
			->getMock();
		$customerSessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$customerSessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$customerSessionMock->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(0));
		$this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

		$this->assertSame(false, Mage::helper('sales/guest')->loadValidOrder());
	}

	/**
	 * Test overriding sales/guest helper, when valid post data is passed and the order exists in eb2c oms
	 * @test
	 */
	public function testLoadValidOrderValidPostDataAndOrderExistInOms()
	{
		Mage::unregister('current_order');

		$_POST['oar_type'] = 'email';
		$_POST['oar_order_id'] = '0005406000000';
		$_POST['oar_billing_lastname'] = 'Gabriel';
		$_POST['oar_email'] = 'rgabriel@trueaction.com';
		$_POST['oar_zip'] = '';

		$addressMock = $this->getModelMockBuilder('sales/order_address')
			->disableOriginalConstructor()
			->setMethods(array('getLastname', 'getEmail', 'getPostcode'))
			->getMock();
		$addressMock->expects($this->any())
			->method('getLastname')
			->will($this->returnValue('Gabriel'));
		$addressMock->expects($this->any())
			->method('getEmail')
			->will($this->returnValue('rgabriel@trueaction.com'));
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue(''));
		$this->replaceByMock('model', 'sales/order_address', $addressMock);

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('loadByIncrementId', 'getId', 'getBillingAddress', 'getProtectCode'))
			->getMock();
		$orderMock->expects($this->any())
			->method('loadByIncrementId')
			->will($this->returnSelf());
		$orderMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$orderMock->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($addressMock));
		$orderMock->expects($this->any())
			->method('getProtectCode')
			->will($this->returnValue('abc-123-you-n-me'));
		$this->replaceByMock('model', 'sales/order', $orderMock);

		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('http://exmple.com'));

		$this->replaceByMock('model', 'core/url', $urlMock);

		$coreSessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$coreSessionMock->expects($this->any())
			->method('addError')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'core/session', $coreSessionMock);

		$customerSessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId', 'isLoggedIn'))
			->getMock();
		$customerSessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$customerSessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$customerSessionMock->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(0));
		$this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

		$coreCookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();
		$coreCookieMock->expects($this->any())
			->method('set')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/cookie', $coreCookieMock);

		$customerOrderSearchMock = $this->getModelMockBuilder('eb2corder/customer_order_search')
			->disableOriginalConstructor()
			->setMethods(array('requestOrderSummary', 'parseResponse'))
			->getMock();
		$customerOrderSearchMock->expects($this->once())
			->method('requestOrderSummary')
			->with($this->equalTo(0), $this->equalTo('0005406000000'))
			->will($this->returnValue('<foo></foo>'));
		$customerOrderSearchMock->expects($this->once())
			->method('parseResponse')
			->with($this->equalTo('<foo></foo>'))
			->will($this->returnValue(array('0005406000000' => new Varien_Object(array(
				'id' => 'order-123',
				'order_type' => 'SALES',
				'test_type' => 'TEST_ORDER',
				'modified_time' => '2011-02-22T22:09:56+00:00',
				'customer_order_id' => '0005406000000',
				'customer_id' => '00000',
				'order_date' => '2011-02-22T22:09:56+00:00',
				'dashboard_rep_id' => 'admin',
				'status' => 'Cancelled',
				'order_total' => 0.0,
				'source' => 'Web',
			)))));
		$this->replaceByMock('model', 'eb2corder/customer_order_search', $customerOrderSearchMock);

		$this->assertSame(true, Mage::helper('sales/guest')->loadValidOrder());
	}

	/**
	 * Test overriding sales/guest helper, when valid post data is passed but the order do not exists in eb2c oms
	 * @test
	 */
	public function testLoadValidOrderValidPostDataButOrderDoNotExistInOms()
	{
		Mage::unregister('current_order');

		$_POST['oar_type'] = 'email';
		$_POST['oar_order_id'] = '0005406000000';
		$_POST['oar_billing_lastname'] = 'Gabriel';
		$_POST['oar_email'] = 'rgabriel@trueaction.com';
		$_POST['oar_zip'] = '';

		$addressMock = $this->getModelMockBuilder('sales/order_address')
			->disableOriginalConstructor()
			->setMethods(array('getLastname', 'getEmail', 'getPostcode'))
			->getMock();
		$addressMock->expects($this->any())
			->method('getLastname')
			->will($this->returnValue('Gabriel'));
		$addressMock->expects($this->any())
			->method('getEmail')
			->will($this->returnValue('rgabriel@trueaction.com'));
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue(''));
		$this->replaceByMock('model', 'sales/order_address', $addressMock);

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('loadByIncrementId', 'getId', 'getBillingAddress', 'getProtectCode'))
			->getMock();
		$orderMock->expects($this->any())
			->method('loadByIncrementId')
			->will($this->returnSelf());
		$orderMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$orderMock->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($addressMock));
		$orderMock->expects($this->any())
			->method('getProtectCode')
			->will($this->returnValue('abc-123-you-n-me'));
		$this->replaceByMock('model', 'sales/order', $orderMock);

		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('http://exmple.com'));

		$this->replaceByMock('model', 'core/url', $urlMock);

		$coreSessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$coreSessionMock->expects($this->any())
			->method('addError')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'core/session', $coreSessionMock);

		$customerSessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId', 'isLoggedIn'))
			->getMock();
		$customerSessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$customerSessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$customerSessionMock->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(0));
		$this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

		$coreCookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();
		$coreCookieMock->expects($this->any())
			->method('set')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/cookie', $coreCookieMock);

		$customerOrderSearchMock = $this->getModelMockBuilder('eb2corder/customer_order_search')
			->disableOriginalConstructor()
			->setMethods(array('requestOrderSummary', 'parseResponse'))
			->getMock();
		$customerOrderSearchMock->expects($this->once())
			->method('requestOrderSummary')
			->with($this->equalTo(0), $this->equalTo('0005406000000'))
			->will($this->returnValue('<foo></foo>'));
		$customerOrderSearchMock->expects($this->once())
			->method('parseResponse')
			->with($this->equalTo('<foo></foo>'))
			->will($this->returnValue(array()));
		$this->replaceByMock('model', 'eb2corder/customer_order_search', $customerOrderSearchMock);

		$this->assertSame(false, Mage::helper('sales/guest')->loadValidOrder());
	}
}

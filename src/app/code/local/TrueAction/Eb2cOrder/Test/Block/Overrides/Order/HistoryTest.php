<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cOrder_Test_Block_Overrides_Order_HistoryTest extends TrueAction_Eb2cCore_Test_Base
{
	public function _mockBlock()
	{
		$elementMock = $this->getMock(
			'Mage_Core_Model_Layout_Update',
			array('setSubscriber', 'addHandle', 'load', 'asSimplexml')
		);
		$elementMock->expects($this->any())
			->method('setSubscriber')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('addHandle')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('load')
			->will($this->returnValue(''));
		$elementMock->expects($this->any())
			->method('asSimplexml')
			->will($this->returnValue(''));

		$updateMock = $this->getMock(
			'Mage_Core_Model_Layout_Update',
			array('addHandle', 'addMessages', 'setEscapeMessageFlag', 'addStorageType')
		);
		$updateMock->expects($this->any())
			->method('addHandle')
			->will($this->returnValue('this is addHandle method'));
		$updateMock->expects($this->any())
			->method('addMessages')
			->will($this->returnValue('this is addMessages method'));
		$updateMock->expects($this->any())
			->method('setEscapeMessageFlag')
			->will($this->returnValue('this is setEscapeMessageFlag method'));
		$updateMock->expects($this->any())
			->method('addStorageType')
			->will($this->returnValue('this is addStorageType method'));

		$blockMock = $this->getMock(
			'Mage_Core_Model_Layout',
			array('getBlock', 'getUpdate', 'getMessagesBlock', '_getBlockInstance', 'setIsAnonymous')
		);
		$blockMock->expects($this->any())
			->method('getBlock')
			->will($this->returnValue($elementMock));
		$blockMock->expects($this->any())
			->method('getUpdate')
			->will($this->returnValue($updateMock));
		$blockMock->expects($this->any())
			->method('getMessagesBlock')
			->will($this->returnValue($updateMock));
		$blockMock->expects($this->any())
			->method('_getBlockInstance')
			->will($this->returnSelf());
		$blockMock->expects($this->any())
			->method('setIsAnonymous')
			->will($this->returnSelf());

		return $blockMock;
	}

	/**
	 * Test overriding sales/order_history block
	 * @test
	 */
	public function testOverrideConstrutor()
	{
		$cookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor()
			->setMethods(array('set', 'delete', 'getDomain'))
			->getMock();
		$cookieMock->expects($this->any())
			->method('set')
			->will($this->returnCallback(array($this, 'setCookieCallback')));
		$cookieMock->expects($this->any())
			->method('delete')
			->will($this->returnCallback(array($this, 'deleteCookieCallback')));
		$cookieMock->expects($this->any())
			->method('getDomain')
			->will($this->returnValue('http://fake-domain.com'));
		$this->replaceByMock('singleton', 'core/cookie', $cookieMock);

		$sessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$this->replaceByMock('singleton', 'customer/session', $sessionMock);

		// Stop here and mark this test as incomplete.
		$this->markTestIncomplete('This test has not been implemented yet.');
		Mage::app()->getLayout()->createBlock('sales/order_history'); //new TrueAction_Eb2cOrder_Overrides_Block_Order_History();

	}
}

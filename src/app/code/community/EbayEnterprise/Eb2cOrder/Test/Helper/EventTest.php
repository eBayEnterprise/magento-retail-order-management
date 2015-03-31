<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cOrder_Test_Helper_EventTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var EbayEnterprise_Eb2cOrder_Helper_Event
	public $eventHelper;

	public function setUp()
	{
		$this->eventHelper = Mage::helper('eb2corder/event');
	}
	/**
	 * Test extracting the name of the order event from a DOMXPath wrapping an
	 * order event message.
	 */
	public function testGetMessageEventName()
	{
		$message = '<?xml version="1.0"?>
		<OrderEvent>
			<MessageHeader><HeaderVersion>1.0</HeaderVersion></MessageHeader>
			<OrderAccepted EventType="Accepted"><Order><IncrementId>100001223</IncrementId></Order></OrderAccepted>
		</OrderEvent>';
		$this->assertSame(
			'ebayenterprise_order_event_accepted',
			$this->eventHelper->getMessageEventName($message)
		);
	}
	/**
	 * Attempt to cancel the order.
	 * Check if the order was actually canceled before setting the new status.
	 */
	public function testAttemptCancelOrder()
	{
		$order = $this->getModelMock('sales/order', array('cancel', 'save'));
		$order->expects($this->once())
			->method('cancel')
			->will($this->returnSelf());
		$order->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$this->eventHelper->attemptCancelOrder($order);
	}
	/**
	 * Log a warning if there's an exception and continue on.
	 */
	public function testAttemptCancelOrderException()
	{
		$order = $this->getModelMock('sales/order', array('cancel', 'save'));
		$order->expects($this->once())
			->method('cancel')
			->will($this->returnSelf());
		$order->expects($this->once())
			->method('save')
			->will($this->throwException(Mage::exception('Mage_Core', 'some error')));

		$this->eventHelper->attemptCancelOrder($order);
	}
}

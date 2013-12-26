<?php

class TrueAction_Eb2cCore_Test_Model_ObserverTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function testCheckQuoteForChanges()
	{
		$quote = $this->getModelMock('sales/quote');
		$event = $this->getMock('Varien_Event', array('getQuote'));
		$evtObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$evtObserver->expects($this->any())->method('getEvent')->will($this->returnValue($event));
		$event->expects($this->any())->method('getQuote')->will($this->returnValue($quote));

		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('updateWithQuote'))
			->getMock();
		$session
			->expects($this->once())
			->method('updateWithQuote')
			->with($this->identicalTo($quote))
			->will($this->returnSelf());

		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$observer = Mage::getModel('eb2ccore/observer');
		$this->assertSame($observer, $observer->checkQuoteForChanges($evtObserver));
	}
}
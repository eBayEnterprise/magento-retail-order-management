<?php
class TrueAction_Eb2cCore_Model_Observer
{
	/**
	 * Update the eb2ccore session with the new quote.
	 * @param  Varien_Event_Observer $observer Event observer object containing a quote object
	 * @return TrueAction_Eb2cCore_Model_Observer $this object
	 */
	public function checkQuoteForChanges($observer)
	{
		Mage::getSingleton('eb2ccore/session')->updateWithQuote($observer->getEvent()->getQuote());
		return $this;
	}
}

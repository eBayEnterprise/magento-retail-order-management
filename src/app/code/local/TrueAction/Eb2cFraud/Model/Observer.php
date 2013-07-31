<?php
/**
 *
 */
class TrueAction_Eb2cFraud_Model_Observer
{
	public function captureOrderContext($event)
	{
		$quote = $event['quote'];
		$request = $event['request'];

		$context = Mage::getModel('eb2cfraud/context');

		$quote->setEb2cFraudJavascriptData($request->getPost('eb2cszyvl',array()));
		$quote->setEb2cFraudCharSet($context->getCharSet());
		$quote->setEb2cFraudLanguage($context->getLanguage());

		$quote->save();

		return;
	}
}

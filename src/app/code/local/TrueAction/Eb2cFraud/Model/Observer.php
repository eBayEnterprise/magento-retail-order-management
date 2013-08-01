<?php
/**
 *
 */
class TrueAction_Eb2cFraud_Model_Observer
{
	public function captureOrderContext($observer)
	{
		$quote = $observer->getEvent->getQuote();
		$request = $observer->getEvent->getRequest();

		$context = Mage::getModel('eb2cfraud/context');

		$quote->setEb2cFraudJavascriptData( $request->getPost('eb2cszyvl',array()) );
		$quote->setEb2cFraudCharSet( $context->getCharSet() );
		$quote->setEb2cFraudLanguage( $context->getLanguage() );
		$quote->setEb2cFraudHostName( $context->getHostName() );
		$quote->setEb2cFraudIpAddress( $context->getIpAddress() );
		$quote->setEb2cFraudSessionId( $context->getSessionId() );
		$quote->setEb2cFraudUserAgent( $context->getUserAgent() );
		$quote->setEb2cFraudReferrer( $context->getReferrer() );
		$quote->setEb2cFraudContentTypes( $context->getContentTypes() );
		$quote->setEb2cFraudEncoding( $context->getEncoding() );

		$quote->save();

		return;
	}
}

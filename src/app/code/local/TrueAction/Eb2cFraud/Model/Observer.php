<?php
/**
 *
 */
class TrueAction_Eb2cFraud_Model_Observer
{
	private $_orderContext;

	private function _getOrderContext()
	{
		if( !$this->_orderContext ) {
			$this->_orderContext = Mage::getModel('eb2cfraud/context');
		}
		return $this->_orderContext;
	}

	public function captureOrderContext($observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$request = $observer->getEvent()->getRequest();

		$quote->setEb2cFraudJavascriptData( $request->getPost('eb2cszyvl',array()) );
		$quote->setEb2cFraudCharSet( $this->_getOrderContext()->getCharSet() );
		$quote->setEb2cFraudContentTypes( $this->_getOrderContext()->getContentTypes() );
		$quote->setEb2cFraudEncoding( $this->_getOrderContext()->getEncoding() );
		$quote->setEb2cFraudHostName( $this->_getOrderContext()->getHostName() );
		$quote->setEb2cFraudIpAddress( $this->_getOrderContext()->getIpAddress() );
		$quote->setEb2cFraudLanguage( $this->_getOrderContext()->getLanguage() );
		$quote->setEb2cFraudReferrer( $this->_getOrderContext()->getReferrer() );
		$quote->setEb2cFraudSessionId( $this->_getOrderContext()->getSessionId() );
		$quote->setEb2cFraudUserAgent( $this->_getOrderContext()->getUserAgent() );

		$quote->save();

		return;
	}
}

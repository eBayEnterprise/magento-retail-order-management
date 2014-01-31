<?php
class TrueAction_Eb2cFraud_Model_Observer
{
	/**
	 * Handler called before order save.
	 * Updates quote with 41st Parameter anti-fraud JS.
	 */
	public function captureOrderContext($observer)
	{
		$http = Mage::helper('eb2cfraud/http');
		$sess = Mage::getSingleton('customer/session');
		$rqst = $observer->getEvent()->getRequest();
		$observer->getEvent()->getQuote()->addData(array(
			'eb2c_fraud_char_set'        => $http->getHttpAcceptCharset(),
			'eb2c_fraud_content_types'   => $http->getHttpAccept(),
			'eb2c_fraud_encoding'        => $http->getHttpAcceptEncoding(),
			'eb2c_fraud_host_name'       => $http->getHttpHost(),
			'eb2c_fraud_referrer'        => $http->getHttpReferer(),
			'eb2c_fraud_user_agent'      => $http->getHttpUserAgent(),
			'eb2c_fraud_language'        => $http->getHttpAcceptLanguage(),
			'eb2c_fraud_ip_address'      => $http->getRemoteAddr(),
			'eb2c_fraud_session_id'      => $sess->getEncryptedSessionId(),
			'eb2c_fraud_javascript_data' => $rqst->getPost(TrueAction_Eb2cFraud_Helper_Data::JSC_FIELD_NAME, ''),
		))->save();
	}
}

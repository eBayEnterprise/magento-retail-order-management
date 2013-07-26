<?php
/**
 * Context class provides getters that gather up the various bits we need
 *	to 'set'Into our Eb2c orders.
 */
class TrueAction_Eb2cFraud_Model_Context extends Mage_Core_Model_Abstract
{
	private $_httpHelper;
	private $_mageSession;

	/**
	 * Gets Magento's Core Http Helper to help us get Context fields that are
	 * 	in the HTTP headers.
	 *
	 */
	private function _getHttpHelper()
	{
		if( !$this->_httpHelper ) {
			$this->_httpHelper = Mage::helper('eb2cfraud/http');
		}
		return $this->_httpHelper;
	}

	/**
	 * Gets Magento's Session so we context can return info from it
	 *
	 */
	private function _getMageSession()
	{
		if( !$this->_mageSession ) {
			$this->_mageSession = Mage::getSingleton('customer/session');
		}
		return $this->_mageSession;
	}

	/**
	 * Returns HTTP 'Accept-Charset'
	 */
	public function getCharSet()
	{
		return $this->_getHttpHelper()->getHttpAcceptCharset();
	}

	/**
	 * Returns HTTP 'Accept-Encoding'
	 */
	public function getEncoding()
	{
		return $this->_getHttpHelper()->getHttpAcceptEncoding();
	}

	/**
	 * Returns HTTP Hostname
	 */
	public function getHostName()
	{
		return $this->_getHttpHelper()->getHttpHost();
	}

	/**
	 * Returns HTTP Hostname
	 */
	public function getContentTypes()
	{
		return $this->_getHttpHelper()->getHttpAccept();
	}

	/**
	 * Returns Remote IP Address
	 */
	public function getIpAddress()
	{
		return $this->_getHttpHelper()->getRemoteAddr();
	}


	/**
	 * Retrieve the Javascript Collector field called 'JavascriptData'
	 */
	public function getJavascriptData()
	{
		return $this->_getMageSession()->getJavascriptData();
	}


	/**
	 * Returns HTTP 'Accept-Language'
	 */
	public function getLanguage()
	{
		return $this->_getHttpHelper()->getHttpAcceptLanguage();
	}

	/**
	 * Return HTTP 'Referer'
	 */
	public function getReferer()
	{
		return $this->_getHttpHelper()->getHttpReferer();
	}

	/**
	 * Returns Magento Session Id
	 */
	public function getSessionId()
	{
		return $this->_getMageSession()->getEncryptedSessionId();
	}

	/**
	 * Return HTTP 'User-Agent'
	 */
	public function getUserAgent()
	{
		return $this->_getHttpHelper()->getHttpUserAgent();
	}
}

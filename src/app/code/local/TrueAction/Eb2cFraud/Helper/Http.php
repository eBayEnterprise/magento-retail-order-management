<?php
class TrueAction_Eb2cFraud_Helper_Http extends Mage_Core_Helper_Http
{
	/**
	 * Retrieve HTTP Accept header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.1
	 * @param boolean $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAccept($clean = true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT', $clean);
	}

	/**
	 * Retrieve HTTP Accept-Encoding header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.3
	 * @param boolean $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAcceptEncoding($clean = true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT_ENCODING', $clean);
	}

	/**
	 * Retrieve HTTP Accept-Language header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
	 * @param boolean $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAcceptLanguage($clean = true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT_LANGUAGE', $clean);
	}
}

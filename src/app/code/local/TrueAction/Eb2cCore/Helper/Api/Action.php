<?php
class TrueAction_Eb2cCore_Helper_Api_Action
{
	const TRUEACTION_EB2CCORE_REQUEST_FAILED = 'Trueaction_Eb2ccore_Request_Failed';
	/**
	 * @throws TrueAction_Eb2cCore_Exception_Critical always
	 */
	public function throwException()
	{
		throw new TrueAction_Eb2cCore_Exception_Critical();
	}
	/**
	 * return an empty string.
	 * @return string
	 */
	public function returnEmpty()
	{
		return '';
	}
	/**
	 * return the response body
	 * @param  Zend_Http_Response $response
	 * @return string
	 */
	public function returnBody(Zend_Http_Response $response)
	{
		return $response->getBody();
	}
	/**
	 * display a general notice in the cart when a request is configured to
	 * fail loudly.
	 * @return string
	 */
	public function displayDefaultMessage()
	{
		Mage::getSingleton('core/session')->addError(self::TRUEACTION_EB2CCORE_REQUEST_FAILED);
		return '';
	}
}

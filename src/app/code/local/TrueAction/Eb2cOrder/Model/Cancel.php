<?php
/**
 * Generates an OrderCancel
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. I need to investigate whether 'order_cancel_after' ... it's my
 *	first candidate for where I should hook the cancel request in.
 *
 *  order_cancel_after
 */
class TrueAction_Eb2cOrder_Model_Cancel extends Mage_Core_Model_Abstract
{
	private $_domRequest = null;
	private $_domResponse = null;
	private $_helper;
	private $_config;
	private $_orderId;

	public function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
	}

	/**
	 * cancel builds, sends Cancel Order Request; returns true or false if we got an answer. Throws exception
	 *	if something went wrong along the way.
	 *
	 * @param args array of arguments keyed as: 'order_type', 'order_id', 'reason_code', 'reason'
	 */
	public function buildRequest(array $args)
	{
		$consts = $this->_helper->getConstHelper();
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$cancelRequest = $this->_domRequest->addElement($consts::CANCEL_DOM_ROOT_NODE_NAME, null, $consts::DOM_ROOT_NS)->firstChild;
		$cancelRequest->addAttribute('orderType', $args['order_type']);

		$this->_orderId = $args['order_id'];
		$cancelRequest->createChild('CustomerOrderId', $this->_orderId);
		$cancelRequest->createChild('ReasonCode', $args['reason_code']);
		$cancelRequest->createChild('Reason', $args['reason']);

		$this->_domRequest->formatOutput = true;
		return $this;
	}

	/**
	 * Handles communication with service endpoint. Either returns true or false when successfully receiving a valid
	 *	response or throws an exception if we can't get a valid response.
	 *
	 */
	public function sendRequest()
	{
		$consts = $this->_helper->getConstHelper();
		$uri = $this->_helper->getOperationUri($consts::CANCEL_OPERATION);

		if( $this->_config->developerMode ) {
			$uri = $this->_config->developerCancelUri;
		}

		try {
			$response = $this->_helper->getApiModel()
				->setUri($uri)
				->setTimeout($this->_helper->getConfig()->serviceOrderTimeout)
				->request($this->_domRequest);

			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
			$rc = strcmp($status, 'CANCELLED') ? false : true;
		}
		catch(Exception $e) {
			Mage::logException($e);
			$rc = false;
		}

		if( $rc === true ) {
			Mage::dispatchEvent('eb2c_order_cancel_succeeded', array('order_id' => $this->_orderId));
		}
		else {
			Mage::dispatchEvent('eb2c_order_cancel_failed', array('order_id' => $this->_orderId));
		}
		return $rc;
	}
}

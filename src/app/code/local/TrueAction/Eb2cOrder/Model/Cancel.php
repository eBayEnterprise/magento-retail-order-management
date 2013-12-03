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
	const ORDER_CANCEL_FAILURE_MESSAGE = 'TrueAction_Eb2cOrder_Cancel_Fail_Message';
	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	private $_domRequest = null;

	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	private $_domResponse = null;

	/**
	 * @var TrueAction_Eb2cOrder_Helper_Data, helper Object
	 */
	private $_helper;

	/**
	 * @var TrueAction_Eb2cCore_Model_Config_Registry, config Object
	 */
	private $_config;

	/**
	 * @var string, order id
	 */
	private $_orderId;

	public function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
	}

	/**
	 * cancel builds, sends Cancel Order Request; returns true or false if we got an answer.
	 * @param string $orderType, the order type
	 * @param string $orderId, the order id
	 * @param string $reasonCode, the reason code
	 * @param string $reason, the reason
	 * @return self
	 */
	public function buildRequest($orderType, $orderId, $reasonCode, $reason)
	{
		$this->_orderId = $orderId;
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$cancelRequest = $this->_domRequest->addElement($this->_config->apiCancelDomRootNodeName, null, $this->_config->apiXmlNs)->firstChild;
		$cancelRequest->addAttribute('orderType', $orderType);
		$cancelRequest->addChild('CustomerOrderId', $this->_orderId)
			->addChild('ReasonCode', $reasonCode)
			->addChild('Reason', $reason);

		$this->_domRequest->formatOutput = true;
		return $this;
	}

	/**
	 * Handles communication with service endpoint. send order cancel request to
	 * eb2c services and log any request exceptions, or xsd validation
	 * @return self
	 */
	public function sendRequest()
	{
		$uri = $this->_helper->getOperationUri($this->_config->apiCancelOperation);

		$response = '';
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $this->_domRequest->saveXML()), Zend_Log::DEBUG);
		try {
			$response = Mage::getModel('eb2ccore/api')
				->setUri($uri)
				->setTimeout($this->_helper->getConfig()->serviceOrderTimeout)
				->setXsd($this->_config->xsdFileCancel)
				->request($this->_domRequest);
		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending order cancel request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		} catch(Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] xsd validation occurred while sending order create request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::CRIT
			);
		}

		if (trim($response) !== '') {
			// load load response with actual content
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
		}

		return $this;
	}

	/**
	 * processing the request response from eb2c, throw exception if reponse status is not cancelled
	 * @return self
	 */
	public function processResponse()
	{
		if (trim($this->_domResponse->saveXML()) !== '') {
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
			if (strtoupper(trim($status)) === 'CANCELLED') {
				Mage::dispatchEvent('eb2c_order_cancel_succeeded', array('order_id' => $this->_orderId));
			} else {
				Mage::dispatchEvent('eb2c_order_cancel_failed', array('order_id' => $this->_orderId));
				throw new TrueAction_Eb2cOrder_Model_Cancel_Exception(
					Mage::helper('eb2corder')->__(self::ORDER_CANCEL_FAILURE_MESSAGE)
				);
			}
		}

		return $this;
	}
}

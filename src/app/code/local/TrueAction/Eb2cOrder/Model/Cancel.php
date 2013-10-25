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
	 * cancel builds, sends Cancel Order Request; returns true or false if we got an answer. Throws exception
	 * if something went wrong along the way.
	 * @param string $orderType, the order type
	 * @param string $orderId, the order id
	 * @param string $reasonCode, the reason code
	 * @param string $reason, the reason
	 * @return self
	 */
	public function buildRequest($orderType, $orderId, $reasonCode, $reason)
	{
		$consts = $this->_helper->getConstHelper();
		$this->_orderId = $orderId;
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$cancelRequest = $this->_domRequest->addElement($consts::CANCEL_DOM_ROOT_NODE_NAME, null, $this->_config->apiXmlNs)->firstChild;
		$cancelRequest->addAttribute('orderType', $orderType);
		$cancelRequest->addChild('CustomerOrderId', $this->_orderId)
			->addChild('ReasonCode', $reasonCode)
			->addChild('Reason', $reason);

		$this->_domRequest->formatOutput = true;
		return $this;
	}

	/**
	 * Handles communication with service endpoint. Either returns true or false when successfully receiving a valid
	 * response or throws an exception if we can't get a valid response.
	 * @return bool, true successfully cancelled in eb2c otherwise false
	 */
	public function sendRequest()
	{
		$consts = $this->_helper->getConstHelper();
		$uri = $this->_helper->getOperationUri($consts::CANCEL_OPERATION);

		if( $this->_config->developerMode ) {
			$uri = $this->_config->developerCancelUri;
		}
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
				Zend_Log::ERR
			);
		}

		return $this->_processResponse($response);
	}

	/**
	 * processing the request response from eb2c
	 * @param string $response, the response string xml from eb2c request
	 * @return bool, true successfully cancelled in eb2c otherwise false
	 */
	private function _processResponse($response)
	{
		if (trim($response) !== '') {
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
			$rc = (strtoupper(trim($status)) === 'CANCELLED') ? false : true;
			if( $rc === true ) {
				Mage::dispatchEvent('eb2c_order_cancel_succeeded', array('order_id' => $this->_orderId));
			} else {
				Mage::dispatchEvent('eb2c_order_cancel_failed', array('order_id' => $this->_orderId));
			}

			return $rc;
		}

		return false;
	}
}

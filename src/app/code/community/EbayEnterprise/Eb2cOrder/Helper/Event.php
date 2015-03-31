<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cOrder_Helper_Event
{
	// prefix for OrderEvent event names in Magento
	const EVENT_PREFIX = 'ebayenterprise_order_event_';
	// XPath to get the type of event from a message
	const EVENT_TYPE_XPATH = 'string(/*/*/@EventType)';
	// @var EbayEnterprise_Eb2cCore_Helper_Data
	protected $_coreHelper;
	// @var EbayEnterprise_MageLog_Helper_Data
	protected $_logger;
	/**
	 * Set up dependencies
	 */
	public function __construct()
	{
		$this->_coreHelper = Mage::helper('eb2ccore');
		$this->_logger = Mage::helper('ebayenterprise_magelog');
	}
	/**
	 * Get the specific name of the order event to dispatch for a given message.
	 * @param  string $message Sting of XML order event
	 * @return string
	 */
	public function getMessageEventName($message)
	{
		return self::EVENT_PREFIX . $this->_coreHelper->underscoreWords(
			$this->_extractOrderEventNameFromMessage($this->_getDomDocumentForMessage($message))
		);
	}
	/**
	 * Get a new EbayEnterprise_Dom_Document with the given message loaded.
	 * @param  string
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _getDomDocumentForMessage($message)
	{
		$doc = $this->_coreHelper->getNewDomDocument();
		// Attempt to load the message. If the message fails to load, log an error
		// but still return the empty document, allowing the attempt to extract
		// an event type from the message to fail and throw an appropriate
		// exception.
		if (!$doc->loadXML($message)) {
			$this->_logger->logErr('[%s] Failed to load message into DOM: %s%s', array(__CLASS__, "\n", $message));
		}
		return $doc;
	}
	/**
	 * Extract the name of the order event from the order event XML.
	 * @param  string $message OrderEvent XML
	 * @return string
	 * @throws EbayEnterprise_Amqp_Exception_Invalid_Message If message could not be parsed for event name
	 */
	protected function _extractOrderEventNameFromMessage(DOMDocument $messageDoc)
	{
		$xpath = $this->_coreHelper->getNewDomXPath($messageDoc);
		$eventType = $xpath->evaluate(self::EVENT_TYPE_XPATH);
		if ($eventType) {
			return $eventType;
		}
		throw new EbayEnterprise_Amqp_Exception_Invalid_Message('Could not extract event name from message.');
	}
	/**
	 * Attempt to cancel an order.
	 * @param  Mage_Sales_Model_Order
	 * @return self
	 */
	public function attemptCancelOrder(Mage_Sales_Model_Order $order)
	{
		try {
			$order->cancel()->save();
			$this->_logger->logInfo('[%s]: Canceling order %s', array(__CLASS__, $order->getIncrementId()));
		} catch (Exception $e) {
			// Catching any exception that might be thrown due to calling cancel method on the order object.
			$this->_logger->logWarn('[%s] An error occurred canceling order "%s". See the exception log for details.', array(__CLASS__, $order->getIncrementId()));
			$this->_logger->logException($e);
		}
		return $this;
	}
	/**
	 * Attempt to hold an order.
	 * @param  Mage_Sales_Model_Order
	 * @return self
	 */
	public function attemptHoldOrder(Mage_Sales_Model_Order $order)
	{
		try {
			$order->hold()->save();
			$this->_logger->logInfo('[%s]: Holding order %s', array(__CLASS__, $order->getIncrementId()));
		} catch (Exception $e) {
			$this->_logger->logWarn('[%s] An error occurred holding order "%s". See the exception log for details.', array(__CLASS__, $order->getIncrementId()));
			$this->_logger->logException($e);
		}
		return $this;
	}
}

<?php
/**
 * Helper for Feed Processing
 *
 */
class TrueAction_Eb2cCore_Helper_Feed extends Mage_Core_Helper_Abstract
{
	const FILETRANSFER_CONFIG_PATH = 'eb2ccore/feed';
	// MWS stands for "Magento Web Store" and won't change according to Eb2c.
	const DEST_ID = 'MWS';
	const DEST_ID_XPATH = '//MessageHeader/DestinationData/DestinationId[normalize-space()="%s"]';
	const EVENT_TYPE_XPATH = '//MessageHeader/EventType[normalize-space()="%s"]';

	/**
	 * If there exists at least one DestinationId of "MWS" (Magento Web Store) this feed is valid.
	 * If not, we log at the DEBUG level and quietly consider the feed invalid.
	 * @param bool whether the destinationId is valid or not for the given feed.
	 */
	private function _validateDestId($doc)
	{
		$xpath = new DOMXPath($doc);
		$matches = $xpath->query(sprintf(self::DEST_ID_XPATH, self::DEST_ID));
		if ($matches->length) {
			return true;
		} else {
			Mage::log(sprintf('[ %s ] Feed does not have "%s" DestinationId node.', __CLASS__, self::DEST_ID), Zend_Log::DEBUG);
			return false;
		}
	}
	/**
	 * Validate the event type.
	 * @param bool whether the event type matches for the given feed.
	 */
	private function _validateEventType($doc, $eventType)
	{
		$xpath = new DOMXPath($doc);
		$matches = $xpath->query(sprintf(self::EVENT_TYPE_XPATH, $eventType));
		if ($matches->length) {
			return true;
		} else {
			Mage::log(sprintf('[ %s ] Feed does not have "%s" EventType node.', __CLASS__, $eventType), Zend_Log::DEBUG);
			return false;
		}
	}
	/**
	 * Ensure this Feed is for the client ID configured here, and event type matches.
	 * @link https://trueaction.atlassian.net/wiki/display/EBC/Message+Header+Validation
	 *
	 * @param DOMDocument $doc, the loaded Dom xml feed
	 * @param string eventType - what event type caller is trying to process
	 *
	 * @return bool true if this matches our client id, false otherwise
	 */
	public function validateHeader($doc, $eventType)
	{
		return $this->_validateDestId($doc) && $this->_validateEventType($doc, trim($eventType));
	}
}

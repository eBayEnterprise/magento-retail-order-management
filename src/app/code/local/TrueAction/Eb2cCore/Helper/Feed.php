<?php
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
			Mage::log(sprintf('[ %s ] Feed does not have "%s" DestinationId node.', __CLASS__, self::DEST_ID), Zend_Log::WARN);
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
			Mage::log(sprintf('[ %s ] Feed does not have "%s" EventType node.', __CLASS__, $eventType), Zend_Log::WARN);
			return false;
		}
	}

	/**
	 * get a DateTime object for when the message was created.
	 * if no date can be retrieved a datetime object with a unix timestamp
	 * of 0 will be returned.
	 * @param  string  $filename path to the xml file
	 * @return DateTime
	 */
	public function getMessageDate($filename)
	{
		$dateObj = null;
		$reader = new XMLReader();
		$reader->open($filename);
		// the following 2 variables prevent the edge case where we get a large file
		// with a node depth < 2
		$elementsRead = 0;
		$maxElements = 2;
		// the date/time node is at depth 2
		$targetDepth = 2;
		// navigate to within the message header
		while ($reader->depth < $targetDepth && $elementsRead <= $maxElements && $reader->read()) {
			// ignore whitespace
			if ($reader->nodeType !== XMLReader::ELEMENT) {
				continue;
			}
		}
		$dateNode = null;
		// at this point we should be at the depth where the creation date is.
		// if we stopped on the node, then grab it
		if ($reader->localName === 'CreateDateAndTime') {
			$dateNode = $reader->expand();
		} elseif ($reader->next('CreateDateAndTime')) {
			// otherwise go to the next instance of it
			$dateNode = $reader->expand();
		}
		if (!($dateNode && $dateNode->nodeValue)) {
			// use the file's local modified time as the failover
			$default = filemtime($filename);
			// if that doesn't work use the start of the epoch
			$dateObj = DateTime::createFromFormat('U', $default === false ? 0 : $default);
			Mage::log(
				'[' . __CLASS__ . "] Unable to read the message date from file [$filename]",
				Zend_Log::WARN
			);
		} else {
			$dateNode = $reader->expand();
			$dateObj = DateTime::createFromFormat('Y-m-d\TH:i:sO', $dateNode->nodeValue);
		}
		return $dateObj;
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

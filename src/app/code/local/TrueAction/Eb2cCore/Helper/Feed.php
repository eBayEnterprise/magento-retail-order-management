<?php
/**
 * Helper for Feed Processing
 *
 */
class TrueAction_Eb2cCore_Helper_Feed extends Mage_Core_Helper_Abstract
{
	const FILETRANSFER_CONFIG_PATH = 'eb2ccore/feed';

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
		$clientId = Mage::getModel('eb2ccore/config_registry')
			->setStore(null)
			->addConfigModel(Mage::getModel('eb2ccore/config'))->clientId;

		$headerXpath = new DOMXPath($doc);

		$docDestinationId = $headerXpath->query('//MessageHeader/DestinationData/DestinationId');
		if (!$docDestinationId->length || (trim($clientId) !== trim($docDestinationId->item(0)->nodeValue))) {
			Mage::log(
				'[' . __CLASS__ . '] DestinationId (client_id) node was not found, or doesn\'t match configured "' . $clientId . '"',
				Zend_Log::CRIT
			);
			return false;
		}

		$docEventType = $headerXpath->query('//MessageHeader/EventType');
		if (!$docEventType->length || (trim($eventType) !== trim($docEventType->item(0)->nodeValue))) {
			Mage::log(
				'[' . __CLASS__ . '] EventType node was not found, or doesn\'t match requested "' . $eventType . '"',
				Zend_Log::CRIT
			);
			return false;
		}
		return true;
	}
}

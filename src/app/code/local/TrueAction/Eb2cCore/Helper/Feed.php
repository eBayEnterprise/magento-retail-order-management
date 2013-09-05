<?php
/**
 * @category   TrueAction
 * @package    TrueActionEb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Helper_Feed extends Mage_Core_Helper_Abstract
{
	const FILETRANSFER_CONFIG_PATH = 'eb2ccore/feed';
	/**
	 * Validating Feed Header. This will validate:
	 * (In MessageHeader):
	 *    HeaderVersion
	 *    VersionReleaseNumber
	 *    EventType
	 *    DestinationId
	 *    DestinationType
	 *
	 * @param DOMDocument $doc, the loaded Dom xml feed
	 * @param string $expectEventType
	 * @param string $expectHeaderVersion, expected to be in the form 2.3.4, where ".4" is your minor version
	 *
	 * @return bool, true header is valid false otherwise
	 */
	public function validateHeader($doc, $expectEventType, $expectHeaderVersion)
	{
		$isValid = true;

		$coreConfig = Mage::getModel('eb2ccore/config_registry')
			->setStore(null)
			->addConfigModel(Mage::getModel('eb2ccore/config'));

		// Determine the major and minor version from the pass Header Version
		$versions = $this->_extractMinorMajorVersion($expectHeaderVersion);

		$headerXpath = new DOMXPath($doc);
		$headerVersion = $headerXpath->query('//MessageHeader/HeaderVersion');

		if (!$headerVersion->length) {
			// Header Version wasn't found in the feed document.
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] HeaderVersion node was not found in the xml feed',
				Zend_Log::WARN
			);
		} elseif(trim($versions['major']) !== trim($headerVersion->item(0)->nodeValue)) {
			// Header Version doesn't match
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Feed Header Version "' .
				$headerVersion->item(0)->nodeValue .
				'" does not match expected Header Version "' .
				$versions['major'] .
				'"',
				Zend_Log::WARN
			);
		}

		$versionReleaseNumber = $headerXpath->query('//MessageHeader/VersionReleaseNumber');
		if (!$versionReleaseNumber->length) {
			// Version Release Number wasn't found in the feed document.
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] versionReleaseNumber node was not found in the xml feed',
				Zend_Log::WARN
			);
		} elseif(trim($versions['minor']) !== trim($versionReleaseNumber->item(0)->nodeValue)) {
			// Version Release Number doesn't match
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Feed Version Release Number "' .
				$versionReleaseNumber->item(0)->nodeValue .
				'" does not match expected Version Release Number "' .
				$versions['minor'] .
				'"',
				Zend_Log::WARN
			);
		}

		$eventType = $headerXpath->query('//MessageHeader/EventType');

		if (!$eventType->length) {
			// Event Type wasn't found in the feed document.
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] EventType node was not found in the xml feed',
				Zend_Log::WARN
			);
		} elseif(trim($expectEventType) !== trim($eventType->item(0)->nodeValue)) {
			// Event Type doesn't match
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Feed Event Type "' .
				$eventType->item(0)->nodeValue .
				'" does not match config Event Type "' .
				$expectEventType .
				'"',
				Zend_Log::WARN
			);
		}

		$destinationId = $headerXpath->query('//MessageHeader/DestinationData/DestinationId');
		if (!$destinationId->length) {
			// DestinationId wasn't found in the feed document.
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] DestinationId (client_id) node was not found in the xml feed',
				Zend_Log::WARN
			);
		} elseif(trim($coreConfig->clientId) !== trim($destinationId->item(0)->nodeValue)) {
			// DestinationId doesn't match
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Feed DestinationId "' .
				$destinationId->item(0)->nodeValue .
				'" does not match config DestinationId (client_id) "' .
				$coreConfig->clientId .
				'"',
				Zend_Log::WARN
			);
		}

		$destinationType = $headerXpath->query('//MessageHeader/DestinationData/DestinationType');
		if (!$destinationType->length) {
			// Destination Type wasn't found in the feed document.
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Destination Type node was not found in the xml feed',
				Zend_Log::WARN
			);
		} elseif(trim($coreConfig->feedDestinationType) !== trim($destinationType->item(0)->nodeValue)) {
			// Destination Type doesn't match
			$isValid = false;
			Mage::log(
				'[' . __CLASS__ . '] Feed Destination Type "' .
				$destinationType->item(0)->nodeValue .
				'" does not match config Destination Type "' .
				$coreConfig->feedDestinationType . '"',
				Zend_Log::WARN
			);
		}

		if (!$isValid) {
			// Log the entire feed header on any mismatch
			$feedHeaderContent = '';
			$headerArr = explode('</MessageHeader>', stristr($doc->saveXML(), '<MessageHeader>'));
			if (sizeof($headerArr) > 0) {
				$feedHeaderContent = $headerArr[0] . '</MessageHeader>';
			}
			Mage::log('[' . __CLASS__ . '] ' . $feedHeaderContent, Zend_Log::DEBUG);
		}

		return $isValid;
	}

	/**
	 * extract minor and major version header version.
	 *
	 * @param string $expectHeaderVersion, the full version to extract from
	 *
	 * @return array, extracted minor/major version
	 */
	protected function _extractMinorMajorVersion($expectHeaderVersion)
	{
		$versions = array();
		$versions['minor'] = '';
		$versions['major'] = '';

		if(strlen($expectHeaderVersion) > 0) {
			$versionArr = explode('.', $expectHeaderVersion);
			$versionArrSize = sizeof($versionArr);
			if ($versionArrSize) {
				$major = '';
				for ($i = 0; $i < ($versionArrSize - 1); $i++) {
					$major .= $versionArr[$i];
					if ($i < ($versionArrSize - 2)){
						$major .= '.';
					}
				}
				$versions['major'] = $major;
				$versions['minor'] = $versionArr[$versionArrSize - 1];
			}
		}

		return $versions;
	}
}

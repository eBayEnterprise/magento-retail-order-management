<?php
class TrueAction_Eb2cCore_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Service URI has the following format:
	 * https://{env}-{rr}.gsipartners.com/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - env - GSI Environment to access
	 * - rr - Geographic region - na, eu, ap
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s-%s.gsipartners.com/v%s.%s/stores/%s/%s/%s%s.%s';
	/**
	 * Get the API URI for the given service/request.
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param string $format
	 */
	public function getApiUri($service, $operation, $params=array(), $format='xml')
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return sprintf(
			self::URI_FORMAT,
			$config->apiEnvironment,
			$config->apiRegion,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			$config->storeId,
			$service,
			$operation,
			(!empty($params)) ? '/' . implode('/', $params) : '',
			$format
		);
	}

	/**
	 * Create and return a new instance of TrueAction_Dom_Document.
	 * @return TrueAction_Dom_Document
	 */
	public function getNewDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}

	/**
	 * validating ftp settings by simply checking if there's actual setting data.
	 *
	 * @return bool, true valid ftp settings otherwise false
	 */
	public function isValidFtpSettings()
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return trim($cfg->sftpUsername) && trim($cfg->sftpLocation) && (
			($cfg->sftpAuthType === 'password' && trim($cfg->sftpPassword)) ||
			($cfg->sftpAuthType === 'pub_key' && trim($cfg->sftpPublicKey) && trim($cfg->sftpPrivateKey))
		);
	}

	/**
	 * convert feed lang data to match magento expected format (en-US => en_US)
	 *
	 * @param string $langCode, the language code
	 *
	 * @return string, the magento expected format
	 */
	public static function xmlToMageLangFrmt($langCode)
	{
		return str_replace('-', '_', $langCode);
	}

	/**
	 * Convert Magento Locale Code into a format matching inbound feed, i.e. en_US => en-US
	 *
	 * @param string $langCode, the language code
	 * @return string, the magento expected format
	 */
	public static function mageToXmlLangFrmt($langCode)
	{
		return str_replace('_', '-', $langCode);
	}

	/**
	 * Ensure the sku/client id/style id matches the same format expected for skus
	 * {catalogId}-{item_id}
	 * @param  string $itemId    Product item/style/client/whatevs id
	 * @param  string $catalogId Product catalog id
	 * @return string            Normalized style id
	 */
	public function normalizeSku($itemId, $catalogId)
	{
		if (!empty($itemId) && strpos($itemId, $catalogId . '-') !== 0) {
			return sprintf('%s-%s', $catalogId, $itemId);
		}
		return $itemId;
	}

	/**
	 * extract node value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 */
	public function extractNodeVal(DOMNodeList $nodeList)
	{
		return ($nodeList->length)? $nodeList->item(0)->nodeValue : null;
	}

	/**
	 * extract node attribute value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 */
	public function extractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		return ($nodeList->length)? $nodeList->item(0)->getAttribute($attributeName) : null;
	}

	/**
	 * Return the eb2c ship method configured to correspond to a known Magento ship method.
	 * @param string $mageShipMethod
	 * @return string EB2C ship method
	 */
	public function lookupShipMethod($mageShipMethod)
	{
		// Deliberately bypass configurator so we can dynamically lookup.
		return Mage::getStoreConfig("eb2ccore/shipmap/$mageShipMethod");
	}
}

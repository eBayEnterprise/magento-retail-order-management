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

use eBayEnterprise\RetailOrderManagement\Api;
use Psr\Log\LoggerInterface;

/**
 * Class EbayEnterprise_Eb2cCore_Helper_Data
 *
 * A loose collection of semi-static methods that are used by independent
 * modules across Retail Order Management.
 */
class EbayEnterprise_Eb2cCore_Helper_Data extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	/**
	 * Service URI has the following format:
	 * https://{host}/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - host - EE Excnahge Platform domain
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s/v%s.%s/stores/%s/%s/%s%s.%s';
	const PERMISSION = 0750;

	public function __construct()
	{
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_context = Mage::helper('ebayenterprise_magelog/context');
	}

	/**
	 * Get the API URI for the given service/request.
	 *
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param string $format
	 * @return string
	 */
	public function getApiUri($service, $operation, $params = [], $format = 'xml')
	{
		$config = Mage::helper('eb2ccore')->getConfigModel();

		return sprintf(
			self::URI_FORMAT,
			$config->apiHostname,
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
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store = null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}

	/**
	 * Create and return a new instance of EbayEnterprise_Dom_Document.
	 * @return EbayEnterprise_Dom_Document
	 */
	public function getNewDomDocument()
	{
		$d = new EbayEnterprise_Dom_Document('1.0', 'UTF-8');
		$d->preserveWhiteSpace = false;
		return $d;
	}

	/**
	 * given DOMDocument get the DOMElement from it.
	 *
	 * @param DOMDocument $doc
	 * @return DOMElement
	 * @codeCoverageIgnore
	 */
	public function getDomElement(DOMDocument $doc)
	{
		return $doc->documentElement;
	}

	/**
	 * Create and return a new instance of XSLTProcessor.
	 *
	 * @return XSLTProcessor
	 * @codeCoverageIgnore
	 */
	public function getNewXsltProcessor()
	{
		return new XSLTProcessor();
	}

	/**
	 * Create and return a new instance of DOMXPath.
	 *
	 * @param DOMDocument $doc
	 * @return DOMXPath
	 * @codeCoverageIgnore
	 */
	public function getNewDomXPath(DOMDocument $doc)
	{
		return new DOMXPath($doc);
	}
	/**
	 * Create and return a new instance of DOMText.
	 * @param string $value
	 * @return DOMText
	 * @codeCoverageIgnore
	 */
	public function getNewDomText($value)
	{
		return new DOMText($value);
	}

	/**
	 * Create and return a new instance of SplObjectStorage.
	 *
	 * @return SplObjectStorage
	 * @codeCoverageIgnore
	 */
	public function getNewSplObjectStorage()
	{
		return new SplObjectStorage();
	}

	/**
	 * Create and return a new Varien_Data_Collection
	 * @return Varien_Data_Collection
	 */
	public function getNewVarienDataCollection()
	{
		return new Varien_Data_Collection();
	}
	/**
	 * Validate sftp settings by simply checking if there's actual setting data.
	 *
	 * @return bool valid ftp settings
	 */
	public function isValidFtpSettings()
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel();

		return trim($cfg->sftpUsername) && trim($cfg->sftpLocation) && (
			($cfg->sftpAuthType === 'password' && trim($cfg->sftpPassword)) ||
			($cfg->sftpAuthType === 'pub_key' && trim($cfg->sftpPrivateKey))
		);
	}

	/**
	 * Convert Magento Locale Code into a format matching inbound feed, i.e. en_US => en-US
	 *
	 * @param string $langCode, the language code
	 * @return string, the magento expected format
	 */
	public function mageToXmlLangFrmt($langCode)
	{
		return str_replace('_', '-', $langCode);
	}

	/**
	 * Get value of node specified for query. In accordance with existing local custom here,
	 * named 'extract', and calls extractNodeValue to ensure consistency.
	 *
	 * @param DOMXpath $xpath The object that can query
	 * @param string $query The xpath query string
	 * @return string value found at node found by query
	 */
	public function extractQueryNodeValue(DOMXpath $xpath, $query)
	{
		return $this->extractNodeVal($xpath->query($query));
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
	 *
	 * @param string $mageShipMethod
	 * @return string EB2C ship method
	 */
	public function lookupShipMethod($mageShipMethod)
	{
		// Deliberately bypass configurator so we can dynamically lookup.
		return Mage::getStoreConfig("eb2ccore/shipmap/$mageShipMethod");
	}

	/**
	 * loading a file into a new SplFileInfo instantiated object
	 *
	 * @param string $fileName
	 * @return SplFileInfo
	 * @codeCoverageIgnore
	 */
	public function loadFile($fileName)
	{
		return new SplFileInfo($fileName);
	}

	/**
	 * abstracting calling file exists
	 *
	 * @param string $fileName
	 * @return bool true if the file is readable
	 * @codeCoverageIgnore
	 */
	public function isFileExist($fileName)
	{
		return $this->loadFile($fileName)->isReadable();
	}

	/**
	 * abstracting calling is dir
	 *
	 * @param string $dir
	 * @return bool true if the dir is a directory
	 * @codeCoverageIgnore
	 */
	public function isDir($dir)
	{
		return $this->loadFile($dir)->isDir();
	}

	/**
	 * abstracting creating dir
	 *
	 * @param string $dir
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function createDir($dir)
	{
		$oldMask = umask(0);
		@mkdir($dir, self::PERMISSION, true);
		umask($oldMask);
	}

	/**
	 * abstracting moving a file
	 *
	 * @param string $source
	 * @param string $destination
	 * @throws EbayEnterprise_Catalog_Exception_Feed_File
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function moveFile($source, $destination)
	{
		@rename($source, $destination);

		if (!$this->isFileExist($destination)) {
			throw new EbayEnterprise_Catalog_Exception_Feed_File("Can not move $source to $destination");
		}
	}

	/**
	 * abstracting removing a file
	 *
	 * @param string $file
	 * @throws EbayEnterprise_Catalog_Exception_Feed_File
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function removeFile($file)
	{
		@unlink($file);

		if ($this->isFileExist($file)) {
			throw new EbayEnterprise_Catalog_Exception_Feed_File("Can not remove $file");
		}
	}

	/**
	 * abstracting getting store configuration value
	 *
	 * @see Mage::getStoreConfig
	 * @param string $path
	 * @param mixed $store
	 * @return mixed
	 * @codeCoverageIgnore
	 */
	public function getStoreConfig($path, $store = null)
	{
		return Mage::getStoreConfig($path, $store);
	}

	/**
	 * abstracting getting store configuration flag
	 *
	 * @see Mage::getStoreConfigFlag
	 * @param string $path
	 * @param mixed $store
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public function getStoreConfigFlag($path, $store = null)
	{
		return Mage::getStoreConfigFlag($path, $store);
	}

	/**
	 * abstracting getting the magento base directory with a given scope or default
	 * scope and a given relative path to build an absolute full path
	 *
	 * @see Mage::getBaseDir
	 * @param string $relative
	 * @param string $scope
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getAbsolutePath($relative, $scope = 'base')
	{
		return Mage::getBaseDir($scope) . DS . $relative;
	}

	/**
	 * getting how long ago a file has been modified or created in minutes
	 *
	 * @param string $sourceFile
	 * @return int time ago in minutes
	 */
	public function getFileTimeElapse($sourceFile)
	{
		$date = Mage::getModel('core/date');
		$timeZone = $this->getNewDateTimeZone();
		$startDate = $this->getNewDateTime(
			$date->gmtDate('Y-m-d H:i:s', $this->loadFile($sourceFile)->getCTime()),
			$timeZone
		);
		$interVal = $startDate->diff($this->getNewDateTime(
			$date->gmtDate('Y-m-d H:i:s', $this->getTime()),
			$timeZone
		));
		return (
			($interVal->y * 365 * 24 * 60) +
			($interVal->m * 30 * 24 * 60) +
			($interVal->d * 24 * 60) +
			($interVal->h * 60) +
			$interVal->i
		);
	}

	/**
	 * abstracting instantiating a new DateTime object
	 *
	 * @param string $time
	 * @param DateTimeZone $timezone
	 * @return DateTime
	 * @codeCoverageIgnore
	 */
	public function getNewDateTime($time = 'now', DateTimeZone $timezone = null)
	{
		return new DateTime($time, $timezone);
	}

	/**
	 * abstracting instantiating a new DateTimeZone in the default
	 * magento config time zone
	 *
	 * @return DateTimeZone
	 * @codeCoverageIgnore
	 */
	public function getNewDateTimeZone()
	{
		return new DateTimeZone($this->getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE));
	}
	/**
	 * abstracting getting the current time
	 *
	 * @return int
	 * @codeCoverageIgnore
	 */
	public function getTime()
	{
		return time();
	}

	/**
	 * abstracting getting an instance of the default store
	 *
	 * @return Mage_Core_Model_Store
	 * @codeCoverageIgnore
	 */
	public function getDefaultStore()
	{
		return $this->getDefaultWebsite()->getDefaultStore();
	}

	/**
	 * abstracting getting an instance of the default website
	 *
	 * @return Mage_Core_Model_Website
	 * @codeCoverageIgnore
	 */
	public function getDefaultWebsite()
	{
		return Mage::app()->getWebsite(true);
	}

	/**
	 * abstracting getting store configuration flag
	 *
	 * @see Mage::getBaseUrl
	 * @codeCoverageIgnore
	 */
	public function getBaseUrl($type = Mage_Core_Model_Store::URL_TYPE_LINK, $secure = null)
	{
		return Mage::getBaseUrl($type, $secure);
	}

	/**
	 * abstracting triggering errors
	 *
	 * @param string $message
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function triggerError($message)
	{
		trigger_error($message, E_USER_ERROR);
	}

	/**
	 * abstracting getting an instance of the current store
	 *
	 * @return Mage_Core_Model_Store
	 * @codeCoverageIgnore
	 */
	public function getCurrentStore()
	{
		return Mage::app()->getStore();
	}
	/**
	 * Expect a comma delimited string of applied salesrule ids and the first rule
	 * id will be concatenated to a string of configured store id and a dash. The
	 * result string will be truncated to only 12 characters if exceeded.
	 *
	 * @param string $appliedRuleIds
	 * @return string
	 */
	public function getDiscountId($appliedRuleIds)
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel();
		$ids = explode(',', $appliedRuleIds);
		$ruleId = !empty($ids)?$ids[0]: 0;
		return sprintf('%.12s', $cfg->storeId . '-' . $ruleId);
	}
	/**
	 * Parse a string into a boolean.
	 * @param string $s the string to parse
	 * @return bool
	 */
	public function parseBool($s)
	{
		if (!is_string($s)) {
			return (bool) $s;
		}
		$result = false;
		switch (strtolower($s)) {
			case '1':
			case 'on':
			case 't':
			case 'true':
			case 'y':
			case 'yes':
				$result = true;
				break;
		}
		return $result;
	}
	/**
	 * Extract data from a passed in DOMNode using an array containing mapping
	 * of how to extract the data.
	 * @param  DOMNode $contextNode a valid DOMNode that is attached to a document
	 * @param  array    $mapping
	 * @param  DOMXPath $xpath a preconfigured xpath object
	 * @return array
	 */
	public function extractXmlToArray(DOMNode $contextNode, array $mapping, DOMXPath $xpath)
	{
		$data = [];
		$coreHelper = Mage::helper('eb2ccore');
		foreach ($mapping as $key => $callback) {
			if ($callback['type'] !== 'disabled') {
				$result = $xpath->query($callback['xpath'], $contextNode);
				if ($result->length) {
					$callback['parameters'] = [$result];
					$data[$key] = $coreHelper->invokeCallback($callback);
				}
			}
		}
		return $data;
	}
	/**
	 * Convert camelCase to underscore_case
	 * @param  string $value
	 * @return string
	 */
	public function underscoreWords($value)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $value));
	}
	/**
	 * Generate a unique request id with a given prefix.
	 * @param string $prefix
	 * @return string
	 */
	public function generateRequestId($prefix = '')
	{
		return uniqid($prefix);
	}
	/**
	 * Create a new ROM SDK API object. API will be configured with core configuration
	 * and the service and operation provided.
	 * @param string $service SDK API service
	 * @param string $operation SDK API operation
	 * @param array $endpointParams Additional params added to end of endpoint URI
	 * @param LoggerInterface|null $logger Provide alternate logger for the SDK. If none give, will provide a default logger.
	 * @return Api\IBidirectionalApi
	 */
	public function getSdkApi(
		$service,
		$operation,
		array $endpointParams = [],
		LoggerInterface $logger = null
	) {
		$config = $this->getConfigModel();
		$apiLogger = $logger ?: $this->_logger;
		$apiConfig = new Api\HttpConfig(
			$config->apiKey,
			$config->apiHostname,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			$config->storeId,
			$service,
			$operation,
			$endpointParams,
			$apiLogger
		);
		return new Api\HttpApi($apiConfig, $apiLogger);
	}
	/**
	 * Call a class static method based on the meta data in the given array.
	 *
	 * @param array $meta a composite array with class name and method to be executed
	 * @return string|null
	 */
	public function invokeCallback(array $meta)
	{
		if (empty($meta)) {
			return null;
		}
		$parameters = isset($meta['parameters'])? $meta['parameters'] : [];
		switch ($meta['type']) {
			case 'model':
				return call_user_func_array([Mage::getModel($meta['class']), $meta['method']], $parameters);
			case 'helper':
				return call_user_func_array([Mage::helper($meta['class']), $meta['method']], $parameters);
			case 'singleton':
				return call_user_func_array([Mage::getSingleton($meta['class']), $meta['method']], $parameters);
			default:
				return null;
		}
	}
}

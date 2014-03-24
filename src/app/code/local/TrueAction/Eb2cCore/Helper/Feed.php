<?php
class TrueAction_Eb2cCore_Helper_Feed extends Mage_Core_Helper_Abstract
{
	const FILETRANSFER_CONFIG_PATH = 'eb2ccore/feed';
	// MWS stands for "Magento Web Store" and won't change according to Eb2c.
	const DEST_ID = 'MWS';
	const DEST_ID_XPATH = '//MessageHeader/DestinationData/DestinationId[normalize-space()="%s"]';
	const EVENT_TYPE_XPATH = '//MessageHeader/EventType[normalize-space()="%s"]';

	const DEFAULT_HEADER_CONF = 'eb2ccore/feed/outbound/message_header';
	const FILE_NAME_CONF = 'eb2ccore/feed/outbound/file_name';
	const GIFTCARD_TENDER_CONFIG_PATH = 'eb2ccore/feed/gift_card_tender_code';

	/**
	 * @var array map of feed type and message header configuration path
	 */
	protected $_feedTypeHeaderConf = array(
		'ItemMaster' => 'eb2ccore/feed/filetransfer_imports/item_master/outbound/message_header',
		'ContentMaster' => 'eb2ccore/feed/filetransfer_imports/content_master/outbound/message_header',
		'iShip' => 'eb2ccore/feed/filetransfer_imports/i_ship/outbound/message_header',
		'Pricing' => 'eb2ccore/feed/filetransfer_imports/item_pricing/outbound/message_header',
		'ImageMaster' => 'eb2ccore/feed/filetransfer_imports/image_master/outbound/message_header',
		'ItemInventories' => 'eb2ccore/feed/filetransfer_imports/inventory/outbound/message_header',
		'PIMExport' => 'eb2cproduct/pim_export_feed/outbound/message_header',
	);

	/**
	 * @var TrueAction_Eb2cCore_Model_Config_Registry
	 */
	protected $_config = null;
	/**
	 * set the an instantiated object of the registry class loaded with eb2ccore config model
	 * to the class property _config if it's not already been set
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfig()
	{
		if (!$this->_config) {
			$this->_config = Mage::getModel('eb2ccore/config_registry')
				->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		}
		return $this->_config;
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
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
	 * Ensure the Feed's event type matches.
	 * @link https://trueaction.atlassian.net/wiki/display/EBC/Message+Header+Validation
	 *
	 * @param DOMDocument $doc, the loaded Dom xml feed
	 * @param string eventType - what event type caller is trying to process
	 *
	 * @return bool true if this matches our client id, false otherwise
	 */
	public function validateHeader($doc, $eventType)
	{
		return $this->_validateEventType($doc, trim($eventType));
	}

	/**
	 * abstracting getting config child nodes in an array
	 * @param string $path the parent path to set of node to get as array
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getConfigData($path)
	{
		return Mage::app()->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)->getConfig($path);
	}

	/**
	 * call a class static method base on the meta data in the given array
	 * @param array $meta a composite array with class name and method to be executed
	 * @return string|null
	 */
	public function invokeCallback(array $meta)
	{
		if (empty($meta)) {
			return null;
		}
		$parameters = isset($meta['parameters'])? $meta['parameters'] : array();
		switch ($meta['type']) {
			case 'model':
				return call_user_func_array(array(Mage::getModel($meta['class']), $meta['method']), $parameters);
			case 'helper':
				return call_user_func_array(array(Mage::helper($meta['class']), $meta['method']), $parameters);
			case 'singleton':
				return call_user_func_array(array(Mage::getSingleton($meta['class']), $meta['method']), $parameters);
			default:
				return null;
		}
	}

	/**
	 * getting eb2ccore default message header config in a composite array
	 * merge with the given feed type message header config data
	 * return an empty array if the given feed type is not in the class property _feedTypeHeaderConf
	 * @param string $feedType known feed types are (ItemMaster, ContentMaster, iShip, Pricing, ImageMaster, ItemInventories)
	 * @return array
	 */
	public function getHeaderConfig($feedType)
	{
		$type = $this->_feedTypeHeaderConf;
		if (!isset($type[$feedType])) {
			return array();
		}
		return $this->_doConfigTranslation(array_merge(
			$this->getConfigData(self::DEFAULT_HEADER_CONF),
			$this->getConfigData($type[$feedType])
		));
	}

	/**
	 * getting file name config data key and value
	 * @param string $feedType
	 * @return array
	 */
	public function getFileNameConfig($feedType)
	{
		return $this->_doConfigTranslation(array_merge(
			array('feed_type' => $feedType),
			$this->getConfigData(self::FILE_NAME_CONF)
		));
	}

	/**
	 * do configuration translation and mapping call
	 * @param array $mhc, key value mapped of configuration data
	 * @return array
	 */
	protected function _doConfigTranslation(array $mhc)
	{
		$data = array();
		foreach ($mhc as $key => $value) {
			$data[$key] = (is_array($value))? $this->invokeCallback($value) : $value;
		}
		return $data;
	}

	/**
	 * retrieve the store id from the config
	 * @return string
	 */
	public function getStoreId()
	{
		return $this->getConfig()->storeId;
	}

	/**
	 * retrieve the client id from the config
	 * @return string
	 */
	public function getClientId()
	{
		return $this->getConfig()->clientId;
	}

	/**
	 * retrieve the catalog id from the config
	 * @return string
	 */
	public function getCatalogId()
	{
		return $this->getConfig()->catalogId;
	}

	/**
	 * Generate a message id
	 * This is an arbitrary construct, designed only to pass XSD validation
	 * @return string
	 */
	public function getMessageId()
	{
		return sprintf("%-.20s", uniqid('M-',true));
	}

	/**
	 * Generate a correlation id
	 * This is an arbitrary construct, designed only to pass XSD validation
	 * @return string
	 */
	public function getCorrelationId()
	{
		return sprintf("%-.20s", uniqid('C-',true));
	}

	/**
	 * get current date and time
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getCreatedDateTime()
	{
		return date('c');
	}

	/**
	 * get timestamp
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getTimeStamp()
	{
		return Mage::getModel('core/date')->gmtDate('YmdHis', time());
	}

}

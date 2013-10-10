<?php
class TrueAction_Eb2cProduct_Model_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
{
	const INVALID_EVENT_TYPE = 'the document is missing a valid event type node [%s]';
	const DOCUMENT_START = 'processing file %s';

	/**
	 * xpath string to the eventtype node
	 */
	const EVENT_TYPE_XPATH = 'MessageHeader/EventType/text()';

	protected $_eventTypes = array(
		'Price' => 'feed_item_pricing',
		'ItemMaster' => 'feed_item_master',
		'Content' => 'feed_content_master',
	);

	/**
	 * xpath object used to query the feed document
	 * @var DOMXPath
	 */
	private $_xpath;

	/**
	 * extracts the event type from the feed file.
	 * @var TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
	 */
	protected $_eventTypeExtractor;

	/**
	 * list of all attribute codes within the set identified by $_attributeCodesSetId
	 * @var array
	 */
	private $_attributeCodes = null;

	/**
	 * attribute set id of the currently loaded attribute codes
	 * @var int
	 */
	private $_attributeCodesSetId = null;

	/**
	 * list of attribute codes that are not setup on the system but were in the feed.
	 * @var array
	 */
	private $_missingAttributes = array();

	protected $_feedFiles = array();

	protected $_eventTypeModel = null;

	protected $_queue = null;

	public function processFeeds()
	{
		$filesProcessed = 0;
		// fetch all files for all feeds.
		foreach ($this->_eventTypes as $eventType => $modelAlias) {
			$this->_eventTypeModel = Mage::getSingleton('eb2cproduct/' . $modelAlias);

			// Set up local folders for receiving, processing
			$coreFeedConstructorArgs = array(
				'base_dir' => Mage::getBaseDir('var') . DS . $this->getFeedLocalPath()
			);

			// FileSystem tool can be supplied, esp. for testing
			if ($this->getFsTool()) {
				$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
			}

			// Ready to set up the core feed helper, which manages files and directories:
			$this->_coreFeed = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);

			$this->_coreFeed->fetchFeedsFromRemote($this->getFeedRemotePath(), $this->getFeedFilePattern());
			$this->_feedFiles = array_merge($this->_feedFiles, $this->_coreFeed->lsInboundDir());
		}
		foreach($this->_feedFiles as $xmlFeedFile ) {
			$this->processFile($xmlFeedFile);
			$this->_coreFeed->mvToArchiveDir($xmlFeedFile);
			$filesProcessed++;
		}
		return $filesProcessed;
	}

	/**
	 * Processes a single xml file.
	 *
	 */
	public function processFile($xmlFile)
	{
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return;
		}

		$this->_eventTypeModel = $this->_getEventTypeModel($dom);

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'base_dir' => Mage::getBaseDir('var') . DS . $this->getFeedLocalPath()
		);

		// FileSystem tool can be supplied, esp. for testing
		if ($this->getFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		// Ready to set up the core feed helper, which manages files and directories:
		$this->_coreFeed = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);

		// Validate Eb2c Header Information
		if ( !Mage::helper('eb2ccore/feed')
			->validateHeader($dom, $this->getFeedEventType() )
		) {
			Mage::log('File ' . $xmlFile . ': Invalid header', Zend_Log::ERR);
			return;
		}
		$this->processDom($dom);
	}

	public function processDom(TrueAction_Dom_Document $doc)
	{
		$units = $this->_splitIntoUnits($doc);
		foreach ($units as $unit) {
			$operation = $this->getOperationType($unit);
			$data = $this->_extractData($unit);
			if ($operation = self::OPERATION_UPSERT) {
				$this->_queue->add($data);
			}
			elseif ($operation = self::OPERATION_REMOVE) {
				$this->_queue->remove($data);
			}
		}
	}

	public function getFeedConfig()
	{
		return $this->_config;
	}

	public function getFeedEventType()
	{
		return $this->_eventTypeModel->getFeedEventType();
	}

	public function getFeedFilePattern()
	{
		return $this->_eventTypeModel->getFeedFilePattern();
	}

	public function getFeedLocalPath()
	{
		return $this->_eventTypeModel->getFeedLocalPath();
	}

	public function getFeedRemotePath()
	{
		return $this->_eventTypeModel->getFeedRemotePath();
	}

	protected function _extractData($unit)
	{
	}

	/**
	 * @param  array  $attributeList list of attributes we want to exist
	 * @return array                 subset of $attributeList that actually exist
	 */
	private function _getApplicableAttributes(array $attributeList)
	{
		$extraAttrs = array_diff($attributeList, self::$_attributeCodes);
		if ($extraAttrs) {
			self::$_missingAttributes = array_unique(array_merge(self::$_missingAttributes, $extraAttrs));
		}
		return array_intersect($attributeList, self::$_attributeCodes);
	}

	protected function _getEventTypeModel($doc)
	{
		$this->_xpath = new DomXPath($doc);
		$nodeList = $this->_xpath->query(self::EVENT_TYPE_XPATH, $doc->documentElement);
		$eventType = null;
		if ($nodeList->item(0)) {
			$eventType = $nodeList->item(0)->nodeValue;
		}
		if (array_search($eventType, array_keys($this->_eventTypes)) === false) {
			$message = sprintf(self::INVALID_EVENT_TYPE, $eventType);
			throw new Mage_Core_Exception($message);
		}
		return Mage::getSingleton('eb2cproduct/' . $this->_eventTypes[$eventType]);
	}

	/**
	 * load all attribute codes
	 * @return self
	 */
	private function _loadAttributeCodes($product)
	{
		if (is_null(self::$_attributeCodes) || self::$_attribeteCodesSetId != $product->getAttributeSetId()) {
			self::$_attributeCodes = Mage::getSingleton('eav/config')
				->getEntityAttributeCodes($product->getResource()->getEntityType(), $product);
		}
		return $this;
	}

	protected function _prepareForFeed()
	{
		return $this;
	}

	/**
	 * suppress the core feed's initialization
	 * create necessary internal models.
	 * @return [type] [description]
	 */
	protected function _construct()
	{
		$this->_eventTypeExtractor = Mage::getModel(
			'eb2cproduct/feed_extractor_xpath',
			array('event_type' => self::EVENT_TYPE_XPATH)
		);
		$this->_queue = Mage::getSingleton('eb2cproduct/feed_queue');
	}
}

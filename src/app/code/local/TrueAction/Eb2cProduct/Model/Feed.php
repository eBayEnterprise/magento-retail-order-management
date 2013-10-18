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

	/**
	 * xpath object used to query the feed document
	 * @var DOMXPath
	 */
	protected $_xpath;

	/**
	 * model to use to handle queueing of data
	 * @var TrueAction_Eb2cProduct_Model_Feed_Queue_Interface
	 */
	protected $_queue;

	private $_eventTypes = array(
		'Price' => 'feed_pricing',
		'ItemMaster' => 'feed_item',
		'Content' => 'feed_content',
		'iShip' => 'feed_iship',
	);

	/**
	 * extracts the event type from the feed file.
	 * @var TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
	 */
	private $_eventTypeExtractor;

	private $_feedFiles = array();

	protected $_eventTypeModel = null;

	public function processFeeds()
	{
		Varien_Profiler::start('processFeeds');
		$filesProcessed = 0;
		// fetch all files for all feeds.
		foreach (array_keys($this->_eventTypes) as $eventType) {
			$this->_eventTypeModel = $this->_getEventTypeModel($eventType);

			$this->_setupCoreFeed();

			$this->_coreFeed->fetchFeedsFromRemote(
				$this->_eventTypeModel->getFeedRemotePath(),
				$this->_eventTypeModel->getFeedFilePattern()
			);
			$this->_feedFiles = array_merge($this->_feedFiles, $this->_coreFeed->lsInboundDir());
		}
		foreach($this->_feedFiles as $xmlFeedFile ) {
			$this->processFile($xmlFeedFile);
			$this->_coreFeed->mvToArchiveDir($xmlFeedFile);
			$filesProcessed++;
		}
		return $filesProcessed;
		Varien_Profiler::stop('processFeeds');
	}

	/**
	 * Processes a single xml file.
	 */
	public function processFile($xmlFile)
	{
		Varien_Profiler::start('processFile');
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return;
		}

		$eventType = $this->_determineEventType($dom);
		$this->_eventTypeModel = $this->_getEventTypeModel($eventType);

		// Validate Eb2c Header Information
		if ( !Mage::helper('eb2ccore/feed')
			->validateHeader($dom, $eventType )
		) {
			Mage::log('File ' . $xmlFile . ': Invalid header', Zend_Log::ERR);
			return;
		}
		try {
			$this->_beforeProcessDom($dom);
		} catch (Mage_Core_Exception $e) {
			Mage::log(sprintf('File %s: error while preparing to process DOM', $xmlFile));
			Mage::logException($e);
			return;
		}
		$this->processDom($dom);
		Varien_Profiler::stop('processFile');
	}

	/**
	 * process a dom document
	 * @param  TrueAction_Dom_Document $doc
	 * @return self
	 */
	public function processDom(TrueAction_Dom_Document $doc)
	{
		Varien_Profiler::start('processDom');
		$units = $this->_getIterableFor($doc);
		foreach ($units as $unit) {
			$operation = $this->getOperationType($unit);
			$isValid = $this->_eventTypeModel->getUnitValidationExtractor()
				->getValue($this->_xpath, $unit);
			if ($isValid) {
				$data = $this->_extractData($unit);
				$operationType = $this->_eventTypeModel->getOperationExtractor()
					->getValue($this->_xpath, $unit);
				$this->_queue->add($data, $operationType);
			}
		}
		Varien_Profiler::stop('processDom');
		return $this;
	}

	/**
	 * setup feed specific internals before attempting to process the dom.
	 * @param  TrueAction_Dom_Document $dom
	 * @return self
	 */
	protected function _beforeProcessDom(TrueAction_Dom_Document $dom)
	{
		$this->_checkPreconditions();
		$this->_setupCoreFeed();
		$this->_xpath = $this->_eventTypeModel->getNewXpath($dom);
		if (!$this->_xpath) {
			$message = '[ ' . __CLASS__ . ' ] unable to get DOMXPath object from model ' .
				get_class($this->_eventTypeModel);
			Mage::throwException($message);
		}
		return $this;
	}

	protected function _determineEventType($doc)
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
		return $eventType;
	}

	protected function _extractData($unit)
	{
		$extractors = $this->_eventTypeModel->getExtractors();
		$result = new Varien_Object();
		foreach ($extractors as $extractor) {
			$data = $extractor->extract($this->_xpath, $unit);
			$result->addData($data);
		}
		return $result;
	}

	/**
	 * get the model for a specified event type.
	 * @param  string $eventType [description]
	 * @return [type]            [description]
	 */
	protected function _getEventTypeModel($eventType)
	{
		return Mage::getSingleton('eb2cproduct/' . $this->_eventTypes[$eventType]);
	}

	protected function _getIterableFor(TrueAction_Dom_Document $doc)
	{
		$baseXpath = $this->_eventTypeModel->getBaseXpath();
		$iterable = $this->_xpath->query($baseXpath);
		return $iterable;
	}

	protected function _setupCoreFeed()
	{
		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'base_dir' => Mage::getBaseDir('var') . DS . $this->_eventTypeModel->getFeedLocalPath()
		);

		// Ready to set up the core feed helper, which manages files and directories:
		$this->_coreFeed = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);
		return $this;
	}

	/**
	 * check the eventTypeModel to see if it is properly configured.
	 * @return [type] [description]
	 */
	protected function _checkPreconditions()
	{
		// Where is the remote path?
		if( is_null($this->_eventTypeModel->getFeedRemotePath()) ) {
			Mage::throwException($this->_missingConfigMessage('FeedRemotePath'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// What is the file pattern for remote retrieval?
		if( is_null($this->_eventTypeModel->getFeedFilePattern()) ) {
			Mage::throwException($this->_missingConfigMessage('FeedFilePattern'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Where is the local path?
		if( is_null($this->_eventTypeModel->getFeedLocalPath()) ) {
			Mage::throwException($this->_missingConfigMessage('FeedLocalPath'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Where is the event type we're processing?
		if( is_null($this->_eventTypeModel->getFeedEventType()) ) {
			Mage::throwException($this->_missingConfigMessage('FeedEventType'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Returns a message string for an exception message
	 * @param string $missingConfigName which config name is missing.
	 */
	private function _missingConfigMessage($missingConfigName)
	{
		return get_class($this->_eventTypeModel) . " was not setup correctly; '$missingConfigName' not configured.";
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
			array(array('event_type' => self::EVENT_TYPE_XPATH))
		);
		$this->_queue = Mage::getSingleton('eb2cproduct/feed_queue');
	}
}

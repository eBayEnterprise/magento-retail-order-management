<?php
class TrueAction_Eb2cProduct_Model_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
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

	/**
	 * feed event types.
	 * WARNING: the order here determines the order the feeds will run.
	 * TODO: this should be moved out to the xml config.
	 * @var array
	 */
	private $_eventTypes = array(
		'ItemMaster' => 'feed_item',
		'Content'    => 'feed_content',
		'Price'      => 'feed_pricing',
		'iShip'      => 'feed_iship',
	);

	/**
	 * extracts the event type from the feed file.
	 * @var TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
	 */
	private $_eventTypeExtractor;

	private $_feedFiles = array();

	protected $_eventTypeModel = null;

	/**
	 * @var string
	 */
	protected $_defaultLangCode = null;

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
		$this->_defaultLangCode = Mage::helper('eb2cproduct')->getDefaultLanguageCode();
	}

	/**
	 * run all feeds define in the _evenType class property
	 * @return int, the number of process feed xml file
	 */
	public function processFeeds()
	{
		Varien_Profiler::start(__METHOD__);
		$filesProcessed = 0;
		$coreFeedHelper = Mage::helper('eb2ccore/feed');
		// fetch all files for all feeds.
		foreach (array_keys($this->_eventTypes) as $eventType) {
			$this->_eventTypeModel = $this->_getEventTypeModel($eventType);

			$this->_coreFeed = $this->_setupCoreFeed();

			$this->_coreFeed->fetchFeedsFromRemote(
				$this->_eventTypeModel->getFeedRemotePath(),
				$this->_eventTypeModel->getFeedFilePattern()
			);
			$remote = $this->_eventTypeModel->getFeedRemotePath();
			$fileList = $this->_coreFeed->lsInboundDir();

			// only merge files when there are actual files
			if ($fileList) {
				// generate error confirmation file by event type
				$errorFile = Mage::helper('eb2cproduct')->buildFileName($eventType);

				// load the file and add the initial data such as xml directive, open node and message header
				Mage::getModel('eb2cproduct/error_confirmations')->loadFile($errorFile)
					->initFeed($eventType);

				// need to track the local file as well as the remote path so it can be removed after processing
				$this->_feedFiles = array_merge($this->_feedFiles, array_map(
					function ($local) use ($remote, $eventType, $coreFeedHelper, $errorFile) {
						$timeStamp = $coreFeedHelper->getMessageDate($local)->getTimeStamp();
						return array(
							'local' => $local,
							'remote' => $remote,
							'timestamp' => $timeStamp,
							'type' => $eventType,
							'error_file' => $errorFile
						); },
					$fileList
				));
			}
		}

		// sort the feed files
		// hidding error from built-in usort php function because of the known bug
		// Warning: usort(): Array was modified by the user comparison function
		@usort($this->_feedFiles, array($this, '_compareFeedFiles'));
		foreach ($this->_feedFiles as $fileDetails) {
			$this->processFile($fileDetails);
			$this->archiveFeed($fileDetails['local'], $fileDetails['remote']);
			$filesProcessed++;
		}
		$this->_queue->process();
		Varien_Profiler::stop(__METHOD__);
		Mage::getModel('eb2cproduct/feed_cleaner')->cleanAllProducts();
		Mage::dispatchEvent('product_feed_processing_complete', array());
		Mage::dispatchEvent('product_feed_complete_error_confirmation', array('feed_details' => $this->_feedFiles));
		return $filesProcessed;
	}

	/**
	 * Processes a single xml file.
	 * @param array $fileDetail
	 * @return self
	 */
	public function processFile(array $fileDetail)
	{
		Varien_Profiler::start(__METHOD__);
		$errorConfirmations = Mage::getModel('eb2cproduct/error_confirmations')->loadFile($fileDetail['error_file']);
		$fileName = basename($fileDetail['local']);

		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		try {
			$dom->load($fileDetail['local']);
		} catch(Exception $e) {
			Mage::logException($e);
			$errorConfirmations->addMessage($errorConfirmations::DOM_LOAD_ERR, $e->getMessage())
				->addError($fileDetail['type'], $fileName)
				->addErrorConfirmation($fileDetail['type'])
				->flush();
			return;
		}

		try {
			$eventType = $this->_determineEventType($dom);
		} catch (TrueAction_Eb2cProduct_Model_Feed_Exception $e) {
			Mage::log(sprintf('The following Event Type error occurred: %s', $e->getMessage()), Zend_Log::ERR);
			$errorConfirmations->addMessage($errorConfirmations::EVENT_TYPE_ERR, $e->getMessage())
				->addError($fileDetail['type'], $fileName)
				->addErrorConfirmation($fileDetail['type'])
				->flush();
			return;
		}

		$this->_eventTypeModel = $this->_getEventTypeModel($eventType);

		// Validate Eb2c Header Information
		if ( !Mage::helper('eb2ccore/feed')->validateHeader($dom, $eventType )) {
			Mage::log(sprintf('File %s: Invalid header', $fileDetail['local']), Zend_Log::ERR);
			$errorConfirmations->addMessage($errorConfirmations::INVALID_HEADER_ERR, $fileName)
				->addError($fileDetail['type'], $fileName)
				->addErrorConfirmation($fileDetail['type'])
				->flush();
			return;
		}

		try {
			$this->_beforeProcessDom($dom);
		} catch (Mage_Core_Exception $e) {
			Mage::log(sprintf('File %s: error while preparing to process DOM', $fileDetail['local']), Zend_Log::ERR);
			Mage::logException($e);
			$errorConfirmations->addMessage($errorConfirmations::BEFORE_PROCESS_DOM_ERR, $e->getMessage())
				->addError($fileDetail['type'], $fileName)
				->addErrorConfirmation($fileDetail['type'])
				->flush();
			return;
		}

		$this->processDom($dom, $fileDetail);
		Varien_Profiler::stop(__METHOD__);

		return $this;
	}

	/**
	 * process a dom document
	 * @param  TrueAction_Dom_Document $doc
	 * @param array $fileDetail
	 * @return self
	 */
	public function processDom(TrueAction_Dom_Document $doc, array $fileDetail)
	{
		Varien_Profiler::start(__METHOD__);
		$errorConfirmations = Mage::getModel('eb2cproduct/error_confirmations')->loadFile($fileDetail['error_file']);
		$fileName = basename($fileDetail['local']);

		$units = $this->_getIterableFor($doc);
		foreach ($units as $unit) {
			$isValid = $this->_eventTypeModel->getUnitValidationExtractor()
				->getValue($this->_xpath, $unit);
			if ($isValid) {
				$data = $this->_extractData($unit);
				$operationType = $this->_eventTypeModel->getOperationExtractor()
					->getValue($this->_xpath, $unit);
				$data->addData(array('file_detail' => $fileDetail));
				try {
					$this->_queue->add($data, $operationType);
				} catch (TrueAction_Eb2cProduct_Model_Feed_Exception $e) {
					Mage::log(sprintf('[ %s ] Error occurred while adding unit to queue: %s', __CLASS__, $e->getMessage()), Zend_Log::ERR);
					$errorConfirmations->addMessage($errorConfirmations::INVALID_OPERATION_ERR, $e->getMessage())
						->addError($fileDetail['type'], $fileName)
						->addErrorConfirmation($fileDetail['type'])
						->flush();
				}
			} else {
				$errorConfirmations->addMessage($errorConfirmations::INVALID_DATA_ERR, $fileName)
					->addError($fileDetail['type'], $fileName)
					->addErrorConfirmation($fileDetail['type'])
					->flush();
			}
		}
		Varien_Profiler::stop(__METHOD__);
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
		$this->_coreFeed = $this->_setupCoreFeed();
		$this->_xpath = $this->_eventTypeModel->getNewXpath($dom);
		if (!$this->_xpath) {
			Mage::throwException(sprintf('[ %s ] unable to get DOMXPath object from model %s',
				__CLASS__, get_class($this->_eventTypeModel)
			));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		return $this;
	}

	/**
	 * compare feedFile entries and return an integer to represent whether
	 * $a has higher, same, or lower priority than $b
	 * @param  array  $a entry in _feedFiles
	 * @param  array  $b entry in _feedFiles
	 * @return int
	 */
	protected function _compareFeedFiles(array $a, array $b)
	{
		$timeDiff = $a['timestamp'] - $b['timestamp'];
		if ($timeDiff !== 0) {
			return $timeDiff;
		}
		$types = array_keys($this->_eventTypes);
		return (int) (array_search($a['type'], $types) - array_search($b['type'], $types));
	}

	/**
	 * determine the event type in the loaded xml feed
	 * if the event type in the feed doesn't match the one of the expected value
	 * then throw an error otherwise return the eventtype found
	 * @param TrueAction_Dom_Document $doc
	 * @return string
	 * @throws TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	protected function _determineEventType(TrueAction_Dom_Document $doc)
	{
		$this->_xpath = new DomXPath($doc);
		$nodeList = $this->_xpath->query(self::EVENT_TYPE_XPATH, $doc->documentElement);
		$eventType = null;
		if ($nodeList->item(0)) {
			$eventType = $nodeList->item(0)->nodeValue;
		}
		if (array_search($eventType, array_keys($this->_eventTypes)) === false) {
			$message = sprintf(self::INVALID_EVENT_TYPE, $eventType);
			throw new TrueAction_Eb2cProduct_Model_Feed_Exception($message);
		}
		return $eventType;
	}

	/**
	 * extracting dom data into varien object
	 * @param DOMElement $unit
	 * @return self
	 */
	protected function _extractData(DOMElement $unit)
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
		return Mage::getSingleton(sprintf('eb2cproduct/%s', $this->_eventTypes[$eventType]));
	}

	/**
	 * getting the nodelist for the dom document
	 * @param TrueAction_Dom_Document $doc, the document got get the nodelist
	 * @return DOMNodeList
	 */
	protected function _getIterableFor(TrueAction_Dom_Document $doc)
	{
		$baseXpath = $this->_eventTypeModel->getBaseXpath();
		$iterable = $this->_xpath->query($baseXpath);
		return $iterable;
	}

	/**
	 * getting eb2ccore/feed model instantiated object
	 * @return TrueAction_Eb2cCore_Model_Feed
	 */
	protected function _setupCoreFeed()
	{
		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'base_dir' => sprintf('%s%s%s', Mage::getBaseDir('var'), DS, $this->_eventTypeModel->getFeedLocalPath())
		);

		// Ready to set up the core feed helper, which manages files and directories:
		return Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);
	}

	/**
	 * check the eventTypeModel to see if it is properly configured.
	 * @return void | throw a Mage_Core_Exception
	 */
	protected function _checkPreconditions()
	{
		// Where is the remote path?
		if( is_null($this->_eventTypeModel->getFeedRemotePath()) ) {
			throw new TrueAction_Eb2cProduct_Model_Config_Exception($this->_missingConfigMessage('FeedRemotePath'));
		}

		// What is the file pattern for remote retrieval?
		if( is_null($this->_eventTypeModel->getFeedFilePattern()) ) {
			throw new TrueAction_Eb2cProduct_Model_Config_Exception($this->_missingConfigMessage('FeedFilePattern'));
		}

		// Where is the local path?
		if( is_null($this->_eventTypeModel->getFeedLocalPath()) ) {
			throw new TrueAction_Eb2cProduct_Model_Config_Exception($this->_missingConfigMessage('FeedLocalPath'));
		}

		// Where is the event type we're processing?
		if( is_null($this->_eventTypeModel->getFeedEventType()) ) {
			throw new TrueAction_Eb2cProduct_Model_Config_Exception($this->_missingConfigMessage('FeedEventType'));
		}
	}

	/**
	 * Returns a message string for an exception message
	 * @param string $missingConfigName which config name is missing.
	 */
	protected function _missingConfigMessage($missingConfigName)
	{
		return sprintf("%s was not setup correctly; '%s' not configured.", get_class($this->_eventTypeModel), $missingConfigName);
	}
}

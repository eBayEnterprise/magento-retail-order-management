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

	protected $_eventTypeModel = null;

	/**
	 * feed event types.
	 * WARNING: the order here determines the order the feeds will run.
	 * TODO: this should be moved out to the xml config.
	 * @var array
	 */
	protected $_eventTypes = array();

	/**
	 * suppress the core feed's initialization
	 * create necessary internal models.
	 * @return [type] [description]
	 */
	protected function _construct()
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_eventTypes = array(
			$cfg->itemFeedEventType => 'feed_item',
			$cfg->contentFeedEventType => 'feed_content',
			$cfg->pricingFeedEventType => 'feed_pricing',
			$cfg->iShipFeedEventType => 'feed_iship',
		);
	}

	/**
	 * pull down the feed files from remote server defined in the event type object
	 * to this method and then return array of files
	 * @param TrueAction_Eb2cProduct_Model_Feed_Abstract $eventTypeModel
	 * @return array
	 */
	protected function _fetchFiles(TrueAction_Eb2cProduct_Model_Feed_Abstract $eventTypeModel)
	{
		$baseDir = Mage::getBaseDir('var') . DS . $eventTypeModel->getFeedLocalPath();
		$this->_coreFeed = Mage::getModel('eb2ccore/feed', array('base_dir' => $baseDir));
		$this->_coreFeed->fetchFeedsFromRemote($eventTypeModel->getFeedRemotePath(), $eventTypeModel->getFeedFilePattern());
		return $this->_coreFeed->lsInboundDir();
	}

	/**
	 * merged all the feed files into a single list of feed file objects
	 * @param TrueAction_Eb2cProduct_Model_Feed_Abstract $eventTypeModel
	 * @param array $feedFiles
	 * @param array $fileList
	 * @param string $eventType
	 * @param string $errorFile
	 * @return array
	 */
	protected function _unifiedAllFiles(TrueAction_Eb2cProduct_Model_Feed_Abstract $eventTypeModel, array $feedFiles, array $fileList, $eventType, $errorFile)
	{
		$remote = $eventTypeModel->getFeedRemotePath();
		$coreFeedHelper = Mage::helper('eb2ccore/feed');
		return array_merge($feedFiles, array_map(
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

	/**
	 * get a list of all feed files object to be process that's already been
	 * sorted so that all I want to do is simply loop through it and process and archive them
	 * @return array
	 */
	protected function _getAllFeedFiles()
	{
		$feedFiles = array();

		// fetch all files for all feeds.
		foreach (array_keys($this->_eventTypes) as $eventType) {
			$eventTypeModel = $this->_getEventTypeModel($eventType);
			$fileList = $this->_fetchFiles($eventTypeModel);

			// only merge files when there are actual files
			if ($fileList) {
				// generate error confirmation file by event type
				$errorFile = Mage::helper('eb2cproduct')->buildFileName($eventType);
				// load the file and add the initial data such as xml directive, open node and message header
				Mage::getModel('eb2cproduct/error_confirmations')->loadFile($errorFile)
					->initFeed($eventType);
				// need to track the local file as well as the remote path so it can be removed after processing
				$feedFiles = $this->_unifiedAllFiles($eventTypeModel, $feedFiles, $fileList, $eventType, $errorFile);
			}
		}

		// sort the feed files
		// hidding error from built-in usort php function because of the known bug
		// Warning: usort(): Array was modified by the user comparison function
		@usort($feedFiles, array($this, '_compareFeedFiles'));

		return $feedFiles;
	}

	/**
	 * get all feed files process each one, then archived them, then clean all products
	 * after completing processing and archiving, then dispatch product_feed_processing_complete event
	 * then dispatch product_feed_complete_error_confirmation event and then return the number
	 * of feed files that were processed
	 * @return int, the number of process feed xml file
	 */
	public function processFeeds()
	{
		Varien_Profiler::start(__METHOD__);
		$feedFiles = $this->_getAllFeedFiles();
		$filesProcessed = 0;
		foreach ($feedFiles as $feedFile) {
			// setting the feed event type via magic for the order for
			// header validation to run properly in TrueAction_Eb2cCore_Model_Feed_Abstract::processFile
			$this->setFeedEventType($feedFile['type']);
			$this->processFile($feedFile);
			$this->archiveFeed($feedFile['local'], $feedFile['remote']);
			$filesProcessed++;
		}
		Varien_Profiler::stop(__METHOD__);
		Mage::getModel('eb2cproduct/feed_cleaner')->cleanAllProducts();
		Mage::dispatchEvent('product_feed_processing_complete', array());
		Mage::dispatchEvent('product_feed_complete_error_confirmation', array('feed_details' => $feedFiles));
		return $filesProcessed;
	}

	/**
	 * @see TrueAction_Eb2cCore_Model_Feed_Abstract::processDom
	 * process a dom document
	 * @param  TrueAction_Dom_Document $doc
	 * @param array $fileDetail
	 * @return self
	 */
	public function processDom(TrueAction_Dom_Document $doc, array $fileDetail)
	{
		Varien_Profiler::start(__METHOD__);
		Mage::log(sprintf('[%s] processing %s', __CLASS__, $fileDetail['local']), Zend_Log::DEBUG);
		$fileDetail['doc'] = $doc;
		Mage::getModel('eb2cproduct/feed_file', $fileDetail)->process();
		Varien_Profiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * compare feedFile entries and return an integer to represent whether
	 * $a has higher, same, or lower priority than $b
	 * @param  array $a entry in _feedFiles
	 * @param  array $b entry in _feedFiles
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
	 * get the model for a specified event type.
	 * @param  string $eventType [description]
	 * @return [type]            [description]
	 */
	protected function _getEventTypeModel($eventType)
	{
		return Mage::getSingleton(sprintf('eb2cproduct/%s', $this->_eventTypes[$eventType]));
	}
}

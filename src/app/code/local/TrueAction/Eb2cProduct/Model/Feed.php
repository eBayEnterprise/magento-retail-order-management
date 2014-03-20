<?php
class TrueAction_Eb2cProduct_Model_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * Config registry keys for the configuration used for each feed processed
	 * by this model. If additional feeds are to be handled by this model, the
	 * config registry key for the feeds configuration needs to be added here.
	 * @var array
	 */
	protected $_feedConfigKeys = array('itemFeed', 'contentFeed', 'pricingFeed', 'iShipFeed');
	/**
	 * Feed event types, populated using the config registry keys set in
	 * self::$_feedConfigKeys. When sorting files for processing order, this
	 * array will be used to break ties between two files with the same creation
	 * time in the file name. The order of this array determines the weightings
	 * and will match the order of the config registry keys used to populate
	 * this list.
	 * @var array
	 */
	protected $_eventTypes = array();
	/**
	 * Array of core feed models, loaded with the config for each type of feed
	 * handled by this model - item, content, etc.
	 * @var array
	 */
	protected $_coreFeedTypes = array();

	/**
	 * suppress the core feed's initialization
	 * create necessary internal models.
	 */
	protected function _construct()
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		foreach ($this->_feedConfigKeys as $feedConfig) {
			$coreFeed = Mage::getModel('eb2ccore/feed', array('feed_config' => $cfg->$feedConfig));
			$this->_coreFeedTypes[] = $coreFeed;
			$this->_eventTypes[] = $coreFeed->getEventType();
		}
	}

	/**
	 * Create the map of feed data for all local files in the file list. Then,
	 * merge that array of file data with the current set of file data in feed
	 * files.
	 * @param array $feedFiles Existing set of file data
	 * @param array $fileList List of local files to create feed data for and merge with $feedFiles
	 * @param string $coreFeed The core feed model containing configuration for the feed type
	 * @param string $errorFile Error file the feed file should use
	 * @return array
	 */
	protected function _unifiedAllFiles(array $feedFiles, array $fileList, $coreFeed, $errorFile)
	{
		$coreFeedHelper = Mage::helper('eb2ccore/feed');
		return array_merge(
			$feedFiles,
			array_map(
				function ($local) use ($coreFeed, $coreFeedHelper, $errorFile) {
					return array(
						'local_file' => $local,
						'timestamp' => $coreFeedHelper->getMessageDate($local)->getTimeStamp(),
						'core_feed' => $coreFeed,
						'error_file' => $errorFile
					);
				},
				$fileList
			)
		);
	}

	/**
	 * get a list of all feed files object to be process that's already been
	 * sorted so that all I want to do is simply loop through it and process and archive them
	 * @return array
	 */
	protected function _getFilesToProcess()
	{
		$feedFiles = array();
		// fetch all files for all feeds.
		foreach ($this->_coreFeedTypes as $coreFeed) {
			$fileList = $coreFeed->lsLocalDirectory();
			// only merge files when there are actual files
			if ($fileList) {
				$eventType = $coreFeed->getEventType();
				// generate error confirmation file by event type
				$errorFile = Mage::helper('eb2cproduct')->buildFileName($eventType);
				// load the file and add the initial data such as xml directive, open node and message header
				Mage::getModel('eb2cproduct/error_confirmations')->loadFile($errorFile)
					->initFeed($eventType);
				// need to track the local file as well as the remote path so it can be removed after processing
				$feedFiles = $this->_unifiedAllFiles($feedFiles, $fileList, $coreFeed, $errorFile);
			}
		}
		// sort the feed files
		// hidding error from built-in usort php function because of the known bug
		// Warning: usort(): Array was modified by the user comparison function
		@usort($feedFiles, array($this, '_compareFeedFiles'));

		return $feedFiles;
	}
	/**
	 * Get all product files to be processed and process them. After completing
	 * the processing, kick off the cleaner and dispatch events to signal product
	 * importing is complete.
	 * @return int, the number of process feed xml file
	 */
	public function processFeeds()
	{
		$filesProcessed = 0;
		$feedFiles = $this->_getFilesToProcess();
		// This needs to be duplicated from the parent class as the error
		// confirmation event dispatched at the end of this method needs to have
		// the list of files processed, which wouldn't be accessible if just using
		// a call to the parent method.
		foreach ($feedFiles as $feedFile) {
			try {
				$this->processFile($feedFile);
				$filesProcessed++;
			// @todo - there should be two types of exceptions handled here, Mage_Core_Exception and
			// TrueAction_Core_Feed_Failure. One should halt any further feed processing and
			// one should just log the error and move on. Leaving out the TrueAction_Core_Feed_Failure
			// for now as none of the feeds expect to use it.
			} catch (Mage_Core_Exception $e) {
				Mage::helper('trueaction_magelog')->logWarn(
					'[%s] Failed to process file, %s. %s',
					array(__CLASS__, basename($feedFile['local_file']), $e->getMessage())
				);
			}
		}
		// Only trigger the cleaner and reindexing event if at least one feed
		// was processed.
		if ($filesProcessed) {
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanAllProducts();
			Mage::dispatchEvent('product_feed_processing_complete', array());
		}
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
		Mage::log(sprintf('[%s] processing %s', __CLASS__, $fileDetail['local_file']), Zend_Log::DEBUG);
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
		return (int) (
			array_search($a['core_feed']->getEventType(), $this->_eventTypes) -
			array_search($b['core_feed']->getEventType(), $this->_eventTypes)
		);
	}
}

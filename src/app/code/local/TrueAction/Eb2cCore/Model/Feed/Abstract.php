<?php
/**
 * A Feed Abstraction for implementing feed processors.
 */
abstract class TrueAction_Eb2cCore_Model_Feed_Abstract extends Mage_Core_Model_Abstract
{
	protected $_coreFeed; // Handles file fetching, moving, listing, etc.

	/**
	 * Processes the DOM loaded into xmlDom. At minimum, you'll have to implement this. You may
	 * wish to override processFile and/ or processFeeds as well if there's something unusual
	 * you need to do.
	 *
	 * @see processFeeds
	 * @see processFile
	 */
	abstract public function processDom(TrueAction_Dom_Document $xmlDom);

	/**
	 * Returns a message string for an exception message
	 *
	 * @param string $missingConfigName which config name is missing.
	 */
	private function _missingConfigMessage($missingConfigName)
	{
		return __CLASS__ . " can't be instantiated, '$missingConfigName' not configured.";
	}

	protected function _construct()
	{
		if( !$this->hasFeedConfig() ) {
			Mage::throwException( __CLASS__ . ' no configuration specifed.');
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Where is the remote path?
		if( !$this->hasFeedRemotePath() ) {
			Mage::throwException($this->_missingConfigMessage('FeedRemotePath'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// What is the file pattern for remote retrieval?
		if( !$this->hasFeedFilePattern() ) {
			Mage::throwException($this->_missingConfigMessage('FeedFilePattern'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Where is the local path?
		if( !$this->hasFeedLocalPath() ) {
			Mage::throwException($this->_missingConfigMessage('FeedLocalPath'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Where is the event type we're processing?
		if( !$this->hasFeedEventType() ) {
			Mage::throwException($this->_missingConfigMessage('FeedEventType'));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'base_dir' => Mage::getBaseDir('var') . DS . $this->getFeedLocalPath()
		);

		// FileSystem tool can be supplied, esp. for testing
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		// Ready to set up the core feed helper, which manages files and directories:
		$this->_coreFeed = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);
	}

	/**
	 * Fetches feeds from the remote, and then loops through all files found in the Inbound Dir.
	 *
	 * @return int Number of files we looked at.
	 */
	public function processFeeds()
	{
		$filesProcessed = 0;
		$this->_coreFeed->fetchFeedsFromRemote($this->getFeedRemotePath(), $this->getFeedFilePattern());
		foreach( $this->_coreFeed->lsInboundDir() as $xmlFeedFile ) {
			try {
				$this->processFile($xmlFeedFile);
				$filesProcessed++;
			// @todo - there should be two types of exceptions handled here, Mage_Core_Exception and
			// TrueAction_Core_Feed_Failure. One should halt any further feed processing and
			// one should just log the error and move on. Leaving out the TrueAction_Core_Feed_Failure
			// for now as none of the feeds expect to use it.
			} catch (Mage_Core_Exception $e) {
				Mage::log(sprintf('[ %s ] Failed to process file, %s.', __CLASS__, $xmlFeedFile), Zend_Log::WARN);
			}
			$this->archiveFeed($xmlFeedFile);
		}
		return $filesProcessed;
	}

	/**
	 * Archive the file after processing - move the local copy to the archive dir for the feed
	 * and delete the file off of the remote sftp server.
	 * @param  string $xmlFeedFile Local path of the file
	 * @return $this object
	 */
	public function archiveFeed($xmlFeedFile)
	{
		$config = Mage::getModel('eb2ccore/config_registry')->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		if ($config->deleteRemoteFeedFiles) {
			$this->_coreFeed->removeFromRemote($this->getFeedRemotePath(), basename($xmlFeedFile));
		}
		$this->_coreFeed->mvToArchiveDir($xmlFeedFile);
		return $this;
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
		} catch (Exception $e) {
			Mage::logException($e);
			return;
		}

		// Validate Eb2c Header Information
		if ( !Mage::helper('eb2ccore/feed')
			->validateHeader($dom, $this->getFeedEventType() )
		) {
			Mage::log(sprintf('[%s] File %s: Invalid header', __CLASS__, $xmlFile), Zend_Log::ERR);
			return;
		}
		$this->processDom($dom);
	}
}

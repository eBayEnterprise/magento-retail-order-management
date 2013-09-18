<?php
/**
 * A Feed Abstraction for implementing feed processors.
 */
abstract class TrueAction_Eb2cCore_Model_Feed_Abstract extends Mage_Core_Model_Abstract
{
	protected $_coreFeed;    // Handles file fetching, moving, listing, etc.

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
		return __CLASS__ . ' can\'t be instantiated, \'' . $missingConfigName . '\' not configured.';
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
			'base_dir' => $this->getFeedLocalPath()
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
			$this->processFile($xmlFeedFile);
			$filesProcessed++;
		}
		return $filesProcessed;
	}

	/**
	 * Processes a single xml file.
	 *
	 * @return int number of Records we looked at.
	 */
	public function processFile($xmlFile)
	{
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return 0;
		}

		// Validate Eb2c Header Information
		if ( !Mage::helper('eb2ccore/feed')
			->validateHeader($dom, $this->getFeedEventType() )
		) {
			Mage::log('File ' . $xmlFile . ': Invalid header', Zend_Log::ERR);
			return 0;
		}
		return $this->processDom($dom);
	}
}

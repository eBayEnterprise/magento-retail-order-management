<?php
/**
 * A Feed Abstraction for implementing feed processors.
 */
abstract class TrueAction_Eb2cCore_Model_Feed_Abstract extends Mage_Core_Model_Abstract
{
	private $_coreFeed;    // Handles file fetching, moving, listing, etc.
	private $_remotePath;  // Where are your files on the remote?

	/**
	 * Processes the DOM loaded into xmlDom. At minimum, you'll have to implement this. You may
	 * wish to override processFile and/ or processFeeds as well if there's something unusual
	 * you need to do.
	 *
	 * @see processFeeds
	 * @see processFile
	 */
	abstract public function processDom(TrueAction_Dom_Document $xmlDom);

	protected function _construct()
	{
		// Where is the remote path?
		if( !$this->hasRemotePath() ) {
			Mage::throwException( __CLASS__ . '::' . __FUNCTION__ . ' can\'t instantiate, no remote path given.');
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_remotePath = $this->getRemotePath();

		// Where is the local path?
		if( !$this->hasLocalPath() ) {
			Mage::throwException( __CLASS__ . '::' . __FUNCTION__ . ' can\'t instantiate, no local path given.');
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array('base_dir' => $this->getLocalPath());

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
		$this->_coreFeed->fetchFeedsFromRemote($this->_remotePath);
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
		return $this->processDom($dom);
	}
}

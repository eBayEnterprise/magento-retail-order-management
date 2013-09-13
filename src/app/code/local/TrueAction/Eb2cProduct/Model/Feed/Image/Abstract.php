<?php
/**
 * A feed class abstraction that we should be able to refactor and move to core, eventually
 */
class TrueAction_Eb2cProduct_Model_Feed_Image_Abstract extends Mage_Core_Model_Abstract
{
	private $_coreFeed;
	private $_fileTransferHelper;
	private $_remotePath;

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

		// Set up remote conduit:
		$this->_fileTransferHelper = Mage::helper('filetransfer');
	}

	/** * Fetch the remote files.
	 */
	private function _fetchFeedsFromRemote()
	{
		$cfg = Mage::helper('eb2ccore/feed');
		try {
			$this->_fileTransferHelper->getFile(
				$this->_coreFeed->getInboundDir(),
				$this->remotePath,
				$cfg::FILETRANSFER_CONFIG_PATH
			);
		} catch (Exception $e ) {
			Mage::logException($e);
		}
	}

	/**
	 * Loops through all files found in the Inbound Dir.
	 *
	 * @return int Number of files we looked at.
	 */
	public function processFeeds()
	{
		$filesProcessed = 0;
		$this->_fetchFeedsFromRemote();
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

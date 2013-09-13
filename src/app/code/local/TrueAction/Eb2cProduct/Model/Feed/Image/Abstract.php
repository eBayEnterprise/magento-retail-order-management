<?php
/**
 * Let's try to build an abstract feed class, shall we
 */
class TrueAction_Eb2cProduct_Model_Feed_Image_Abstract extends Mage_Core_Model_Abstract
{
	private $_localIo;
	private $_remoteIo;
	private $_remotePath;

	private $_fileInfo;

	protected function _construct()
	{
		// Where is the remote path?
		$this->_remotePath = $this->getRemotePath();

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array('base_dir' => $this->getLocalPath());
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}
		$this->_localIo = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);

		// Set up remote conduit:
		$this->_remoteIo = Mage::helper('filetransfer');
	}

	/**
	 * Fetch the remote files.
	 */
	private function _fetchFeedsFromRemote()
	{
		$cfg = Mage::helper('eb2ccore/feed');
		$this->_remoteIo->getFile(
			$this->_localIo->getInboundDir(),
			$this->remotePath,
			$cfg::FILETRANSFER_CONFIG_PATH
		);
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
		foreach( $this->_localIo->lsInboundDir() as $xmlFeedFile ) {
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
		// Load the XML:
		$dom = new TrueAction_Dom_Document();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return 0;
		}
		return $this->processXmlDom($dom);
	}
}

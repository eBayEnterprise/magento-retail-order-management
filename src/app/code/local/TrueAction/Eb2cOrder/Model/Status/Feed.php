<?php
/**
 * Order Status processing Class.
 *	- Gets Order Status feeds from remote
 *	- For each file:
 * 		+ Verify it's in the correct sequence
 * 		+ Apply the specified updates
 * 		+ Note/ Remember which files have been processed
 *		+ Archive / Move to Error folders depending upon success/ failure
 */
class TrueAction_Eb2cOrder_Model_Status_Feed extends Mage_Core_Model_Abstract
{
	private $_helper;
	private $_config;
	private $_feedIo;
	private $_remoteIo;

	public function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();

		// Set up local folders for receiving, processing, etc:
		$this->_localIo = Mage::getModel('eb2ccore/feed')->setBaseFolder($this->_config->statusFeedLocalPath);

		// Set up remote conduit:
		$this->_remoteIo = Mage::helper('filetransfer');
	}

	/**
	 * Fetch the remote files.
	 */
	private function _fetchFeedsFromRemote()
	{
		$this->_remoteIo->getFile(
					$this->_localIo->getInboundFolder(),
					$this->_config->statusFeedRemotePath,
					$this->_config->fileTransferConfigPath
			);	// Gets the files. 
	}

	/**
	 * Loops through all files found in the Inbound Folder.
	 */
	public function processFeeds()
	{
		$this->_fetchFeedsFromRemote();
		foreach( $this->_localIo->lsInboundFolder() as $xmlFeedFile ) {
			$this->processFile($xmlFeedFile);
		}
		return true;
	}

	/**
	 * Processes a single xml file.
	 */
	public function processFile($xmlFile)
	{
		$dom = $this->_helper->getDomDocument();
		// Presumably load and run? $dom->load($xmlFile);
		$dom = null;
		// Archive or move to Error here
		return;
	}
}

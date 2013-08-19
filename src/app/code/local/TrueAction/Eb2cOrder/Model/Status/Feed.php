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
	private $_config;
	private $_coreFeedHelper;
	private $_coreHelper;
	private $_feedIo;
	private $_helper;
	private $_remoteIo;

	public function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
		$this->_coreFeedHelper = $this->_helper->getCoreFeedHelper();
		$this->_coreHelper = $this->_helper->getCoreHelper();

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
		$dom = new TrueAction_Dom_Document();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return false;
		}
		if (!$this->_coreFeedHelper->validateHeader($dom,
				$this->_config->statusFeedEventType,
				$this->_config->statusFeedHeaderVersion))
		{
			return false;
		}
		// Archive or move to Error here
		return true;
	}
}

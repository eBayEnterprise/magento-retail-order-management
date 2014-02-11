<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * all dirs exists.
 *
 * @method string getArchivePath()
 * @method string getBaseDir()
 * @method string getFsTool()
 * @method string getInboundPath()
 * @method string getOutboundPath()
 * @method string setBaseDir(string pathName)
 */
class TrueAction_Eb2cCore_Model_Feed extends Varien_Object
{
	const INBOUND_DIR_NAME  = 'inbound';
	const OUTBOUND_DIR_NAME = 'outbound';
	const ARCHIVE_DIR_NAME  = 'archive';
	/**
	 * Turn on allow create folders; it's off by default in the base Varien_Io_File. Set up
	 * subdirectories if we're passed a base_dir
	 */
	protected function _construct()
	{
		if (!$this->hasFsTool()) {
			$this->setFsTool(new Varien_Io_File());
		}
		$this->getFsTool()->setAllowCreateFolders(true)->open();
		if ($this->hasBaseDir()) {
			$this->setUpDirs();
		}
	}
	/**
	 * Return configuration registry
	 * @return eb2ccore/config_registry+eb2ccore/config
	 */
	protected function _getCoreConfig() {
		return Mage::getModel('eb2ccore/config_registry')
			->setStore(null)
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}

	/**
	 * Assigns our folder variable and does the recursive creation
	 * @param string $path the full path to the directory to set up.
	 * @return boolean
	 */
	protected function _setCheckAndCreateDir($path)
	{
		return $this->getFsTool()->checkAndCreateFolder($path);
	}
	/**
	 * Wrapper function/scaffolding for calls that involve remote connections and
	 * should be retried. Will retry up to a configured number of times. Meant to be used
	 * with TrueAction/FileTransfer's helper methods.
	 *
	 * @param  callable $callable Callback to be tried each time.
	 * @param  array    $argArray Arguments that should be passed to the callable.
	 * @return void
	 */
	protected function _remoteCall($callable, $argArray)
	{
		$connectionAttempts = 0;
		$coreConfig = $this->_getCoreConfig();
		while(true) {
			try {
				$connectionAttempts++;
				call_user_func_array($callable, $argArray);
				break;
			} catch( TrueAction_FileTransfer_Exception_Connection $e ) {
				// Connection exceptions we'll retry, could be a temporary condition
				Mage::logException($e);
				if( $connectionAttempts >= $coreConfig->feedFetchConnectAttempts ) {
					Mage::log('Connect failed, retry limit reached', Zend_Log::ERR);
					break;
				}
				else {
					Mage::log(
						sprintf('Connect failed, sleeping %d seconds (attempt %d of %d)',
						$coreConfig->feedFetchRetryTimer, $connectionAttempts, $coreConfig->feedFetchConnectAttempts),
						Zend_Log::DEBUG
					);
					sleep($coreConfig->feedFetchRetryTimer);
				}
			} catch (Exception $e ) {
				// Any other exception is failure, log and return
				Mage::logException($e);
				break;
			}
		}
	}
	/**
	 * For feeds, just configure a base folder, and you'll get the rest.
	 */
	public function setUpDirs()
	{
		$base = $this->getBaseDir();
		if (!$base) {
			Mage::throwException('No base dir specified. Cannot set up dirs.');
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->addData(array(
			'inbound_path'  => $base . DS . self::INBOUND_DIR_NAME,
			'outbound_path' => $base . DS . self::OUTBOUND_DIR_NAME,
			'archive_path'  => $base . DS . self::ARCHIVE_DIR_NAME,
		));
		$this->_setCheckAndCreateDir($this->getInboundPath());
		$this->_setCheckAndCreateDir($this->getOutboundPath());
		$this->_setCheckAndCreateDir($this->getArchivePath());
	}
	/**
	 * Fetchs feeds from remote, places them into inBoundPath
	 *
	 * @param string $remotePath path on remote to pull from
	 * @param string $filePattern filename pattern  to match
	 */
	public function fetchFeedsFromRemote($remotePath, $filePattern)
	{
		if (Mage::helper('eb2ccore')->isValidFtpSettings()) {
			$cfg = Mage::helper('eb2ccore/feed');
			$this->_remoteCall(
				array(Mage::helper('filetransfer'), 'getAllFiles'),
				array(
					$this->getInboundPath(),
					$remotePath,
					$filePattern,
					$cfg::FILETRANSFER_CONFIG_PATH,
				)
			);
			// Acknowledge Receipt of newly received files. If we decide to remove after receipt - this is the place.
			$newlyReceivedFeeds = $this->lsInboundDir();
			foreach ($newlyReceivedFeeds as $newFeedPath) {
				$this->_acknowledgeReceipt($newFeedPath);
			}
		} else {
			Mage::log(sprintf('[%s] Invalid sftp configuration, please configure sftp setting.', __METHOD__), Zend_Log::WARN);
		}
	}
	/**
	 * Remote the file from the remote.
	 * @param  string $remotePath Directory the file resides in.
	 * @param  string $fileName   Name of the file to remove
	 * @return void
	 */
	public function removeFromRemote($remotePath, $fileName)
	{
		if (Mage::helper('eb2ccore')->isValidFtpSettings()) {
			$cfg = Mage::helper('eb2ccore/feed');
			$this->_remoteCall(
				array(Mage::helper('filetransfer'), 'deleteFile'),
				array($remotePath . DS . $fileName, $cfg::FILETRANSFER_CONFIG_PATH)
			);
		} else {
			Mage::log(sprintf('[%s] Invalid sftp configuration, please configure sftp setting.', __METHOD__), Zend_Log::WARN);
		}
	}
	/**
	 * Lists contents of the Inbound Dir
	 *
	 * @return array() of file names
	 */
	public function lsInboundDir($filetype='xml')
	{
		$dirContents = array();
		$this->getFsTool()->cd($this->getInboundPath());
		foreach ($this->getFsTool()->ls() as $file) {
			if (!strcasecmp($filetype, $file['filetype'])) {
				$dirContents[] = $this->getFsTool()->pwd() . DS . $file['text'];
			}
		}
		asort($dirContents);
		return $dirContents;
	}
	/**
	 * mv a source file to a directory
	 *
	 * @param string $srcFile
	 * @param string $targetDir
	 * @return boolean
	 */
	protected function _mvToDir($srcFile, $targetDir)
	{
		$dest = $targetDir . DS . basename($srcFile);
		return $this->getFsTool()->mv($srcFile, $dest);
	}
	/**
	 * mv file to Inbound Dir
	 *
	 * @param string $filePath to move
	 * @return boolean
	 */
	public function mvToInboundDir($filePath)
	{
		return $this->_mvToDir($filePath, $this->getInboundPath());
	}
	/**
	 * mv file to Outbound Dir
	 *
	 * @param string $filePath to move
	 * @return boolean
	 */
	public function mvToOutboundDir($filePath)
	{
		return $this->_mvToDir($filePath, $this->getOutboundPath());
	}
	/**
	 * mv file to Archive Dir
	 *
	 * @param string $filePath to move
	 * @return boolean
	 */
	public function mvToArchiveDir($filePath)
	{
		return $this->_mvToDir($filePath, $this->getArchivePath());
	}
	/**
	 * Build the Acknowledgement file's name
	 * @param eventType (as parsed from newly-received feed)
	 * @return string base filename
	 */
	protected function _getBaseAckFileName($eventType)
	{
		$coreConfig = $this->_getCoreConfig();
		$timestamp  = date($coreConfig->feedAckTimestampFormat);
		$filename   = str_replace(
			array('{eventtype}', '{clientid}', '{storeid}', '{timestamp}'),
			array($eventType, $coreConfig->clientId, $coreConfig->storeId, $timestamp),
			$coreConfig->feedAckFilenamePattern
		);
		return $filename;
	}
	/**
	 * Acknoweldge the XML Feed file at xmlToAckPath.
	 * @param xmlToAckPath path to a feed we want to acknowledge
	 * @return self
	 */
	protected function _acknowledgeReceipt($xmlToAckPath)
	{
		$coreHelper  = Mage::helper('eb2ccore');
		$feedHelper  = Mage::helper('eb2ccore/feed');
		$xmlToAckDom = $coreHelper->getNewDomDocument(); // The file I am acknowledging
		$ackDom      = $coreHelper->getNewDomDocument(); // The acknowledgement file itself

		$xmlToAckDom->load($xmlToAckPath);
		$xpath     = new DOMXpath($xmlToAckDom);
		$messageId = $coreHelper->extractQueryNodeValue($xpath, '//MessageHeader/MessageData/MessageId');
		$eventType = $coreHelper->extractQueryNodeValue($xpath, '//MessageHeader/EventType');

		$ack = $ackDom->addElement('Acknowledgement', null)->firstChild;

		$configMap           = $feedHelper->getHeaderConfig($eventType);
		$headerTemplate      = $this->_getCoreConfig()->feedHeaderTemplate;
		$messageHeaderString = str_replace(
			array_map(function ($key) { return "{{$key}}"; }, array_keys($configMap)),
			array_values($configMap),
			$headerTemplate
		);

		$messageHeaderDom = $coreHelper->getNewDomDocument();
		$messageHeaderDom->loadXml($messageHeaderString);
		$messageHeaderNode = $messageHeaderDom->getElementsByTagName('MessageHeader')->item(0);

		$messageHeaderNode = $ackDom->importNode($messageHeaderNode, true);
		$ack->appendChild($messageHeaderNode);
		$ack->createChild('FileName', basename($xmlToAckPath));
		$ack->createChild('ReceivedDateAndTime', date('c'));
		$ack->createChild('ReferenceMessageId', $messageId);

		Mage::getModel('eb2ccore/api')->schemaValidate($ackDom, $this->_getCoreConfig()->feedAckXsd);
		$basename = $this->_getBaseAckFileName($eventType);
		$localPath = $this->getOutboundPath() . DS . $basename;
		$ackDom->save($localPath);
		$remotePath = $this->_getCoreConfig()->feedAckRemotePath . DS . $basename;
		$this->_remoteCall(
			array(Mage::helper('filetransfer'), 'sendFile'),
			array(
				$localPath,
				$remotePath,
				$feedHelper::FILETRANSFER_CONFIG_PATH
			)
		);
		$this->mvToArchiveDir($localPath);
		return $this;
	}
}

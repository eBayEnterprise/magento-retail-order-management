<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * all dirs exists.
 *
 * @method string getFsTool()
 * @method string getLocalDirectory()
 * @method string getSentDirectory()
 * @method string getFeedConfig()
 */
class TrueAction_Eb2cCore_Model_Feed extends Varien_Object
{
	const GLOBAL_PROCESSING_DIR = 'feedProcessingDirectory';
	const GLOBAL_IMPORT_ARCHIVE_DIR = 'feedImportArchive';
	const GLOBAL_EXPORT_ARCHIVE_DIR = 'feedExportArchive';

	protected $_requiredConfigFields = array('local_directory');
	/**
	 * Validate the feed config the instance was set up with. If valid, setup
	 * a new or the injected file system tool (a Varien_Io_File) - allow it to
	 * create folders and open it - and set up any local directoriues.
	 */
	protected function _construct()
	{
		$this->_validateFeedConfig();
		if (!$this->hasFsTool()) {
			$this->setFsTool(new Varien_Io_File());
		}
		$this->getFsTool()->setAllowCreateFolders(true)->open();
		$this->_setUpDirs();
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
	 * Validate the feed config the instance was constructed with.
	 * Must have a 'local_directory' and 'event_type' key/value pairs.
	 * @return self
	 * @throws TrueAction_Eb2cCore_Exception_Feed_File if config is invalid
	 */
	protected function _validateFeedConfig()
	{
		$feedConfig = $this->getFeedConfig();
		if (!is_array($feedConfig)) {
			throw new TrueAction_Eb2cCore_Exception_Feed_File(
				sprintf("%s 'feed_config' must be an array of feed configuration values.", __CLASS__)
			);
		}
		if ($missingConfig = array_diff($this->_requiredConfigFields, array_keys($feedConfig))) {
			throw new TrueAction_Eb2cCore_Exception_Feed_File(
				sprintf("%s missing configuration: '%s'.", __CLASS__, implode("', '", $missingConfig)));
		}
		return $this;
	}
	/**
	 * Return the event type included in the config data.
	 * @return string
	 */
	public function getEventType()
	{
		$feedConfig = $this->getFeedConfig();
		return isset($feedConfig['event_type']) ? $feedConfig['event_type'] : '';
	}
	/**
	 * Ensure the directory at the given path exists or recursively create
	 * necessary directories. If the directory is given a "name", save the path
	 * as magic data with that key.
	 * @param string $path the full path to the directory to set up.
	 * @param string $dirName magic data field to save path to
	 * @return self
	 */
	protected function _setCheckAndCreateDir($path, $dirName=null)
	{
		try {
			$this->getFsTool()->checkAndCreateFolder($path);
		} catch (Exception $e) {
			throw new TrueAction_Eb2cCore_Exception_Feed_File($e->getMessage());
		}
		if ($dirName) {
			$this->setData($dirName, $path);
		}
		return $this;
	}
	/**
	 * Get a "clean" representation of the given file path.
	 * @param string $_,...
	 * @return string
	 */
	protected function _normalPaths($_)
	{
		return $this->getFsTool()->getCleanPath(implode(DS, func_get_args()));
	}
	/**
	 * If a dir config has been set, use it to check for those directories to
	 * exist and create them if they don't. This method may safely be called
	 * multiple times with the same directory configuration.
	 * After ensuring the directories exist, set the path to each as "magic"
	 * data attributes.
	 * @return self
	 */
	protected function _setUpDirs()
	{
		$base = Mage::getBaseDir('var');
		$feedConfig = $this->getFeedConfig();
		// The local directory will always exist on if the instance is valid,
		// checked during instance construction.
		$localDirectory = $this->_normalPaths($base, $feedConfig['local_directory']);
		$this->_setCheckAndCreateDir($localDirectory, 'local_directory');
		// Sent directory may exist as another local directory for exported files
		// to be moved to while awaiting an ack.
		if (isset($feedConfig['sent_directory'])) {
			$sentDirectory = $this->_normalPaths($base, $feedConfig['sent_directory']);
			$this->_setCheckAndCreateDir($sentDirectory, 'sent_directory');
		}
		return $this;
	}
	/**
	 * Return an array of files matching the given shell glob
	 * @param  string $pattern shell glob
	 * @return attay
	 * @codeCoverageIgnore Dependency on the file system makes this trivial code non-trivial to test.
	 */
	protected function _glob($pattern)
	{
		return glob($pattern);
	}
	/**
	 * Get a list files in the local directory, optionally matching a
	 * given pattern. If the pattern is not given, a 'file_pattern' in the feed
	 * config will be used if it exists. If not, a default value of '*' will be
	 * used to list everything in the directory.
	 * @param  string $pattern
	 * @return array
	 */
	public function lsLocalDirectory($pattern=null)
	{
		// If not given a file pattern, attempt to use the one that may have been
		// included with the dir config.
		if (is_null($pattern)) {
			$feedConfig = $this->getFeedConfig();
			$pattern = isset($feedConfig['file_pattern']) ? $feedConfig['file_pattern'] : '*';
		}
		return $this->_glob(rtrim($this->_normalPaths($this->getLocalDirectory(), $pattern), DS));
	}
	/**
	 * mv a source file to a directory, keeping same file name
	 * @param string $srcFile
	 * @param string $targetFile
	 * @return self
	 * @throws TrueAction_Eb2cCore_Exception_Feed_File if file could not be moved
	 */
	protected function _mv($srcFile, $targetFile)
	{
		if (!$this->getFsTool()->mv($srcFile, $targetFile)) {
			throw new TrueAction_Eb2cCore_Exception_Feed_File("Could not move {$srcFile} to {$targetFile}.");
		}
		return $this;
	}
	/**
	 * Move the source file to the local directory, keeping same file name and
	 * returning the new location of the file
	 * @param  string $srcFile Absolute path to file to move
	 * @return string
	 */
	public function mvToLocalDirectory($srcFile)
	{
		$targetFile = $this->_normalPaths($this->getLocalDirectory(), basename($srcFile));
		$this->_mv($srcFile, $targetFile);
		return $targetFile;
	}
	/**
	 * Move the source file to the sent directory if one has been configured,
	 * keeping same file name and returning the new location of the file.
	 * @param  string $srcFile
	 * @return string
	 * @throws TrueAction_Eb2cCore_Exception_Feed_File if no sent file has been configured
	 */
	public function mvToSentDirectory($srcFile)
	{
		if (!$this->getSentDirectory()) {
			throw new TrueAction_Eb2cCore_Exception_Feed_File('No sent directory configured');
		}
		$targetFile = $this->_normalPaths($this->getSentDirectory(), basename($srcFile));
		$this->_mv($srcFile, $targetFile);
		return $targetFile;
	}
	/**
	 * Move the source file to one of the globally configured directories, e.g.
	 * import archive, export archive, processing. The relative path to the
	 * directory should be retrievable via that given config key. Should return
	 * the new location of the file.
	 * @param  string $srcFile
	 * @param  string $feedConfigKey Known config registry key
	 * @return string
	 */
	private function _mvToGlobalDirectory($srcFile, $feedConfigKey)
	{
		$targetFile = $this->_normalPaths(
			Mage::getBaseDir('var'),
			$this->_getCoreConfig()->$feedConfigKey,
			basename($srcFile)
		);
		$this->_setCheckAndCreateDir(dirname($targetFile));
		$this->_mv($srcFile, $targetFile);
		return $targetFile;
	}
	/**
	 * Move the source file to the configured processing directory and return
	 * the new location of the file.
	 * @param  string $srcFile
	 * @return string
	 */
	public function mvToProcessingDirectory($srcFile)
	{
		return $this->_mvToGlobalDirectory($srcFile, self::GLOBAL_PROCESSING_DIR);
	}
	/**
	 * Move the file to the import archive directory, keeping same file name, and
	 * return the new location of the file.
	 * @param  string $srcFile Absolute path to the file to move
	 * @return string
	 */
	public function mvToImportArchive($srcFile)
	{
		return $this->_mvToGlobalDirectory($srcFile, self::GLOBAL_IMPORT_ARCHIVE_DIR);
	}
	/**
	 * Move the fiel to the export archive directory, keeping same file name, and
	 * return the new location of the file.
	 * @param  string $srcFile Absolute path to the file to move.
	 * @return string
	 */
	public function mvToExportArchive($srcFile)
	{
		return $this->_mvToGlobalDirectory($srcFile, self::GLOBAL_EXPORT_ARCHIVE_DIR);
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
			$coreConfig->feedAckFilenameFormat
		);
		return $filename;
	}
	/**
	 * Acknoweldge the XML Feed file at xmlToAckPath.
	 * @param xmlToAckPath path to a feed we want to acknowledge
	 * @return self
	 */
	public function acknowledgeReceipt($xmlToAckPath)
	{
		$cfg = $this->_getCoreConfig();
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
		$headerTemplate      = $cfg->feedHeaderTemplate;
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

		Mage::getModel('eb2ccore/api')->schemaValidate($ackDom, $cfg->feedAckXsd);
		$basename = $this->_getBaseAckFileName($eventType);
		$localPath = $this->_normalPaths(
			Mage::getBaseDir('var'), $cfg->feedAckOutbox, $basename
		);
		$this->_setCheckAndCreateDir(dirname($localPath));
		$ackDom->save($localPath);
		return $this;
	}
}

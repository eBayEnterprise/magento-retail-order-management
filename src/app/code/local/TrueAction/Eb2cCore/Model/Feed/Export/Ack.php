<?php
/**
 * This class will implement the functionality to confirm that feed files that were exported such as PIM, and Image
 * had their their acknowledgment files imported and if a known exported file has no firm imported acknowledgment file
 * after a configurable elapse time it will move the exported file to the out-box to be exported.
 * when an exported file have a match acknowledgment file this exported file will be move to export_archive folder
 */
class TrueAction_Eb2cCore_Model_Feed_Export_Ack
{
	const CFG_EXPORT_ARCHIVE = 'export_archive';
	const CFG_IMPORT_ARCHIVE = 'import_archive';
	const CFG_EXPORT_OUTBOX = 'export_outbox';

	const CFG_IMPORTED_ACK_DIR = 'imported_ack_dir';
	const CFG_EXPORTED_FEED_DIR = 'exported_feed_dir';

	const CFG_WAIT_TIME_LIMIT = 'waiting_time_limit';

	const XPATH_ACK_EXPORTED_FILE = 'FileName';

	const SCOPE_VAR = 'var';
	const FILE_EXTENSION = '*.xml';

	const ACK_KEY = 'ack';
	const RELATED_KEY = 'related';

	/**
	 * @var array hold key config value map
	 */
	protected $_configMap = array();

	/**
	 * cache the self::_configMap known class constant and map them
	 * to their configuration value
	 * @param string $cfgKey the key of the configMap array
	 * @return string | null a string value if the key is in the configMap otherwise null
	 */
	protected function _getConfigMapValue($cfgKey)
	{
		if (empty($this->_configMap)) {
			$cfg = Mage::getModel('eb2ccore/config_registry')
				->addConfigModel(Mage::getSingleton('eb2ccore/config'));

			$this->_configMap = array(
				self::CFG_EXPORT_ARCHIVE => $cfg->feedExportArchive,
				self::CFG_IMPORT_ARCHIVE => $cfg->feedImportArchive,
				self::CFG_EXPORT_OUTBOX => $cfg->feedOutboxDirectory,
				self::CFG_IMPORTED_ACK_DIR => $cfg->feedAckInbox,
				self::CFG_EXPORTED_FEED_DIR => $cfg->feedSentDirectory,
				self::CFG_WAIT_TIME_LIMIT => $cfg->exportResendTimeLimit,
			);
		}
		return isset($this->_configMap[$cfgKey])? $this->_configMap[$cfgKey] : null;
	}

	/**
	 * given a configuration key get all feed files
	 * @param string $cfgKey the configuration key map to some configuration value
	 * @return array
	 */
	protected function _listFilesByCfgKey($cfgKey)
	{
		return $this->_listFiles($this->_buildPath($cfgKey));
	}

	/**
	 * given an imported acknowledgment file extract its related exported file
	 * @param string $ackFile
	 */
	protected function _extractExportedFile($ackFile)
	{
		$exportedDir = $this->_buildPath(self::CFG_EXPORTED_FEED_DIR);
		return $this->_extractAckExportedFile($ackFile, $exportedDir);
	}

	/**
	 * use the configuration to determine where to find acknowledgment files
	 * loop through all the acknowledgment files extract their related exported files
	 * and add that to an array index as an array of key acknowledgment  mapping to the imported acknowledgment file
	 * and another key 'export' mapping to the extracted related exported file
	 * @return array
	 */
	protected function _getImportedAckFiles()
	{
		$imports = $this->_listFilesByCfgKey(self::CFG_IMPORTED_ACK_DIR);
		return !empty($imports)? array_map(array($this, '_extractExportedFile'), $imports): array();
	}

	/**
	 * given an acknowledgment feed file, load into a DOMDocument object
	 * attach it into a DOMXPath object and then query it using a constant
	 * that hold XPath for extracting the related exported file
	 * and then return an array of key acknowledgment map to the given acknowledgment file
	 * and a 'related' key mapped to the extracted exported file in the acknowledgment file
	 * @param string $ackFile the full path to the acknowledgment to extract the exported file related to it
	 * @param string $exportedDir the directory to where exported sent file exists
	 * @return array
	 */
	protected function _extractAckExportedFile($ackFile, $exportedDir)
	{
		$helper = Mage::helper('eb2ccore');
		$doc = $helper->getNewDomDocument();
		$doc->load($ackFile);
		$xpath = $helper->getNewDOMXPath($doc);
		return array(
			self::ACK_KEY => $ackFile,
			self::RELATED_KEY => $exportedDir . DS . $helper->extractNodeVal($xpath->query(
				self::XPATH_ACK_EXPORTED_FILE, $helper->getDomElement($doc)
			))
		);
	}

	/**
	 * given a file that was exported and a list of acknowledgment that was imported
	 * find which acknowledgment file that's acknowledging the exported file, return
	 * acknowledgment file when a match is found otherwise null
	 * @param string $exportedFile the exported file
	 * @param array $importedAck a list of imported acknowledgment files
	 * @return string | null the acknowledgment file when match otherwise null
	 */
	protected function _getAck($exportedFile, array $importedAck=array())
	{
		foreach ($importedAck as $ack) {
			if (basename($exportedFile) === basename($ack[self::RELATED_KEY])) {
				return $ack[self::ACK_KEY];
			}
		}
		return null;
	}

	/**
	 * given a sourceFile and a self::_configMap give move the file
	 * to any destination the key is map to after successful file move
	 * try removing the source file
	 * @param string $sourceFile
	 * @param string $cfgKey
	 * @return self
	 */
	protected function _mvTo($sourceFile, $cfgKey)
	{
		$helper = Mage::helper('eb2ccore');
		$destination = $this->_buildPath($cfgKey) . DS . basename($sourceFile);
		$isDeletable = true;

		try{
			$helper->moveFile($sourceFile, $destination);
			Mage::log(sprintf('[%s] Moving file %s to %s', __CLASS__, $sourceFile, $destination), Zend_Log::DEBUG);
		} catch (TrueAction_Eb2cCore_Exception_Feed_File $e) {
			$isDeletable = false;
			Mage::log(sprintf('[%s] moving file cause this exception: %s', __CLASS__, $e->getMessage()), Zend_Log::WARN);
		}

		if ($isDeletable) {
			try{
				$helper->removeFile($sourceFile);
			} catch (TrueAction_Eb2cCore_Exception_Feed_File $e) {
				Mage::log(sprintf('[%s] removing file cause this exception: %s', __CLASS__, $e->getMessage()), Zend_Log::WARN);
			}
		}

		return $this;
	}

	/**
	 * given a configuration key build and return the absolute path
	 * @param string $cfgKey the configuration key
	 * @return string
	 */
	protected function _buildPath($cfgKey)
	{
		return Mage::helper('eb2ccore')->getAbsolutePath(
			$this->_getConfigMapValue($cfgKey),
			self::SCOPE_VAR
		);
	}

	/**
	 * given an exported file that has no imported acknowledgment file
	 * check if the time the file was exported exceed the configured waiting time
	 * then return true to indicate the file need to be resend otherwise return false to keep waiting
	 * @param string $exportedFile the exported file that don't currently have an imported acknowledgment file
	 * @return bool true exported file exceed the configured waiting time otherwise false
	 */
	protected function _isResendable($exportedFile)
	{
		return (
			Mage::helper('eb2ccore')->getFileTimeElapse($exportedFile) >
			(int) $this->_getConfigMapValue(self::CFG_WAIT_TIME_LIMIT)
		);
	}

	/**
	 * given a file directory pattern return an array of files in the directory that matches some pattern
	 * FYI: ignoring coverage for this method because PHP glob is untestable
	 * @param string $directory
	 * @return array
	 * @codeCoverageIgnore
	 */
	protected function _listFiles($directory)
	{
		return glob($directory . DS . self::FILE_EXTENSION);
	}

	/**
	 * get all exported files and a list of acknowledgment files imported
	 * loop through all the exported files and check if each exported files has an imported acknowledgment file
	 * in the list of acknowledgment files, if the file is in the list of
	 * acknowledgment file, then simply move the exported to export_archive and the acknowledgment file to import_archive
	 * otherwise the exported file has no acknowledgment therefore, check the created time of
	 * exported file if is greater than the configurable elapse time simply move it back to
	 * out-box to be exported again, however if the elapse time is less than the configurable
	 * simply ignore the file
	 * @return self
	 */
	public function process()
	{
		$exportedList = $this->_listFilesByCfgKey(self::CFG_EXPORTED_FEED_DIR);
		if (!empty($exportedList)) {
			$importedList = $this->_getImportedAckFiles();
			foreach ($exportedList as $exported) {
				$ack = $this->_getAck($exported, $importedList);
				if (!is_null($ack)) {
					$this->_mvTo($exported, self::CFG_EXPORT_ARCHIVE)
						->_mvTo($ack, self::CFG_IMPORT_ARCHIVE);
				} elseif ($this->_isResendable($exported)) {
					$this->_mvTo($exported, self::CFG_EXPORT_OUTBOX);
				}
			}
		}

		return $this;
	}
}

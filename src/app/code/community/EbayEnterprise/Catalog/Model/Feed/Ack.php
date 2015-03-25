<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class will implement the functionality to confirm that feed files that were exported such as PIM, and Image
 * had their their acknowledgment files imported and if a known exported file has no firm imported acknowledgment file
 * after a configurable elapse time it will move the exported file to the out-box to be exported.
 * when an exported file have a match acknowledgment file this exported file will be move to export_archive folder
 */
class EbayEnterprise_Catalog_Model_Feed_Ack
{
	const CFG_EXPORT_ARCHIVE = 'export_archive';
	const CFG_IMPORT_ARCHIVE = 'import_archive';
	const CFG_EXPORT_OUTBOX = 'export_outbox';
	const CFG_ERROR_DIRECTORY = 'error_archive';

	const CFG_IMPORTED_ACK_DIR = 'imported_ack_dir';
	const CFG_EXPORTED_FEED_DIR = 'exported_feed_dir';

	const CFG_WAIT_TIME_LIMIT = 'waiting_time_limit';

	const XPATH_ACK_EXPORTED_FILE = 'FileName';

	const SCOPE_VAR = 'var';
	const FILE_EXTENSION = '*.xml';

	const ACK_KEY = 'ack';
	const RELATED_KEY = 'related';

	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var array hold key config value map */
	protected $_configMap = [];

	public function __construct($args = [])
	{
		list(
			$this->_logger,
			$this->_coreHelper
		) = $this->_checkTypes(
			$this->_nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore'))
		);
	}

	/**
	 * enforce injected types
	 * @param  EbayEnterprise_MageLog_Helper_Data
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	) {
		return [$logger, $coreHelper];
	}

	protected function _nullCoalesce($key, array $arr, $default=null)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * cache the self::_configMap known class constant and map them
	 * to their configuration value
	 * @param string $cfgKey the key of the configMap array
	 * @return string | null a string value if the key is in the configMap otherwise null
	 */
	protected function _getConfigMapValue($cfgKey)
	{
		if (empty($this->_configMap)) {
			$cfg = $this->_coreHelper->getConfigModel();

			$this->_configMap = [
				self::CFG_EXPORT_ARCHIVE => $cfg->feedExportArchive,
				self::CFG_IMPORT_ARCHIVE => $cfg->feedImportArchive,
				self::CFG_EXPORT_OUTBOX => $cfg->feedOutboxDirectory,
				self::CFG_IMPORTED_ACK_DIR => $cfg->feedAckInbox,
				self::CFG_EXPORTED_FEED_DIR => $cfg->feedSentDirectory,
				self::CFG_WAIT_TIME_LIMIT => $cfg->ackResendTimeLimit,
				self::CFG_ERROR_DIRECTORY => $cfg->feedAckErrorDirectory,
			];
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
	 *
	 * @param string $ackFile
	 * @return array
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
		return !empty($imports)? array_map([$this, '_extractExportedFile'], $imports): [];
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
		$doc = $this->_coreHelper->getNewDomDocument();
		$doc->load($ackFile);
		$xpath = $this->_coreHelper->getNewDOMXPath($doc);
		return [
			self::ACK_KEY => $ackFile,
			self::RELATED_KEY => $exportedDir . DS . $this->_coreHelper->extractNodeVal($xpath->query(
				self::XPATH_ACK_EXPORTED_FILE, $this->_coreHelper->getDomElement($doc)
			))
		];
	}

	/**
	 * given a file that was exported and a list of acknowledgment that was imported
	 * find which acknowledgment file that's acknowledging the exported file, return
	 * acknowledgment file when a match is found otherwise null
	 * @param string $exportedFile the exported file
	 * @param array $importedAck a list of imported acknowledgment files
	 * @return string | null the acknowledgment file when match otherwise null
	 */
	protected function _getAck($exportedFile, array $importedAck=[])
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
	 * @param string
	 * @param string
	 * @return self
	 */
	protected function _mvTo($sourceFile, $cfgKey)
	{
		$destination = $this->_buildPath($cfgKey) . DS . basename($sourceFile);
		$isDeletable = true;

		try{
			$this->_coreHelper->moveFile($sourceFile, $destination);
		} catch (EbayEnterprise_Catalog_Exception_Feed_File $e) {
			$isDeletable = false;
			$this->_logger->logException($e);
		}

		if ($isDeletable) {
			try{
				$this->_coreHelper->removeFile($sourceFile);
			} catch (EbayEnterprise_Catalog_Exception_Feed_File $e) {
				$this->_logger->logException($e);
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
		return $this->_coreHelper->getAbsolutePath(
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
	protected function _isTimedOut($exportedFile)
	{
		return (
			$this->_coreHelper->getFileTimeElapse($exportedFile) >
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
				} elseif ($this->_isTimedOut($exported)) {
					// create the error directory since it's not automatically created
					// when processing the feeds
					$this->_coreHelper->createDir($this->_buildPath(self::CFG_ERROR_DIRECTORY));
					$this->_mvTo($exported, self::CFG_ERROR_DIRECTORY);
					$this->_logger->logCrit('%s: %s was not acknowledged by Product Hub', [__CLASS__, $exported]);
				}
			}
		}
		return $this;
	}
}

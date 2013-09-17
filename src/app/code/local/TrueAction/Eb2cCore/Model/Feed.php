<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * 	all dirs exists. Intended usage:
 *
 * @method string getArchivePath()
 * @method string getBaseDir()
 * @method string getErrorPath()
 * @method string getFsTool()
 * @method string getInboundPath()
 * @method string getOutboundPath()
 * @method string getTmpPath()
 * @method string setBaseDir(string pathName)
 */
class TrueAction_Eb2cCore_Model_Feed extends Varien_Object
{
	const INBOUND_DIR_NAME  = 'inbound';
	const OUTBOUND_DIR_NAME = 'outbound';
	const ARCHIVE_DIR_NAME  = 'archive';
	const ERROR_DIR_NAME    = 'error';
	const TMP_DIR_NAME      = 'tmp';

	/**
	 * Turn on allow create folders; it's off by default in the base Varien_Io_File. Set up
	 * subdirectories if we're passed a base_dir
	 */
	protected function _construct()
	{
		if (!$this->hasFsTool()) {
			$this->setFsTool(new Varien_Io_File());
		}
		$this->getFsTool()->setAllowCreateFolders(true);
		if ($this->hasBaseDir()) {
			$this->setUpDirs();
		}
	}

	/**
	 * Assigns our folder variable and does the recursive creation
	 *
	 * @param string $path the full path to the directory to set up.
	 * @return boolean
	 */
	private function _setCheckAndCreateDir($path)
	{
		return $this->getFsTool()->checkAndCreateFolder($path);
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
			'error_path'    => $base . DS . self::ERROR_DIR_NAME,
			'tmp_path'      => $base . DS . self::TMP_DIR_NAME,
		));

		$this->_setCheckAndCreateDir($this->getInboundPath());
		$this->_setCheckAndCreateDir($this->getOutboundPath());
		$this->_setCheckAndCreateDir($this->getArchivePath());
		$this->_setCheckAndCreateDir($this->getErrorPath());
		$this->_setCheckAndCreateDir($this->getTmpPath());
	}

	/** 
     * fetchs feeds from remote, places them into inBoundPath
	 *
	 */
	public function fetchFeedsFromRemote($remotePath)
	{
		$attempts   = 0;
		$cfg        = Mage::helper('eb2ccore/feed');
		$coreConfig = Mage::getModel('eb2ccore/config_registry')
			->setStore(null)
			->addConfigModel(Mage::getModel('eb2ccore/config'));

		do {
			try {
				$attempts++;
				Mage::helper('filetransfer')->getFile(
					$this->getInboundPath(),
					$remotePath,
					$cfg::FILETRANSFER_CONFIG_PATH
				);
				break;
			} catch( TrueAction_FileTransfer_Exception_Connection $e ) {
				// Connection exceptions we'll retry, could be a temporary condition
				Mage::logException($e);
				if( $attempts >= $coreConfig->feedFetchConnectAttempts ) {
					Mage::log('Connect failed, retry limit reached', Zend_Log::ERR);
					break;
				}
				else {
					Mage::log(
						sprintf('Connect failed, sleeping %d seconds (attempt %d of %d)',
						$coreConfig->feedFetchRetryTimer, $attempts, $coreConfig->feedFetchConnectAttempts),
						Zend_Log::DEBUG
					);
					sleep($coreConfig->feedFetchRetryTimer);
				}
			} catch (Exception $e ) {
				// Any other exception is failure, log and return
				Mage::logException($e);
				break;
			}
		} while(true);
		return;
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
	private function _mvToDir($srcFile, $targetDir)
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
	 * mv file to Error Dir
	 *
	 * @param string $filePath to move
	 * @return boolean
	 */
	public function mvToErrorDir($filePath)
	{
		return $this->_mvToDir($filePath, $this->getErrorPath());
	}

	/**
	 * mv file to Tmp Dir
	 *
	 * @param string $filePath to move
	 * @return boolean
	 */
	public function mvToTmpDir($filePath)
	{
		return $this->_mvToDir($filePath, $this->getTmpPath());
	}
}

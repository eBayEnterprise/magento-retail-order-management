<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * 	all dirs exists. Intended usage:
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
	 * Wrapper function/scaffolding for calls that involve remote connections and
	 * should be retried. Will retry up to a configured number of times. Meant to be used
	 * with TrueAction/FileTransfer's helper methods.
	 *
	 * @param  callable $callable Callback to be tried each time.
	 * @param  array    $argArray Arguments that should be passed to the callable.
	 * @return void
	 */
	private function _remoteCall($callable, $argArray)
	{
		$connectionAttempts = 0;
		$cfg                = Mage::helper('eb2ccore/feed');
		$coreConfig         = Mage::getModel('eb2ccore/config_registry')
			->setStore(null)
			->addConfigModel(Mage::getModel('eb2ccore/config'));

		do {
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
		} while(true);
		return;
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
	}

	/**
	 * Remote the file from the remote.
	 * @param  string $remotePath Directory the file resides in.
	 * @param  string $fileName   Name of the file to remove
	 * @return void
	 */
	public function removeFromRemote($remotePath, $fileName)
	{
		$this->_remoteCall(
			array(Mage::helper('filetransfer'), 'deleteFile'),
			array($remotePath, $fileName)
		);
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
}

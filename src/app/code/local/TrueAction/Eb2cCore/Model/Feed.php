<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * 	all dirs exists. Intended usage:
 *
 * $feed = Mage::getModel('eb2corder/feed');
 * $feed->setBaseDir('/path/to/your/config/base/dir');	// intended to come from your config
 * $fileTransferRecevier($feed->getInboundDir(), $remoteLocation); // Run your file receiver 'into' getInboundDir()
 * foreach( $feed->lsInboundDir() as $file ) {
 *		// Do feed things ...
 * 		if( ok ) {
 *			$feed->mvToArchiveDir($file);
 *		} else {
 *			$feed->mvToErrorDir($file);
 * 		}
 * }
 *
 */
class TrueAction_Eb2cCore_Model_Feed extends Mage_Core_Model_Abstract
{
	const INBOUND_DIR_NAME  = 'inbound';
	const OUTBOUND_DIR_NAME = 'outbound';
	const ARCHIVE_DIR_NAME  = 'archive';
	const ERROR_DIR_NAME    = 'error';
	const TMP_DIR_NAME      = 'tmp';

	/**
	 * Turn on allow create folders; it's off by default in the base Varien_Io_File
	 */
	public function _construct()
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
	 * @param string $path the full path to the directory to set up.
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
			// @fixme This could be written better
			Mage::throwException('Need to set up base directory before calling this.');
		}
		return $this->addData(array(
			'inbound_path'  => $this->_setCheckAndCreateDir($base . DS . self::INBOUND_DIR_NAME),
			'outbound_path' => $this->_setCheckAndCreateDir($base . DS . self::OUTBOUND_DIR_NAME),
			'archive_path'  => $this->_setCheckAndCreateDir($base . DS . self::ARCHIVE_DIR_NAME),
			'error_path'    => $this->_setCheckAndCreateDir($base . DS . self::ERROR_DIR_NAME),
			'tmp_path'      => $this->_setCheckAndCreateDir($base . DS . self::INBOUND_DIR_NAME),
		));
	}

	/**
	 * Lists contents of the Inbound Dir
	 */
	public function lsInboundDir($filetype='xml')
	{
		$dirContents = array();

		$this->getFsTool()->cd($this->_inboundDir);
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
	 */
	private function _mvToDir($srcFile, $targetDir)
	{
		$dest = $targetDir . DS . basename($srcFile);
		$this->getFsTool()->mv($srcFile, $dest);
	}

	/**
	 * mv file to Inbound Dir
	 */
	public function mvToInboundDir($filePath) {
		return $this->_mvToDir($filePath, $this->_inboundDir);
	}

	/**
	 * mv file to Outbound Dir
	 */
	public function mvToOutboundDir($filePath) {
		return $this->_mvToDir($filePath, $this->_outboundDir);
	}

	/**
	 * mv file to Archive Dir
	 */
	public function mvToArchiveDir($filePath) {
		return $this->_mvToDir($filePath, $this->_archiveDir);
	}

	/**
	 * mv file to Error Dir
	 */
	public function mvToErrorDir($filePath) {
		return $this->_mvToDir($filePath, $this->_errorDir);
	}

	/**
	 * mv file to Tmp Dir
	 */
	public function mvToTmpDir($filePath) {
		return $this->_mvToDir($filePath, $this->_tmpDir);
	}
}

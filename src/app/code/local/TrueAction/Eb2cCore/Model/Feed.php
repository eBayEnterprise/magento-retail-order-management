<?php
/**
 * This class is intended to simplify file movements during feed processing, and make sure
 * 	all folders exists. Intended usage:
 *
 * $feed = Mage::getModel('eb2corder/feed');
 * $feed->setBaseFolder('/path/to/your/config/base/folder');	// intended to come from your config
 * $fileTransferRecevier($feed->getInboundFolder(), $remoteLocation); // Run your file receiver 'into' getInboundFolder()
 * foreach( $feed->lsInboundFolder() as $file ) {
 *		// Do feed things ...
 * 		if( ok ) {
 *			$feed->mvToArchiveFolder($file);
 *		}
 *		else {
 *			$feed->mvToErrorFolder($file);
 * 		}
 * }
 *
 */
class TrueAction_Eb2cCore_Model_Feed extends Varien_Io_File
{
	const INBOUND_FOLDER_NAME 	=	'inbound';
	const OUTBOUND_FOLDER_NAME	=	'outbound';
	const ARCHIVE_FOLDER_NAME	=	'archive';
	const ERROR_FOLDER_NAME		=	'error';
	const TMP_FOLDER_NAME		=	'tmp';

	private $_baseFolder;
	private $_inboundFolder;
	private $_outboundFolder;
	private $_archiveFolder;
	private $_errorFolder;
	private $_tmpFolder;

	/**
	 * Turn on allow create folders; it's off by default in the base Varien_Io_File
	 */
	public function __construct()
	{
		parent::setAllowCreateFolders(true);
	}

	/**
	 * Assigns our folder variable and does the recursive creation
	 * 
	 */
	private function _setCheckAndCreateFolder(&$folderName, $folderPath)
	{
		$folderName = $folderPath;
		return $this->checkAndCreateFolder($folderName);
	}

	/**
	 * For feeds, just configure a base folder, and you'll get the rest.
	 */
	public function setBaseFolder($userFolder)
	{
		$this->_setCheckAndCreateFolder($this->_baseFolder,		$userFolder);	// $userFolder should come from module's config
		$this->_setCheckAndCreateFolder($this->_inboundFolder,	$this->_baseFolder . $this->dirsep() . self::INBOUND_FOLDER_NAME);
		$this->_setCheckAndCreateFolder($this->_outboundFolder,	$this->_baseFolder . $this->dirsep() . self::OUTBOUND_FOLDER_NAME);
		$this->_setCheckAndCreateFolder($this->_archiveFolder,	$this->_baseFolder . $this->dirsep() . self::ARCHIVE_FOLDER_NAME);
		$this->_setCheckAndCreateFolder($this->_errorFolder,	$this->_baseFolder . $this->dirsep() . self::ERROR_FOLDER_NAME);
		$this->_setCheckAndCreateFolder($this->_tmpFolder,		$this->_baseFolder . $this->dirsep() . self::TMP_FOLDER_NAME);
		$this->cd($this->_baseFolder);
	}

	/**
	 * Lists contents of the Inbound Folder
	 */
	public function lsInboundFolder()
	{
		$dirContents = array();

		$this->cd($this->_inboundFolder);
		foreach( $this->ls() as $file ) {
			$dirContents[] = $this->_cwd . $this->dirsep() . $file['text'];
		}
		return $dirContents;
	}

	/**
	 * Get the full path to the inbound folder
	 */
	public function getInboundFolder()
	{
		return $this->_inboundFolder;
	}

	/**
	 * Get the full path to the Outbound folder
	 */
	public function getOutboundFolder()
	{
		return $this->_outboundFolder;
	}

	/**
	 * Get the full path to the Archive folder
	 */
	public function getArchiveFolder()
	{
		return $this->_archiveFolder;
	}

	/**
	 * Get the full path to the Error folder
	 */
	public function getErrorFolder()
	{
		return $this->_errorFolder;
	}

	/**
	 * Get the full path to the tmp folder
	 */
	public function getTmpFolder()
	{
		return $this->_tmpFolder;
	}

	/**
	 * mv a source file to a folder
	 */
	private function _mvToFolder($srcFile,$targetDir)
	{
		$dest = $targetDir . $this->dirsep() . basename($srcFile);
		return @rename($srcFile,$dest);
	}

	/**
	 * mv file to Inbound Folder
	 */
	public function mvToInboundFolder($filePath) {
		return $this->_mvToFolder($filePath, $this->_inboundFolder);
	}

	/**
	 * mv file to Outbound Folder
	 */
	public function mvToOutboundFolder($filePath) {
		return $this->_mvToFolder($filePath, $this->_outboundFolder);
	}

	/**
	 * mv file to Archive Folder
	 */
	public function mvToArchiveFolder($filePath) {
		return $this->_mvToFolder($filePath, $this->_archiveFolder);
	}

	/**
	 * mv file to Error Folder
	 */
	public function mvToErrorFolder($filePath) {
		return $this->_mvToFolder($filePath, $this->_errorFolder);
	}

	/**
	 * mv file to Tmp Folder
	 */
	public function mvToTmpFolder($filePath) {
		return $this->_mvToFolder($filePath, $this->_tmpFolder);
	}
}

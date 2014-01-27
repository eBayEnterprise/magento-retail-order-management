<?php

abstract class TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	/**
	 * Shell glob pattern expected to match the filename of the feed file
	 * @var string
	 */
	protected $_feedFilePattern;
	/**
	 * File path to the feed file on the remote sftp server
	 * @var string
	 */
	protected $_feedRemotePath;
	/**
	 * File path to the feed file on the local file system
	 * @var string
	 */
	protected $_feedLocalPath;
	/**
	 * Config registry with product configuration loaded
	 * @var TrueAction_Eb2cCore_Model_Config_Registry
	 */
	protected $_config;
	/**
	 * Setup a config registry with the product configuration loaded in.
	 */
	public function __construct()
	{
		$this->_config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2cproduct/config'))
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}
	/**
	 * @see self::_feedFilePattern
	 */
	public function getFeedFilePattern()
	{
		return $this->_feedFilePattern;
	}
	/**
	 * @see self::_feedRemotePath
	 */
	public function getFeedRemotePath()
	{
		return $this->_feedRemotePath;
	}
	/**
	 * @see self::_feedLocalPath
	 */
	public function getFeedLocalPath()
	{
		return $this->_feedLocalPath;
	}
}

<?php

class TrueAction_Eb2cProduct_Model_Feed_Iship
	extends TrueAction_Eb2cProduct_Model_Feed_Item
{
	public function __construct()
	{
		parent::__construct();
		$this->_feedLocalPath = $this->_config->iShipFeedLocalPath;
		$this->_feedRemotePath = $this->_config->iShipFeedRemoteReceivedPath;
		$this->_feedFilePattern = $this->_config->iShipFeedFilePattern;
	}
}

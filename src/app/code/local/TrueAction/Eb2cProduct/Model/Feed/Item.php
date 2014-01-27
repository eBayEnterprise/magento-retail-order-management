<?php

class TrueAction_Eb2cProduct_Model_Feed_Item
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->_feedLocalPath = $this->_config->itemFeedLocalPath;
		$this->_feedRemotePath = $this->_config->itemFeedRemoteReceivedPath;
		$this->_feedFilePattern = $this->_config->itemFeedFilePattern;
	}
}

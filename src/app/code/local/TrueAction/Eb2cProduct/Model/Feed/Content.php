<?php

class TrueAction_Eb2cProduct_Model_Feed_Content
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->_feedLocalPath = $this->_config->contentFeedLocalPath;
		$this->_feedRemotePath = $this->_config->contentFeedRemoteReceivedPath;
		$this->_feedFilePattern = $this->_config->contentFeedFilePattern;
	}
}

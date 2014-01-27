<?php

class TrueAction_Eb2cProduct_Model_Feed_Pricing
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->_feedLocalPath = $this->_config->pricingFeedLocalPath;
		$this->_feedRemotePath = str_replace('{storeid}', $this->_config->storeId, $this->_config->pricingFeedRemoteReceivedPath);
		$this->_feedFilePattern = $this->_config->pricingFeedFilePattern;
	}
}

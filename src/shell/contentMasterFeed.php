<?php
require_once 'abstract.php';

/**
 * Magento Eb2cProduct Content Master Shell Script
 *
 */
class Mage_Shell_Content_Master_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct Content Master Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cProduct Content Master Feed from shell script.', Zend_Log::DEBUG);
		$contentMaster = Mage::getModel('eb2cproduct/feed_content_master');
		$contentMaster->processFeeds();
		Mage::log('Ending Eb2cProduct Content Master Feed from shell script', Zend_Log::DEBUG);
	}

	/**
	 * Retrieve Usage Help Message
	 *
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE
Usage:  php -f $scriptName -- [options]
  help          This help

USAGE;
	}
}

$shell = new Mage_Shell_Content_Master_Feed();
$shell->run();

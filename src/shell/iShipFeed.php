<?php
require_once 'abstract.php';

/**
 * Magento Eb2cProduct iShip Shell Script
 *
 */
class Mage_Shell_I_Ship_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct iShip Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cProduct iShip Feed from shell script.', Zend_Log::DEBUG);
		$iShip = Mage::getModel('eb2cproduct/feed_i_ship');
		$iShip->processFeeds();
		Mage::log('Ending Eb2cProduct iShip Feed from shell script', Zend_Log::DEBUG);
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

$shell = new Mage_Shell_I_Ship_Feed();
$shell->run();

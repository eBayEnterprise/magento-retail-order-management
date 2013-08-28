<?php
require_once 'abstract.php';

/**
 * Magento Eb2cInventory Item Inventories Shell Script
 *
 */
class Mage_Shell_Item_Inventories_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cInventory Item Inventories Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cInventory Item Inventories Feed from shell script.', Zend_Log::DEBUG);
		$itemInventories = Mage::getModel('eb2cinventory/feed_item_Inventories');
		$itemInventories->processFeeds();
		Mage::log('Ending Eb2cInventory Item Inventories Feed from shell script', Zend_Log::DEBUG);
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

$shell = new Mage_Shell_Item_Inventories_Feed();
$shell->run();

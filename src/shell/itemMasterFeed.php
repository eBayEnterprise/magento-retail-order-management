<?php
require_once 'abstract.php';

/**
 * Magento Eb2cProduct Item Master Shell Script
 *
 */
class Mage_Shell_Item_Master_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct Item Master Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cProduct Item Master Feed from shell script.', Zend_Log::DEBUG);
		$itemMaster = Mage::getModel('eb2cproduct/feed_item_master');
		Mage::log("Eb2cProduct Item Master Feed Shell Script Run Ruselt:\n\r" . $itemMaster->processFeeds(), Zend_Log::DEBUG);
		Mage::log('Ending Eb2cProduct Item Master Feed from shell script', Zend_Log::DEBUG);
	}
}

$shell = new Mage_Shell_Item_Master_Feed();
$shell->run();

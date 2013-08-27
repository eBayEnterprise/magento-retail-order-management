<?php
require_once 'abstract.php';

/**
 * Magento Log Shell Script
 *
 * @category    Eb2cProduct
 * @package     Mage_Shell
 * @author      TrueAction
 */
class Mage_Shell_Item_Master_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct Item Master Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Start Eb2cProduct: Item Master Feed.', Zend_Log::DEBUG);
		$itemMaster = Mage::getModel('eb2cproduct/feed_item_master');
		$itemMaster->processFeeds();
		Mage::log('End Eb2cProduct: Item Master Feed.', Zend_Log::DEBUG);
	}
}

$shell = new Mage_Shell_Item_Master_Feed();
$shell->run();

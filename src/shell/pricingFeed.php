<?php
require_once 'abstract.php';

/**
 * Magento Eb2cProduct Item Pricing Shell Script
 *
 */
class Mage_Shell_Item_Pricing_Feed extends Mage_Shell_Abstract
{
	/**
	 * Running Eb2cProduct Item Pricing Feed Script
	 *
	 */
	public function run()
	{
		Mage::log('Starting Eb2cProduct Item Pricing Feed.', Zend_Log::DEBUG);
		$itemPricing = Mage::getModel('eb2cproduct/feed_item_pricing');
		$itemPricing->processFeeds();
		Mage::log('Ending Eb2cProduct Item Pricing Feed', Zend_Log::DEBUG);
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

$shell = new Mage_Shell_Item_Pricing_Feed();
$shell->run();

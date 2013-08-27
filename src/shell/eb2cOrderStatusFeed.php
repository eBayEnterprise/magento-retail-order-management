<?php
require_once('abstract.php');
/**
 * Drives the Eb2cOrder Status Feed processor from a command line
 */
class TrueAction_Eb2cOrder_Shell_Status_Feed extends Mage_Shell_Abstract
{
	public function _construct()
	{
	}

	/**
	 * Return some help text
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE
Usage: php -f $scriptName -- [options]
	help	This help

USAGE;
	}

	/**
	 * Instantiate and call the _processFeeds Method
	 *
	 */
	public function run()
	{
		$scriptName = basename(__FILE__);
		echo "Starting $scriptName, " . get_class($this) . '::' . __FUNCTION__ . "\n";
		Mage::log( "Starting $scriptName, " . get_class($this) . '::' . __FUNCTION__, Zend_log::DEBUG );
		$filesProcessed = Mage::getModel('eb2corder/status_feed', array('base_dir'=>'/tmp/'.basename(__FILE__,'.php')))->processFeeds();
		echo  "Finished $scriptName, " . get_class($this) . '::' . __FUNCTION__, " $filesProcessed File(s) Processed\n";
		Mage::log( "Finished $scriptName, " . get_class($this) . '::' . __FUNCTION__, " $filesProcessed File(s) Processed", Zend_log::DEBUG );
	}
}
$feedProcessor = new TrueAction_Eb2cOrder_Shell_Status_Feed();
$feedProcessor->run();

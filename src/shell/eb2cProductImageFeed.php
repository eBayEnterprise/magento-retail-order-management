<?php
require_once('abstract.php');
/**
 * Drives the Eb2cProduct Image Feed processor from a command line
 */
class TrueAction_Eb2cProduct_Shell_Image_Feed extends Mage_Shell_Abstract
{
	/**
	 * Log message; if debug mode and stdout is a tty also echo it
	 *
	 * @param string $message to log
	 * @param log level
	 */
	private function _log($message, $level)
	{
		$message = '[' . __CLASS__ . '] ' . basename(__FILE__) . ': ' . $message;
		Mage::log($message, $level);
		if( $level === Zend_Log::DEBUG && posix_isatty(STDOUT)) {
			echo $message . "\n";
		}
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
	 * @todo: This was written prior to Image Feed being built, please come back and check that this works!
	 */
	public function run()
	{
		$this->_log( 'Script started', Zend_log::DEBUG );
		$filesProcessed = Mage::getModel('eb2cproduct/feed_image_master')->processFeeds();
		$this->_log( "Script finished, $filesProcessed file(s) processed.", Zend_log::DEBUG );
	}
}
$feedProcessor = new TrueAction_Eb2cProduct_Shell_Image_Feed();
$feedProcessor->run();

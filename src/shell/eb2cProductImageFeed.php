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
	 * @todo: This doesn't actually do anything as there's no implementation yet. Come back and attach to actual feed processor.
	 * @fixme: This doesn't actually do anything, as above.
	 */
	public function run()
	{
		$this->_log( 'Script started', Zend_log::DEBUG );
		// TODO: OBVIOUSLY this isn't real. Return value depends on image implementation, as yet undefined.
		$filesProcessed = rand(0, 9);
		$this->_log( "Script finished, $filesProcessed file(s) processed.", Zend_log::DEBUG );
	}
}
$feedProcessor = new TrueAction_Eb2cProduct_Shell_Image_Feed();
$feedProcessor->run();

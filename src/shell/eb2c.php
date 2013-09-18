<?php
require_once 'abstract.php';

/**
 * Eb2c Feed Shell
 */
class TrueAction_Eb2c_Shell_Feed extends Mage_Shell_Abstract
{
	private $_feedCore;

	/**
	 * Instantiate the core feed shell
	 *
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_feedCore = Mage::getModel('eb2ccore/feed_shell');
	}

	/**
	 * When passed the '-validate config' options, see that there's a valid model for each feed that's been configured
	 *
	 */
	private function _validateConfig()
	{
		foreach( $this->_feedCore->listAvailableFeeds() as $aFeed ) {
			echo "\t$aFeed " . ($this->_feedCore->getFeedModel($aFeed) ? 'valid' : '*INVALID*') . "\n";
		}
	}

	/**
	 * The 'main' of a Mage Shell Script
	 *
	 * @see usageHelp
	 */
	public function run()
	{
		if( !count($this->_args) ) {
			echo $this->usageHelp();
			return;
		}

		if( $this->getArg('validate') === 'config' ) {
			$this->_validateConfig();
			return;
		}

		$this->_log( 'Script Started', Zend_log::DEBUG );
		$feeds = preg_split('/[\s,]+/', $this->getArg('feeds')); // Split feeds on whitespace
		foreach( $feeds as $feedName ) {
			$this->_log("Feed begins: $feedName", Zend_log::DEBUG);
			try {
				$rc = $this->_feedCore->runFeedModel($feedName);
			} catch(Exception $e) {
				Mage::logException($e);
				$rc = false;
			}
			$this->_log( "Feed ends: $feedName, rc is $rc", Zend_log::DEBUG );
		}
		$this->_log( 'Script Ended', Zend_log::DEBUG );
	}

	/**
	 * Log a message
	 *
	 * @param string $message to log
	 * @param log level
	 */
	private function _log($message, $level)
	{
		$message = '[' . __CLASS__ . '] ' . basename(__FILE__) . ': ' . $message;
		Mage::log($message, $level);
	}

	/**
	 * Return some help text
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		$msg = <<<USAGE

Usage: php -f $scriptName -- [options]
  -feed      list_of_feeds (Watch out for shell escapes)
  -validate  config (Ensures all feeds configured are valid)
  help       This help

Configured and Enabled feeds:

USAGE;
		return $msg . '  ' . implode( "\n  ", $this->_feedCore->listAvailableFeeds()) . "\n";
	}
}

$feedProcessor = new TrueAction_Eb2c_Shell_Feed();
$feedProcessor->run();

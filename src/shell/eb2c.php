<?php
require_once 'abstract.php';

/**
 * Runs the processor.
 *
 */
function main()
{
	$feedProcessor = new TrueAction_Eb2c_Shell_Feed();
	$feedProcessor->run();
}

/**
 * Eb2c Feed Shell
 *
 */
class TrueAction_Eb2c_Shell_Feed extends Mage_Shell_Abstract
{
	const DEFAULT_PROCESS_METHOD = 'processFeeds';

	/**
	 * 'feed model' => 'module'
	 *
	 * @todo: move this to config
	 */
	private $_availableFeeds = array (
		'feed_item_Inventories' => 'eb2cinventory',
		'status_feed'           => 'eb2corder',
		'feed_content_master'   => 'eb2cproduct',
		'feed_image_master'     => 'eb2cproduct',
		'feed_item_iship'       => 'eb2cproduct',
		'feed_item_master'      => 'eb2cproduct',
		'feed_item_pricing'     => 'eb2cproduct',
	);

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
echo $message ."\n";
	}

	/**
	 * Return some help text
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		echo <<<USAGE
Usage: php -f $scriptName -- [options]
	feed1 feed2 [...]	feed to run, processed in order given on command line
	help	This help

Available feeds:

USAGE;
		foreach( $this->_availableFeeds as $feedName => $modelName ) {
			echo "\t$feedName ($modelName)\n";
		}
	}

	/**
	 * Instantiate the model passed on the command line, and run either the default
	 * method ('processFeeds') or what has been passed on the command line (via the
	 * -method switch).
	 *
	 * @see usageHelp
	 */
	public function run()
	{
		$this->_log( 'Script started', Zend_log::DEBUG );

		// 'model' is required; instantiate it. If not passed, or it's invalid, we die with some help text.
		$modelName = $this->getArg('model');
		if( !$modelName ) {
			echo "'model' is required.\n";
			die($this->usageHelp());
		}

		$model = Mage::getModel($modelName);
		if( !$model ) {
			echo "Can't instantiate model '$modelName'. Does it exist? Spelling error perhaps?\n";
			die($this->usageHelp());
		}

		$processMethod = $this->getArg('method');
		if( $processMethod ) {
			// If they've passed us a method to use, make sure it exists.
			if( !method_exists($model, $processMethod) ) {
				echo $modelName . ' (' . get_class($model) . ') does not have a method "' . $processMethod . "\"\n";
				die($this->usageHelp());
			}
		} else {
			// If they haven't specified a method, we set method to the default name. We still have to verify if the method exists.
			$processMethod = self::DEFAULT_PROCESS_METHOD;
			if( !method_exists($model, $processMethod) ) {
				echo $modelName . ' (' . get_class($model) . ') does not have the default method "' . $processMethod . "\"\n";
				die($this->usageHelp());
			}
		}

		// This presumes the process method is return the number of files processed. I can't thing of anything
		// much more meaningful, though of course I have no way to enforce that here.
		$this->_log('Running ' . get_class($model) . '::' . $processMethod, Zend_log::DEBUG);
		$filesProcessed = $model->$processMethod();
		$this->_log( "Script finished, $filesProcessed file(s) processed.", Zend_log::DEBUG );
	}
}

main();

<?php

/**
 * Eb2c Feed Shell
 *
 * Ensures that feeds instantiate appropriate models by fetching the feed, instantiating the model, and 
 * validating that it implements the correct interface
 *
 */
class TrueAction_Eb2cCore_Model_Feed_Shell extends Varien_Object
{
	private $_availableFeeds;

	/**
	 * Basically assembles the 'feed model' => 'module' array
	 *
	 * @todo: move array to the 'normal' way we do configuration
	 */
	public function _construct()
	{
		if( $this->hasFeedSet() ) {
			$this->_availableFeeds = $this->getFeedSet();
		}
		else {
			$this->_availableFeeds = array (
				'feed_item_Inventories' => 'eb2cinventory',
				'status_feed'           => 'eb2corder',
				'feed_content_master'   => 'eb2cproduct',
				'feed_image_master'     => 'eb2cproduct',
				'feed_item_iship'       => 'eb2cproduct',
				'feed_item_master'      => 'eb2cproduct',
				'feed_item_pricing'     => 'eb2cproduct',
			);
		}
	}

	/**
	 * Instantiate and Validate that this model name implements 'TrueAction_Eb2cCore_Model_Feed_Interface' - that ensure we'll
	 * have a processFeeds method. That will convince me that this is an OK feed processor.
	 *
	 * @param $modelName a (theoretically) valid Model
	 * @return boolean null - Not a valid 'TrueAction_Eb2cCore_Model_Feed_Interface'
	 * @return Mage::getModel() - valid model for feed processing.
	 */
	private function _validateModel($modelName)
	{
		try {
			$model = Mage::getModel($modelName);
		} catch( Exception $e ) {
			Mage::logException($e);
			return false;
		}
		if( $model ) {
			$reflector = new ReflectionClass($model);
			if( !$reflector->implementsInterface('TrueAction_Eb2cCore_Model_Feed_Interface')) {
				Mage::log( '\'' . get_class($model) . '\' does not implement appropriate interface(s).', Zend_log::ERR);
				return false;
			}
		}
		return $model;
	}

	/**
	 * Given a partial feed name, return the approriate model for that feed. Side effect is that all feed 
	 * names must be unique. Intentionally called 'pregGet' as opposed to 'get' to avoid any magic connotations.
	 *
	 * @return boolean null - Not a valid 'TrueAction_Eb2cCore_Model_Feed_Interface'
	 * @return Mage::getModel() - valid model for feed processing.
	 */
	public function pregGetFeedModel($partialFeedName)
	{
		$match = preg_grep("/$partialFeedName/", array_keys($this->_availableFeeds));
		if( count($match) === 1 ) {
			$feedName = $match[key($match)];
			$modelName = $this->_availableFeeds[$feedName] . '/' . $feedName;
			return $this->_validateModel($modelName);
		}
		return false;
	}

	/**
	 * Given a partial feed name, run the processFeedsMethod
	 *
	 * @return Number of files processed
	 */
	public function runFeedModel($partialFeedName)
	{
		$model = $this->pregGetFeedModel($partialFeedName);
		if( !$model ) {
			Mage::throwException('No valid model found for feed type ' . $partialFeedName);
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		return $model->processFeeds();
	}


	/**
	 * Return a list of the configured feeds
	 *
	 * @return array List of Feeds configured
	 */
	public function listAvailableFeeds()
	{
		return array_keys($this->_availableFeeds);
	}
}

<?php
class TrueAction_Eb2cCore_Helper_Feed_Shell extends Mage_Core_Helper_Abstract
{
	/**
	 * Returns an array of available feed models configured in core config.xml.
	 * Does *not* validate them in any way - just returns what's configured.
	 * @return array Configured feed models in the form 'module/class_name'
	 */
	public function getConfiguredFeedModels()
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		$availableFeeds = array();
		foreach( $config->feedAvailableModels as $module => $feedClass ) {
			foreach( $feedClass as $class => $enabled ) {
				if( $enabled ) {
					$availableFeeds[] = $module . '/' . $class;
				}
			}
		}
		return $availableFeeds;
	}
}

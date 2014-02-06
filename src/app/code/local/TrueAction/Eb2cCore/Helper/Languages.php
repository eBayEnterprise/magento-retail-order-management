<?php
class TrueAction_Eb2cCore_Helper_Languages extends Mage_Core_Helper_Abstract
{
	/**
	 * Return an array of stores and attach a language code to them, Varien_Object style
	 * @param string langCode (optional) if passed, only stores using that langCode are returned.
	 * @return array of Mage_Core_Model_Store, each element of which has new magic getter 'getLanguageCode()'
	 */
	public function getStores($langCode=null)
	{
		$stores = array();
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				foreach ($group->getStores() as $store) {
					$config->setStore($store->getStoreId());
					if (!$langCode || ($langCode && $langCode === $config->languageCode)) {
						$store->setLanguageCode($config->languageCode);
						$stores[] = $store;
					}
				}
			}
		}
		return $stores;
	}
	/**
	 * Get a simple array of all language codes used in this installation
	 * @return array
	 */
	public function getLanguageCodesList()
	{
		$languages = array();
		foreach ($this->getStores() as $store) {
			$languages[] = $store->getLanguageCode();
		}
		return array_unique($languages);
	}
}

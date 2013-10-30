<?php
/**
 * Single point of access for retrieving configuration values.
 * Looks up configuration paths and values via configuration models registered with the helper.
 *
 * @prop string configVal The store config value represented by the key config_val
 * @prop bool configValFlag A boolean store config value represented by the key config_val
 */
class TrueAction_Eb2cCore_Model_Config_Registry
{
 	/**
	 * The default value to use when looking up config values.
	 * @var null|string|bool|int|Mage_Core_Model_Store
	 */
	protected $_store = null;
	/**
	 * Array of registered config models used to associate config paths with keys.
	 * @var TrueAction_Eb2cCore_Model_Config_Interface[]
	 */
	protected $_configModels = array();

	/**
	 * Add a new config settings model to the collection and update the list of known config paths
	 * @param TrueAction_Eb2cCore_Model_Config_Interface $configModel
	 * @return TrueAction_Eb2cCore_Helper_Config $this
	 */
	public function addConfigModel(TrueAction_Eb2cCore_Model_Config_Interface $configModel)
	{
		array_unshift($this->_configModels, $configModel);
		return $this;
	}

	/**
	 * Set a default store to use when getting config values
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return TrueAction_Eb2cCore_Helper_Config $this
	 */
	public function setStore($store)
	{
		$this->_store = Mage::app()->getStore($store);
		return $this;
	}

	/**
	 * Get the default store
	 * @return null|Mage_Core_Model_Store
	 */
	public function getStore()
	{
		return $this->_store;
	}

	/**
	 * Search through registered config models for one that knows about the key
	 * and get the actual config path from it for the lookup.
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @param boolean $asFlag
	 * @return string|boolean
	 * @throws Exception Raised if the config path is not found.
	 */
	protected function _getStoreConfigValue($configKey, $store, $asFlag)
	{
		foreach ($this->_configModels as $configModel) {
			if ($configModel->hasKey($configKey)) {
				$configMethod = $asFlag ? 'getStoreConfigFlag' : 'getStoreConfig';
				return Mage::$configMethod($configModel->getPathForKey($configKey), $store);
			}
		}
		Mage::throwException('Configuration path specified by ' . $key . ' was not found.');
		// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Get the configuration value represented by the given configKey
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return string
	 */
	public function getConfigFlag($configKey, $store=null)
	{
		// if a value is given store, use it, even if it is null/false/empty string/whatever
		$store = count(func_get_args()) > 1 ? $store : $this->getStore();
		return $this->_getStoreConfigValue($configKey, $store, true);
	}

	/**
	 * Get the configuration flag value represented by the given configKey
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return boolean
	 */
	public function getConfig($configKey, $store=null)
	{
		// if a value is given store, use it, even if it is null/false/empty string/whatever
		$store = count(func_get_args()) > 1 ? $store : $this->getStore();
		return $this->_getStoreConfigValue($configKey, $store, false);
	}

	/**
	 * Convert the magic method name, minus the "get"/"is" to a
	 * potential config key.
	 * Changes CamelCase to underscore_words
	 * @param string $name
	 * @return string
	 */
	protected function _magicNameToConfigKey($name)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $name));
	}

	/**
	 * Catch any unknown property references to try to magically retrieve config values.
	 * Uses the stored store.
	 *
	 * @param string $name The property name
	 * @return null|string|boolean|Mage_Core_Model_Store Boolean if the property name ends with "Flag",
	 *         Mage_Core_Model_Store if the property is "store", string otherwise.
	 */
	public function __get($name)
	{
		$store = $this->getStore();
		$isFlag = preg_match('/Flag$/', $name); // It's a flag if it ends with "Flag"
		// Strip the "Flag" part if it exists.
		$configKey = $this->_magicNameToConfigKey(substr($name, 0, strlen($name) - 4 * $isFlag));
		try {
			// Retrieve the actual config value, passing the $isFlag arg.
			return $this->_getStoreConfigValue($configKey, $store, $isFlag);
		} catch (Exception $e) {
			// Be consistent with how PHP treats undefined properties.
			trigger_error(sprintf('Undefined property: %s::%s in php shell code on line %d', get_class($this), $name, __LINE__));
		}
		return null;
	}

	public function __set($name, $value)
	{
		trigger_error(sprintf('Cannot write property %s::%s in php shell code on line %d', get_class($this), $name, __LINE__), E_USER_ERROR);
	}
}

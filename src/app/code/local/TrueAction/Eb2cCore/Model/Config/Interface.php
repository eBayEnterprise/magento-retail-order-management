<?php
interface TrueAction_Eb2cCore_Model_Config_Interface {
	/**
	 * Indicates this model knows about the given config key
	 * @return boolean
	 */
	public function hasKey($configKey);

	/**
	 * Return the config path for a known key
	 * @return string
	 */
	public function getPathForKey($configKey);
}

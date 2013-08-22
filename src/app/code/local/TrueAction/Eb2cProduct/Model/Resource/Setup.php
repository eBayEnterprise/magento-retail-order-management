<?php
class TrueAction_Eb2cProduct_Model_Resource_Setup
	extends Mage_Catalog_Model_Resource_Eav_Mysql4_Setup
{


	/**
	 * log an error message
	 * @param  string $message
	 */
	protected function _logError($message)
	{
		Mage::log($message, Zend_Log::ERR);
	}

	/**
	 * log a warning message
	 * @param  string $message
	 */
	protected function _logWarn($message)
	{
		Mage::log($message, Zend_Log::WARN);
	}
}

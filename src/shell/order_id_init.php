<?php
require_once 'abstract.php';

/**
 * Set the initial order number.
 */
class TrueAction_Eb2c_Shell_Increment extends Mage_Shell_Abstract
{
	/**
	 * getting default store id
	 * @return int, the default store id
	 */
	private function _getDefaultStoreId()
	{
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $storeId => $val) {
			return Mage::app()->getStore($storeId)->getId();
		}
		return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
	}

	/**
	 * The 'main' of a Mage Shell Script
	 * @see usageHelp
	 */
	public function run()
	{
		$args = array_keys($this->_args);

		if (count(array_intersect($args, array('help', '--help', '-h'))) > 0) {
			echo $this->usageHelp();
			return 0;
		} elseif (count($args) > 1) {
			echo $this->usageHelp();
			return 1;
		} else {
			$entityTypeId = (int) Mage::getSingleton('eav/entity_type')
				->loadByCode(Mage_Sales_Model_Order::ENTITY)
				->getEntityTypeId();
			$entityStoreConfig = Mage::getModel('eav/entity_store')
				->loadByEntityStore($entityTypeId, $this->_getDefaultStoreId());
			$lastIncrementId = $entityStoreConfig->getIncrementLastId();
			if (count($args) === 1) {
				if (is_numeric($args[0])) {
					$newIncrementId = (int) $args[0];
					$entityStoreConfig
						->setIncrementLastId($newIncrementId)
						->save();
				} else {
					echo $this->usageHelp();
					return 1;
				}
			} else { // no arguments
				printf("Current Increment Order Id is %d\n", $lastIncrementId);
			}
		}
	}

	/**
	 * @return string how to use this script
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE

Usage: php -f $scriptName [order_num]
  order_num  Start counting at this order number.
  help       This help

When run without arguments, prints the current order number

USAGE;
	}
}

$IncrementProcessor = new TrueAction_Eb2c_Shell_Increment();
exit($IncrementProcessor->run());

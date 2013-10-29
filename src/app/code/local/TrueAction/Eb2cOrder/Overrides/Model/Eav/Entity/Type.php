<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

class TrueAction_Eb2cOrder_Overrides_Model_Eav_Entity_Type
	extends Mage_Eav_Model_Entity_Type
{
	/**
	 * overriding this method to prepend ebc client order id prefix for increment order id.
	 * Retreive new incrementId
	 * @param int $storeId
	 * @return string
	 */
	public function fetchNewIncrementId($storeId=null)
	{
		if (!$this->getIncrementModel()) {
			return false;
		}

		if (!$this->getIncrementPerStore() || ($storeId === null)) {
			/**
			 * store_id null we can have for entity from removed store
			 */
			$storeId = 0;
		}

		// Start transaction to run SELECT ... FOR UPDATE
		$this->_getResource()->beginTransaction();

		$entityStoreConfig = Mage::getModel('eav/entity_store')
			->loadByEntityStore($this->getId(), $storeId);

		if (!$entityStoreConfig->getId()) {
			$entityStoreConfig
				->setEntityTypeId($this->getId())
				->setStoreId($storeId)
				->setIncrementPrefix($storeId)
				->save();
		}

		$incrementInstance = Mage::getModel($this->getIncrementModel())
			->setPrefix($entityStoreConfig->getIncrementPrefix())
			->setPadLength($this->getIncrementPadLength())
			->setPadChar($this->getIncrementPadChar())
			->setLastId($entityStoreConfig->getIncrementLastId())
			->setEntityTypeId($entityStoreConfig->getEntityTypeId())
			->setStoreId($entityStoreConfig->getStoreId());

		/**
		 * do read lock on eav/entity_store to solve potential timing issues
		 * (most probably already done by beginTransaction of entity save)
		 */
		$incrementId = $incrementInstance->getNextId();

		$entityStoreConfig->setIncrementLastId($incrementId);
		$entityStoreConfig->save();

		// Commit increment_last_id changes
		$this->_getResource()->commit();

		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$ebcPrefix = $cfg->clientOrderIdPrefix;
		if (trim($ebcPrefix) !== '' && trim($incrementId) !== '') {
			$incrementId = sprintf('%s%s', $ebcPrefix, substr($incrementId, 1, strlen($incrementId)-1));
		}

		return $incrementId;
	}
}

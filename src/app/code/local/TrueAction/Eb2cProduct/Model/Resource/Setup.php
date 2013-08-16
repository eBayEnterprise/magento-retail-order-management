<?php
class TrueAction_Eb2cProduct_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
	/**
	 * apply default attributes to all existing attribute sets.
	 * @return $this
	 */
	public function installDefaultAttributes()
	{
		/*
		load default attributes config.
		// load attribute sets
		// TODO: LOW_PRIORITY figure out filter to not load sets that already have defaults defined
		//$attrSets = Mage::getModel('eav/entity_attribute_set')->getCollection();
		// spin through each attribute set and associate the attributes to the attribute set
		foreach attributeset
			$this->applyDefaultAttributes($attributeset);
		*/
		return $this;
	}

	/**
	 * get the attribute set collection.
	 * @return Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
	 */
	protected function _getAttributeSetCollection()
	{
		$collection = Mage::getModel('eav/entity_attribute_set')
			->getCollection();
		return $collection;
	}

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

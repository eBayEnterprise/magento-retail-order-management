<?php
class TrueAction_Eb2cProduct_Model_Resource_Eav_Entity_Setup
	extends Mage_Catalog_Model_Resource_Eav_Mysql4_Setup
{
	/**
	 * base log message template.
	 * @var string
	 */
	protected $_baseLogMessage = '%s: %sattribute "%s" entity type "%s": %s';

	/**
	 * apply default attributes to all valid attribute sets.
	 * @param  mixed $attributeSet
	 * @return $this
	 */
	public function applyToAllSets($attrInfo)
	{
		$entityTypeIds  = $attrInfo->getTargetEntityTypeIds();
		$attributesData = $attrInfo->getAttributesData();
		foreach ($entityTypeIds as $entityTypeId) {
			$message = 'applying default attributes to entity type id(%s)';
			$this->_logDebug(sprintf($message, $entityTypeId));
			foreach ($attributesData as $attrCode => $attrConfig) {
				$attrId = $this->getAttribute($entityTypeId, $attrCode, 'attribute_id');
				if ($attrId) {
					$message = 'existing attribute with id=\'%s\' will be replaced';
					$this->_logDebug(sprintf($message, $attrId));
				}

				$this->addAttribute($entityTypeId, $attrCode, $attrConfig);
				if ($attrId) {
					$message = 'existing attribute with id=\'%s\' was replaced';
					$this->_logWarn(sprintf($message, $attrId));
				}
			}
		}
		return $this;
	}


	protected function _setCurrentEntityType($entityType)
	{
	}

	protected function _setCurrentAttributeCode($attrCode)
	{
	}

	/**
	 * return an attributeset for the product entity type or null
	 * @param  mixed $attributeSet
	 * @return Mage_Eav_Model_Entity_Attribute_Set
	 * @throws Mage_Core_Exception If $attributeSet references an invalid attribute set
	 */
	protected function _getAttributeSet($attributeSet = null)
	{
		$errorMessage = '';
		// take either an id or a model.
		if (!$attributeSet instanceof Mage_Eav_Model_Entity_Attribute_Set) {
			if (is_int($attributeSet)) {
				$attributeSet = Mage::getModel('eav/entity_attribute_set')
					->load($attributeSet);
			} else {
				$errorMessage = 'unable to retrieve attribute set "' .
					(string) $attributeSet .'"';
				$this->_logWarn($errolrMessage);
			}
		}
		if (!$errorMessage && !$this->_isValidEntityType($attributeSet)) {
			$errorMessage = 'attribute set is unexpected entity type: typeId(' .
				$attributeSet->getEntityTypeId() . ')';
			$this->_logDebug($errorMessage);
		}
		if ($errorMessage) {
			$attributeSet = null;
		}
		return $attributeSet;
	}

	/**
	 * lookup an attribute by code and entity id.
	 * @param  string $attributeCode
	 * @param  int $entityTypeId
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected function _lookupAttribute($attributeCode, $entityTypeId)
	{
		$attrData = $this->getAttribute($entityTypeId, $attributeCode);
		return $attr;
	}

	protected function _checkAttributeGroup($groupName, $attrSetId, $entityTypeId)
	{
		$groupData = $this->getAttributeGroup(
			$entityTypeId,
			$attrSetId,
			$groupName
		);
		$isGroupSame = isset($groupData['attribute_group_id']) &&
			$groupData['attribute_group_name'] === $groupName;
		if (!$isGroupSame) {
			$message = 'replacing attribute group (%s) with (%s)';
			$this->logWarn(sprintf(
				$message,
				$groupData['attribute_group_name'],
				$groupName
			));
		}
		return $this;
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

	/**
	 * log an error message
	 * @param  string $message
	 */
	protected function _logDebug($message)
	{
		Mage::log($message, Zend_Log::ERR);
	}
}

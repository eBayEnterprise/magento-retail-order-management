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
		$this->cleancache();
		return $this;
	}

	/**
	 * log a warning message
	 * @param  string $message
	 * @codeCoverageIgnore
	 */
	protected function _logWarn($message)
	{
		Mage::log($message, Zend_Log::WARN);
	}

	/**
	 * log an error message
	 * @param  string $message
	 * @codeCoverageIgnore
	 */
	protected function _logDebug($message)
	{
		Mage::log($message, Zend_Log::ERR);
	}
}

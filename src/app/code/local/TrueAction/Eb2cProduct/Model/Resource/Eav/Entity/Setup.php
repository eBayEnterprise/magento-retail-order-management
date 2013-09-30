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
			$msg = 'applying default attributes to entity type id(%s)';
			$this->_logErr(sprintf($msg, $entityTypeId));
			foreach ($attributesData as $attrCode => $attrConfig) {
				$attrId = $this->getAttribute($entityTypeId, $attrCode, 'attribute_id');
				if ($attrId) {
					$msg = 'existing attribute with id="%s" will be replaced';
					$this->_logErr(sprintf($msg, $attrId));
				}
				$this->addAttribute($entityTypeId, $attrCode, $attrConfig);
				if ($attrId) {
					$msg = 'existing attribute with id="%s" was replaced';
					$this->_logWarn(sprintf($msg, $attrId));
				}
			}
		}
		return $this;
	}

	/**
	 * log a warning message
	 * @param string $msg
	 * @codeCoverageIgnore
	 */
	protected function _logWarn($msg)
	{
		Mage::log(sprintf('[ %s ] %s', __CLASS__, $msg), Zend_Log::WARN);
	}

	/**
	 * log an error message
	 * @param string $msg
	 * @codeCoverageIgnore
	 */
	protected function _logErr($msg)
	{
		Mage::log(sprintf('[ %s ] %s', __CLASS__, $msg), Zend_Log::ERR);
	}
}

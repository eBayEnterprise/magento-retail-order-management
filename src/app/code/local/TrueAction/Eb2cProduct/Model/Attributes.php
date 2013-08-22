<?php
class TrueAction_Eb2cProduct_Model_Attributes extends Mage_Core_Model_Abstract
{
	/**
	 * @var string path of the attributes configuration in the global config.
	 */
	const ATTRIBUTES_CONFIG                            = 'global/eb2cproduct_attributes';

	/**
	 * name of the file containing overriding configuration.
	 * @var string
	 */
	protected static $_attributeConfigOverrideFilename = 'eb2cproduct_attributes.xml';

	/**
	 * eav setup model.
	 * @var Mage_Core_Catalog_Model_Resource_Eav_Mysql4_Setup
	 */
	protected $_eavSetup = null;

	/**
	 * base log message template.
	 * @var string
	 */
	protected $_baseLogMessage = '%s: %sattribute "%s" entity type "%s": %s';

	/**
	 * the attributes configuration
	 * @var [type]
	 */
	protected $_defaultAttributesConfig = null;

	/**
	 * prototype attribute data cache.
	 * @var array
	 */
	protected $_prototypeCache = array();

	protected $_entityTypes    = array(
		'catalog/category',
	);

	/**
	 * mapping of config field name to model field name
	 * @var array
	 */
	protected $_fieldNameMap = array(
		'scope'         => 'is_global',
		'unique'        => 'is_unique',
		'input_type'    => 'frontend_input',
		'product_types' => 'apply_to',
		'label'         => 'frontend_label',
	);

	protected $_valueFunctionMap = array(
		'is_global'           => '_formatScope',
		'frontend_label'      => '_formatFrontendLabel',
		'apply_to'            => '_formatArray',
		'default_value_yesno' => '_formatBoolean',
		'is_unique'           => '_formatBoolean',
		'default_value_date'  => '_formatDate',
	);

	protected static $_scopeMap = array(
		'website' => '1',
		'store'   => '0',
	);

	protected function _construct()
	{
		$this->_eavSetup = Mage::getResourceModel('catalog/eav_mysql4_setup', 'core_setup');
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
				$this->_logWarn($errorMessage);
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
	 * return true if the attribute set is an entity type that
	 * should have the attributes applied.
	 * @param  int  $typeId
	 * @return boolean
	 */
	protected function _isValidEntityType($typeId)
	{
		$result = in_array((int)$typeId, $this->_getTargetEntityTypeIds());
		return $result;
	}

	protected function _setCurrentEntityType($entityType)
	{
	}

	protected function _setCurrentAttributeCode($attrCode)
	{
	}

	/**
	 * return a list of entity type id's the attributes will be added to.
	 * @return array
	 */
	protected function _getTargetEntityTypeIds()
	{
		$result = array();
		foreach ($this->_entityTypes as $entityType) {
			$result[] = Mage::getModel($entityType)->getResource()->getTypeId();
		}
	}

	/**
	 * apply default attributes to all valid attribute sets.
	 * @param  mixed $attributeSet
	 * @return $this
	 */
	public function applyDefaultAttributes()
	{
		$entityTypeIds  = $this->_getTargetEntityTypeIds();
		$config         = $this->_loadDefaultAttributesConfig();
		$defaults       = $config->getNode('default');
		foreach ($entityTypeIds as $entityTypeId) {
			$message = 'applying default attributes';
			$this->_logDebug(sprintf($message, $entityTypeId));
			foreach ($defaults->children() as $attrCode => $attrConfig) {
				$prototypeAttrData = $this->_getPrototypeData($attrConfig);
				$attrId = $this->_eavSetup->getAttribute($entityTypeId, $attrCode, 'attribute_id');
				if ($attrId) {
					$message = 'existing attribute with id=\'%s\' will be replaced';
					$this->_logDebug(sprintf($message, $attrId));
				}
				$this->_eavSetup->addAttribute($entityTypeId, $attrCode, $prototypeAttrData);
				if ($attrId) {
					$message = 'existing attribute with id=\'%s\' was replaced';
					$this->_logWarn(sprintf($message, $attrId));
				}
			}
		}
		return $this;
	}

	/**
	 * convert the fronend label into an an array.
	 * @param  Varien_SimpleXml_Element $data
	 * @return array
	 */
	protected function _formatFrontendLabel($data)
	{
		return array((string) $data, '', '', '', '');
	}

	/**
	 * lookup an attribute by code and entity id.
	 * @param  string $attributeCode
	 * @param  int $entityTypeId
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected function _lookupAttribute($attributeCode, $entityTypeId)
	{
		$eavSetup = Mage::getModel('eav/entity_setup');
		$attrData = $eavSetup->getAttribute($entityTypeId, $attributeCode);
		return $attr;
	}

	protected function _checkAttributeGroup($groupName, $attrSetId, $entityTypeId)
	{
		$groupData = $this->_eavSetup->getAttributeGroup(
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
	 * get an attribute group model.
	 * return null if the group does not exist.
	 * @param  string $groupName
	 * @param  string $setId
	 * @return Mage_Catalog_Model_Product_Attribute_Group
	 */
	protected function _getAttributeGroup($groupName, $setId)
	{
		$model = Mage::getModel('eav/entity_attribute_group')
			->getResourceCollection()
			->AddFieldToFilter('attribute_group_name', array('eq' => $groupName))
			->setAttributeSetFilter($setId)
			->load()
			->getFirstItem();
		$group = $model->getId() ? $model : null;
		return $group;
	}

	/**
	 * get the attribute model field name for the config field name.
	 * @param  string $fieldName
	 * @return string
	 */
	protected function _getMappedFieldName($fieldName)
	{
		$result = array_key_exists($fieldName, $this->_fieldNameMap) ?
			$this->_fieldNameMap[$fieldName] :
			$fieldName;
		return $result;
	}

	/**
	 * convert the scope string to the integer value
	 * @param  Varien_SimpleXml_Element $data
	 * @return int
	 */
	protected function _formatScope($data)
	{
		$scopeStr = strtolower((string) $data);
		$key = strtolower($scopeStr);
		if (!isset(self::$_scopeMap[$scopeStr])) {
			Mage::throwException('Invalid scope value "' . $scopeStr . '"');
		}
		$val = self::$_scopeMap[$scopeStr];
		return $val;
	}

	/**
	 * convert the ',' delimited list into an array.
	 * @param  Varien_SimpleXml_Element $data
	 * @return int
	 */
	protected function _formatArray($data)
	{
		$str = (string) $data;
		$val = explode(',', $str);
		return $val;
	}

	/**
	 * convert a string into a boolean value.
	 * @see  http://php.net/manual/en/function.is-bool.php
	 * @param Varien_SimpleXml_Element
	 * @return  bool
	 */
	protected function _formatBoolean($data)
	{
		$str = strtolower((string) $data);
		switch ($str) {
			case $str === true:
			case $str == 1:
			// case $str == '1': // no need for this, because we used
								 // $val == 1 not $str === 1
			case $str == 'true':
			case $str == 'on':
			case $str == 'yes':
			case $str == 'y':
				$out = '1';
				break;
			default: $out = '0';
		}
		return $out;
	}

	/**
	 * convert data from the config to a form that can be set to the specified
	 * field on the attribute model.
	 * @param  string                   $fieldName
	 * @param  Varien_SimpleXml_Element $data
	 * @return string
	 */
	protected function _getMappedFieldValue($fieldName, Varien_SimpleXml_Element $data)
	{
		$value = (string) $data;
		if (isset($this->_valueFunctionMap[$fieldName])) {
			$funcName = $this->_valueFunctionMap[$fieldName];
			if (!method_exists($this, $funcName)) {
				Mage::throwException(
					"invalid value-function map. $funcName is not a member of " . get_class($this)
				);
			}
			$value = $this->$funcName($data);
		}
		return $value;
	}

	/**
	 * get the name of the default value field for based on the frontend type.
	 * @param   string $frontendType
	 * @return  string default value field name.
	 */
	protected function _getDefaultValueFieldName($frontendType)
	{
		$frontendType = strtolower($frontendType);
		$fieldName    = 'default_value_';
		$suffix       = '';
		switch ($frontendType) {
			case $frontendType === 'boolean':
				$suffix = 'yesno';
			break;
			case $frontendType === 'date':
				$suffix = 'date';
			break;
			case $frontendType === 'select';
			case $frontendType === 'multiselect';
				$fieldName = 'option';
				$suffix    = '';
			break;
			default:
				$suffix = 'text';
			break;
		}
		$fieldName .= $suffix;
		return $fieldName;
	}

	/**
	 * return an array of the default attribute codes.
	 * optionally, the list can be filtered to only include codes whose group is $groupFilter.
	 * @param string $groupFilter
	 * @return array
	 */
	public function getDefaultAttributesCodeList($groupFilter = null, $onlyUngrouped = false)
	{
		Mage::log('getDefaultAttributesCodeList called with' . $groupFilter);
		$result  = array();
		// load the attributes from the config.
		$config  = $this->_loadDefaultAttributesConfig();
		// loop through the attributes and return the list of attribute names as an array.
		$default = $config->getNode('default');
		foreach ($default->children() as $code => $node) {
			if (!$groupFilter) {
				$result[] = $code;
			} elseif ($node->group && $groupFilter === (string)$node->group) {
				$result[] = $code;
			}
		}
		// TODO: perhaps store it in a cache?
		return $result;
	}

	/**
	 * get the entity type id of the product model.
	 * @return int
	 */
	protected function _getDefaultEntityTypeId()
	{
		$entityTypeID = Mage::getModel('catalog/category')->getResource()->getTypeId();
		return $entityTypeID;
	}

	/**
	 * get an array to initialize an attribute model with data extracted from the config.
	 * @param  Varien_SimpleXml_Element $fieldCfg
	 * @return Mage_Catalog_Model_Eav_Entity_Attribute
	 */
	protected function _getPrototypeData(Varien_SimpleXml_Element $fieldCfg)
	{
		$attributeCode = $fieldCfg->getName();
		if (!isset($this->_prototypeCache[$attributeCode])) {
			$baseData = $this->_getInitialData();
			foreach ($fieldCfg->children() as $cfgField => $data) {
				$fieldName = '';
				if ($cfgField === 'default') {
					$input_type = (string) $fieldCfg->input_type;
					$fieldName  = $this->_getDefaultValueFieldName($input_type);
				} else {
					$fieldName = $this->_getMappedFieldName($cfgField);
				}
				$value                = $this->_getMappedFieldValue($fieldName, $data);
				$baseData[$fieldName] = $value;
			}
			$this->_prototypeCache[$attributeCode] = $baseData;
		}
		return $this->_prototypeCache[$attributeCode];
	}

	/**
	 * load attribute configuration files into a mage config object.
	 * satisfies requirements:
	 * 	- attributes stored in config file
	 *  - attributes are overridable.
	 * @return Mage_Core_Model_Config_Base
	 */
	protected function _loadDefaultAttributesConfig()
	{
		if (!$this->_defaultAttributesConfig) {
			$config = Mage::getModel('core/config')
				->setXml(Mage::getConfig()->getNode(self::ATTRIBUTES_CONFIG));
			// load config from an xml file and merge it with the base config.
			Mage::getConfig()->loadModulesConfiguration(
				self::$_attributeConfigOverrideFilename,
				$config
			);
			$this->_defaultAttributesConfig = $config;
		}
		return $this->_defaultAttributesConfig;
	}

	/**
	 * get an array containing the defaults for an attribute.
	 * @return array
	 */
	protected function _getInitialData()
	{
		$data = array(
			'is_global'                     => '0',
			'frontend_input'                => 'text',
			'default_value_text'            => '',
			'default_value_yesno'           => '0',
			'default_value_date'            => '',
			'default_value_textarea'        => '',
			'is_unique'                     => '0',
			'is_required'                   => '0',
			'frontend_class'                => '',
			'is_searchable'                 => '1',
			'is_visible_in_advanced_search' => '1',
			'is_comparable'                 => '1',
			'is_used_for_promo_rules'       => '0',
			'is_html_allowed_on_front'      => '1',
			'is_visible_on_front'           => '0',
			'used_in_product_listing'       => '0',
			'used_for_sort_by'              => '0',
			'is_configurable'               => '0',
			'is_filterable'                 => '0',
			'is_filterable_in_search'       => '0',
			'backend_type'                  => 'varchar',
			'default_value'                 => '',
		);
		return $data;
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
	 * log a debug message
	 * @param  string $message
	 */
	protected function _logDebug($message)
	{
		Mage::log($message, Zend_Log::DEBUG);
	}
}

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
		'catalog/product',
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

	/**
	 * return a list of entity type id's the attributes should be added to.
	 * @return array
	 */
	public function getTargetEntityTypeIds()
	{
		$result = array();
		foreach ($this->_entityTypes as $entityType) {
			$result[] = (int) Mage::getModel($entityType)->getResource()->getTypeId();
		}
		return $result;
	}

	public function getAttributesData()
	{
		$config      = $this->_loadDefaultAttributesConfig();
		$defaultNode = $config->getNode('default');
		foreach ($defaultNode->children() as $attrCode => $attrConfig) {
			$this->_getPrototypeData($attrConfig);
		}
		Mage::log("getattributesdata");
		return $this->_prototypeCache;
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
}

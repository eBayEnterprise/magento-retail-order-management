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
	 * the attributes configuration
	 * @var [type]
	 */
	protected $_defaultAttributesConfig = null;

	/**
	 * prototype attribute model cache.
	 * @var array
	 */
	protected $_prototypeCache = array();

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
			}
		}
		if (
			!$errorMessage &&
			$attributeSet->getEntityTypeId() !== $this->_getDefaultEntityTypeId()
		) {
			$errorMessage = 'attribute set is unexpected entity type: typeId(' .
				$attributeSet->getEntityTypeId() . ')';
		}
		if ($errorMessage) {
			$attributeSet = null;
			Mage::throwException($errorMessage);
		}
		return $attributeSet;
	}

	/**
	 * apply default attributes to $attributeSet
	 * @param  mixed $attributeSet
	 * @return $this
	 */
	public function applyDefaultAttributes($attributeSet = null)
	{
		$attributeSet   = $this->_getAttributeSet($attributeSet);
		$config         = $this->_loadDefaultAttributesConfig();
		$defaults       = $config->getNode('default');
		$attributeSetId = $attributeSet->getId();
		$entityTypeId   = $attributeSet->getEntityTypeId();
		foreach ($defaults->children() as $attrCode => $attrConfig) {
			$newAttr = $this->_getModelPrototype($attrConfig);
			$attr = $this->_lookupAttribute($attrCode, $entityTypeId);
			$group = null;
			if ($newAttr->hasGroup()) {
				// attribute is in a group
				$groupName = $newAttr->getGroup(); // note: group field
				$group = $this->_getAttributeGroup($groupName, $attributeSetId);
				if (!$group) {
					$message = 'unable to get model for group (%s)';
					Mage::throwException(sprintf($message, $groupName));
				}
			}
			if ($attr->getId()) {
				// check if the groupid !== $group->getAttributeGroupId
				if ($attr->getAttributeGroupId() !== $group->getId()) {
					// log a warning cuz the attribute already exists with another group.
					$message = 'replacing attribute group (%s) with (%s)';
					$this->_logWarn(sprintf($message, $group->getAttributeGroupName(), $newAttr->getGroup()));
					// update attr with new group.
					$attr->setAttributeGroupId($group->getId());
					// attr->save
					$attr->save();
				}
				// the groups match so nothing to do.
			} else {
				// the attribute doesn't exist, so use the prototype to add it.
				$newAttr->setAttributeGroupId($group->getId());
				$newAttr->save();
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
		$collection = Mage::getResourceModel('catalog/eav_attribute_collection');
		$attr = $collection->AddFieldToFilter('entity_type_id', array('eq' => $entityTypeId))
			->AddFieldToFilter('attribute_code', array('eq' => $attributeCode))
			->load()
			->getFirstItem();
		return $attr;
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
	 * Create an attribute.
	 * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
	 * @see http://www.magentocommerce.com/wiki/5_-_modules_and_development/catalog/programmatically_adding_attributes_and_attribute_sets
	 * @return int|false
	 */
	public function createAttribute(Mage_Core_Model_Config_Base $config)
	{
		$labelText = trim($labelText);
		$attributeCode = trim($attributeCode);

		if($labelText == '' || $attributeCode == '') {
			$this->logError("Can't import the attribute with an empty label or code.  LABEL= [$labelText]  CODE= [$attributeCode]");
			return false;
		}
		if($values === -1) {
			$values = array();
		}
		if($productTypes === -1) {
			$productTypes = array();
		}

		if($setInfo !== -1 && (isset($setInfo['SetID']) == false || isset($setInfo['GroupID']) == false)) {
			$this->logError("Please provide both the set-ID and the group-ID of the attribute-set if you'd like to subscribe to one.");
			return false;
		}

		$this->logInfo("Creating attribute [$labelText] with code [$attributeCode].");

		//>>>> Build the data structure that will define the attribute. See
		//     Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
		$data = $this->_getInitialData();
		// Now, overlay the incoming values on to the defaults.
		foreach($values as $key => $newValue) {
			if(isset($data[$key]) == false) {
	 			$this->logError("Attribute feature [$key] is not valid.");
	 			return false;
	 		} else {
	 			$data[$key] = $newValue;
	 		}
		}
		// Valid product types: simple, grouped, configurable, virtual, bundle, downloadable, giftcard
		$data['apply_to']       = $productTypes;
		$data['attribute_code'] = $attributeCode;
		$data['frontend_label'] = array(
			0 => $labelText,
			1 => '',
			3 => '',
			2 => '',
			4 => '',
		);
		$model = Mage::getModel('catalog/resource_eav_attribute');
		$model->addData($data);

		if($setInfo !== -1) {
			$model->setAttributeSetId($setInfo['SetID']);
			$model->setAttributeGroupId($setInfo['GroupID']);
		}

		$entityTypeID = $this->getEntityTypeId();
		$model->setEntityTypeId($entityTypeID);
		$model->setIsUserDefined(1);

		try {
			$model->save();
		} catch(Exception $ex) {
			$this->logError("Attribute [$labelText] could not be saved: " . $ex->getMessage());
			return false;
		}

		$id = $model->getId();
		$this->logInfo("Attribute [$labelText] has been saved as ID ($id).");
		return $id;
	}

	/**
	 * get the entity type id of the product model.
	 * @return int
	 */
	protected function _getDefaultEntityTypeId()
	{
		$entityTypeID = Mage::getModel('catalog/product')->getResource()->getTypeId();
		return $entityTypeID;
	}

	/**
	 * get a prototype array to use to initialize an attribute model with config data.
	 * @param  Varien_SimpleXml_Element $fieldCfg
	 * @return Mage_Catalog_Model_Eav_Entity_Attribute
	 */
	protected function _getModelPrototype(Varien_SimpleXml_Element $fieldCfg)
	{
		$attributeCode = $fieldCfg->getName();
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
		$model = Mage::getResourceModel('catalog/eav_attribute');
		$model->addData($baseData);
		return $model;
	}

	/**
	 * create the attribute if it doesnt exist and return the model for it.
	 * @param  string $code
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected function _getOrCreateAttribute($code)
	{
		// use collection to load instances of model.
		return Mage::getModel('catalog/resource_eav_attribute')->load($code, 'attribute_code');
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
}

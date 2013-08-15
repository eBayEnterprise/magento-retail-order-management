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
	 * apply default attributes to $attributeSet
	 * @param  mixed $attributeSet
	 * @return $this
	 */
	public function applyDefaultAttributes($attributeSet = null)
	{
		// take either an id or a model.
		// load the attribute set
		// if entity type id not a product entitry type id
			// log a debug message
			// return
		// foreach attribute
			// if in a group
				// $this->_getAttributeGroup($groupName, $attributeSetId);
				// select the attribute for the group and entitytypeid
			// if not in a group
				// use entitytype id and attribute_code to select attribute
			// if not exist
				// create empty attribute model
				// update model
					// get default data
					// update the data array
						// for each field/value pair in attribute config
							// map field to data array key
							// apply value to array element by key
				// save the model
		return $this;
	}

	/**
	 * return an array of the default attribute codes.
	 * optionally, the list can be filtered to only include codes whose group is $groupFilter.
	 * @param string $groupFilter
	 * @return array
	 */
	public function getDefaultAttributesCodeList($groupFilter = null)
	{
		Mage::log('getDefaultAttributesCodeList called with' . $groupFilter);
		$result = array();
		// load the attributes from the config.
		// loop through the attributes and return the list of attribute names as an array.
		// TODO: perhaps store it in a cache?
		return $result;
	}

	/**
	 * apply default attributes to all existing attribute sets.
	 * @return $this
	 */
	public function installDefaultAttributes()
	{
		/*
		use list of default attributes to load models for the default attributes.
		// load attribute sets
		// TODO: LOW_PRIORITY figure out filter to not load sets that already have defaults defined
		//$attrSets = Mage::getModel('eav/entity_attribute_set')->getCollection();
		// spin through each attribute set and associate the attributes to the attribute set
		foreach attributeset
			foreach default attribute
				get attribute instance for attribute set
					if attribute doesn't exist, create it.
		*/
		return $this;
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
	 * load the attribute identified by $code into a model and return the model object.
	 * @param  string $code
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected function _getOrCreateAttribute($code)
	{
		// use collection to load instances of model.
		return Mage::getModel('catalog/resource_eav_attribute')->load($code, 'attribute_code');
	}

	/**
	 * return the config model for the default attributes configuration.
	 * @return [type] [description]
	 */
	protected function _getBaseConfig()
	{
		$config = Mage::getConfig(self::DEFAULT_ATTRIBUTES_CONFIG);
		return $config;
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
		$config = $this->_getBaseConfig();
		// load config from an xml file and merge it with the base config.
		$config = Mage::getSingleton('core/config')->loadModulesConfiguration(
			self::$_attributeConfigFilename
			null,
			$config
		);
		return $config;
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
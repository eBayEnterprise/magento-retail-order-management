<?php
class TrueAction_Eb2cProduct_Helper_Map_Attribute extends Mage_Core_Helper_Abstract
{
	const COLOR = 'color';

	/**
	 * @var Mage_Eav_Model_Resource_Entity_Attribute_Collection
	 */
	protected $_attributeCollection = null;

	/**
	 * @var int product entity type id
	 */
	protected $_entityTypeId = null;

	/**
	 * set and retrieve the catalog/product entity type id to class property
	 * self::_entityTypeId
	 * @return int
	 */
	protected function _getEntityTypeId()
	{
		if (is_null($this->_entityTypeId)) {
			$this->_entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
		}

		return $this->_entityTypeId;
	}

	/**
	 * set and retrieve the attribute collection
	 * @return Mage_Eav_Model_Resource_Entity_Attribute_Collection
	 */
	protected function _getAttributeCollection()
	{
		if (is_null($this->_attributeCollection)) {
			$this->_attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection')
				->addFieldToFilter('entity_type_id', $this->_getEntityTypeId())
				->addExpressionFieldToSelect('lcase_attr_code', 'LCASE({{attrcode}})', array('attrcode' => 'attribute_code'));
		}

		return $this->_attributeCollection;
	}

	/**
	 * get attribute id by attribute name
	 * @param string $name the attribute to check if exists in magento
	 * @return int|null the actual attribute id otherwise return null if attribute is not in magento
	 */
	protected function _getAttributeIdByName($name)
	{
		$attribute = $this->_getAttributeCollection()->getItemByColumnValue('attribute_code', $name);
		return ($attribute)? $attribute->getId() : null;
	}

	/**
	 * get attribute object by attribute code
	 * @param string $name the attribute code to get this instance in the collection by
	 * @return Mage_Eav_Model_Entity_Attribute
	 */
	protected function _getAttributeInstanceByName($name)
	{
		return Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $name);
	}

	/**
	 * extract the color for a given product, determine which store id set for this product
	 * get the language code for the store, base on the language code determine which language description
	 * to use to get the option specific for this product
	 * @param DOMNodeList $node DOM nodes extracted from the feed
	 * @return int|null
	 */
	public function extractColorValue(DOMNodeList $nodeList)
	{
		$colorCode = Mage::helper('eb2ccore')->extractNodeVal($nodeList);
		$optionId = $this->_getAttributeOptionId(self::COLOR, $colorCode);
		return (!$optionId)? $this->_addNewOption(self::COLOR, $colorCode) : $optionId;
	}

	/**
	 * given an attribute code and and new option code add new record and return
	 * the newly added option item of the given attribute code
	 * @param string $attributeCode the attribute code such as color or size, etc
	 * @param string $optionCode the option to be added to the attribute
	 * @return int the newly added option code option id
	 */
	protected function _addNewOption($attributeCode, $optionCode)
	{
		$this->_loadEavAttributeModel($this->_getAttributeIdByName($attributeCode))
			->addData(array(
				'option' => array('value' => array(0 => array(
					Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID => $optionCode
				)))
			))
			->save();
		return $this->_getAttributeOptionId($attributeCode, $optionCode);
	}

	/**
	 * given an attribute id load the Mage_Catalog_Model_Resource_Eav_Attribute class
	 * and return the it as a new object
	 * @param int $attributeId the attribute for example color
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected function _loadEavAttributeModel($attributeId)
	{
		return Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
	}

	/**
	 * get a list of option from a given attribute code and store id
	 * @param string $attributeCode the attribute code (color, size)
	 * @param int $storeId and actual store entity id
	 * @param string $optionValue
	 * @return int list of label/value
	 */
	protected function _getAttributeOptionId($attributeCode, $optionValue)
	{
		$option = Mage::getResourceModel('eav/entity_attribute_option_collection')
			->setAttributeFilter($this->_getAttributeIdByName($attributeCode))
			->addFieldToFilter('tdv.value', $optionValue)
			->setStoreFilter(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
			->load()
			->getFirstItem();

		return (!is_null($option))? (int) $option->getOptionId() : 0;
	}

	/**
	 * given a DOMNodeList and a Mage_Catalog_Model_Product make sure this is
	 * a product of type configurable and then extract the configurable attribute from the node list
	 * then get the configurable attribute array for each configured attributes
	 * @param DOMNOdeList $nodes the node with configurableAttributes data
	 * @return array|null an array of configurable attribute data if the given product is configurable otherwise null
	 */
	public function extractConfigurableAttributesData(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$typeInstance = $product->getTypeInstance(true);
		// making sure the right type instance is set on the product
		if (!$typeInstance instanceof Mage_Catalog_Model_Product_Type_Configurable) {
			$product->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
				->setTypeInstance(Mage_Catalog_Model_Product_Type::factory($product, true), true);
		}

		$data = null; // purposely setting this to null just in cause all the attribute already exists
		// we need to know which configurable attribute we already have for this product
		// so that we don't try to create the same super attribute relationship which will
		// cause unique key duplication sql constraint to be thrown
		$existedData = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
		foreach (explode(',', strtolower(Mage::helper('eb2ccore')->extractNodeVal($nodes))) as $attributeCode) {
			// if we don't currently have a super attribute relationship then get the
			// configurable attribute data
			if (!$this->_isSuperAttributeExists($existedData, $attributeCode)) {
				$data[] = $this->_getConfiguredAttributeData($attributeCode);
			}
		}
		return $data;
	}

	/**
	 * given an array of super configurable attribute check if the given attribute code
	 * if in this array of super configurable attributes data
	 * @param array $attributeData the super configurable attribute data
	 * @param string $attributeCode the attribute code to check if exist in this attribute data
	 * @return bool true the given attribute code is find otherwise not found
	 */
	protected function _isSuperAttributeExists(array $attributeData, $attributeCode)
	{
		foreach ($attributeData as $data) {
			if ($data['attribute_code'] === $attributeCode) {
				return true;
			}
		}
		return false;
	}

	/**
	 * check if the given DOMNodeList has actual value and return true otherwise false
	 * @param DOMNOdeList $nodes the node with configurableAttributes data
	 * @return bool
	 */
	public function extractCanSaveConfigurableAttributes(DOMNodeList $nodes)
	{
		return (Mage::helper('eb2ccore')->extractNodeVal($nodes) !== '');
	}

	/**
	 * given an attribute code get the configurable data
	 * @param string $attributeCode
	 * @return array configured attribute data
	 */
	protected function _getConfiguredAttributeData($attributeCode)
	{
		$superAttribute = $this->_getAttributeInstanceByName($attributeCode);
		$configurableAtt = $this->_getConfigurableAttributeModel($superAttribute);
		return array(
			'id' => $configurableAtt->getId(),
			'label' => $configurableAtt->getLabel(),
			'position' => (int) $configurableAtt->getPosition(),
			'values' => array(),
			'attribute_id' => $superAttribute->getId(),
			'attribute_code' => $superAttribute->getAttributeCode(),
			'frontend_label' => $superAttribute->getFrontEnd()->getLabel(),
		);
	}

	/**
	 * instantiate the Mage_Catalog_Model_Product_Type_Configurable_Attribute class, pass the given
	 * Mage_Eav_Model_Entity_Attribute object to setProductAttribute and then return the new instantiate
	 * @param Mage_Eav_Model_Entity_Attribute $attribute
	 * @return Mage_Catalog_Model_Product_Type_Configurable_Attribute
	 */
	protected function _getConfigurableAttributeModel(Mage_Eav_Model_Entity_Attribute $attribute)
	{
		return Mage::getModel('catalog/product_type_configurable_attribute')
			->setProductAttribute($attribute);
	}
}

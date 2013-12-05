<?php
/**
 * Contains utility methods to check attributes in attributes set
 * methods to add attributes to attributes sets
 */
class TrueAction_Eb2cProduct_Model_Feed_Attribute
{
	const DEFAULT_SORT_ORDER = 6;
	const DEFAULT_ATTRIBUTE_SET_GROUP = null;

	/**
	 * getting product entity type id
	 * @return int, the entity type id for product
	 */
	public function getProductEntityTypeId()
	{
		return (int) Mage::getModel('catalog/product')->getResource()
			->getEntityType()
			->getId();
	}

	/**
	 * getting attribute set id by attribute set name
	 * @param string $attributeSetName, the attribute set name
	 * @param int $entityTypeId, the entity type id
	 * @return int, the attribute set id
	 */
	public function getAttributeSetId($attributeSetName, $entityTypeId)
	{
		return (int) Mage::getModel('eav/entity_attribute_set')->getCollection()
			->addFieldToFilter('entity_type_id', $entityTypeId)
			->addFieldToFilter('attribute_set_name', $attributeSetName)
			->load()
			->getFirstItem()
			->getAttributeSetId();
	}

	/**
	 * checking if an attribute exists in known attribute set
	 * @param string $attribute, the attribute name to check
	 * @param string $attributeSet, the attribute set name to check the attribute in
	 * @return bool, true when attribute exists in attribute set otherwise false
	 */
	public function isAttributeInAttributeSet($attribute, $attributeSet)
	{
		return (bool) Mage::getModel('eav/entity_attribute')
			->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute)
			->setAttributeSetId($this->getAttributeSetId($attributeSet, $this->getProductEntityTypeId()))
			->loadEntityAttributeIdBySet()
			->getEntityAttributeId();
	}

	/**
	 * getting attribute id by attribute name
	 * @param string $attribute, the attribute name
	 * @return int, the attribute id
	 */
	public function getAttributeId($attribute)
	{
		return (int) Mage::getModel('eav/entity_attribute')
			->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute)
			->getAttributeId();
	}

	/**
	 * add attributes to attribute set
	 * @param string $attribute, the attribute name to add
	 * @param string $attributeSet, the attribute set name to add the attribute to
	 * @return self
	 */
	public function addAttributeToAttributeSet($attribute, $attributeSet)
	{
		try{
			Mage::getModel('catalog/product_attribute_set_api')->attributeAdd(
				$this->getAttributeId($attribute),
				$this->getAttributeSetId($attributeSet, $this->getProductEntityTypeId()),
				self::DEFAULT_ATTRIBUTE_SET_GROUP,
				self::DEFAULT_SORT_ORDER
			);
		} catch (Mage_Api_Exception $e) {
			Mage::log(
				sprintf(
					'[%s] the following exception was thrown %s, while attempting to add attribute "%s" to attribute set "%s"',
					__METHOD__, $e->getMessage(), $attribute, $attributeSet
				),
				Zend_Log::DEBUG
			);
		}

		return $this;
	}
}

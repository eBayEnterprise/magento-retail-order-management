<?php
class TrueAction_Eb2cProduct_Overrides_Block_Catalog_Product_Attribute_Set_Main
	extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Set_Main
{
	/**
	 * Retrieve Attribute Set Group Tree as JSON format
	 *
	 * @return string
	 */
	public function getGroupTreeJson()
	{
		$items = array();
		$setId = $this->_getSetId();

/*		git list of default attributes that have a group
*/
		/* @var $groups Mage_Eav_Model_Mysql4_Entity_Attribute_Group_Collection */
		$groups = Mage::getModel('eav/entity_attribute_group')
			->getResourceCollection()
			->setAttributeSetFilter($setId)
			->setSortOrder()
			->load();

		$configurable = Mage::getResourceModel('catalog/product_type_configurable_attribute')
			->getUsedAttributes($setId);

		/* @var $node Mage_Eav_Model_Entity_Attribute_Group */
		foreach ($groups as $node) {
			$item = array();
			$item['text']       = $node->getAttributeGroupName();
			$item['id']         = $node->getAttributeGroupId();
			$item['cls']        = 'folder';
			$item['allowDrop']  = true;
			$item['allowDrag']  = true;

			/*
			filter attribute list by group
			 */
			$nodeChildren = Mage::getResourceModel('catalog/product_attribute_collection')
				->setAttributeGroupFilter($node->getId())
				->addVisibleFilter()
				->checkConfigurableProducts()
				->load();

			if ($nodeChildren->getSize() > 0) {
				$item['children'] = array();
				foreach ($nodeChildren->getItems() as $child) {
					/* @var $child Mage_Eav_Model_Entity_Attribute */
					$attr = array(
						'text'              => $child->getAttributeCode(),
						'id'                => $child->getAttributeId(),
						'cls'               => (!$child->getIsUserDefined()) ? 'system-leaf' : 'leaf',
						'allowDrop'         => false,
						'allowDrag'         => true,
						'leaf'              => true,
						'is_user_defined'   => $child->getIsUserDefined(),
						'is_configurable'   => (int)in_array($child->getAttributeId(), $configurable),
						'entity_id'         => $child->getEntityAttributeId()
					);
					/*
					if child is in the filtered list of default attributes for the group
						remove it from the default attributes code list
					*/

					$item['children'][] = $attr;
				}
			}
			$items[] = $item;
			/*
			foreach of the remaining codes in list for this group
				create and attach the attribute to the attribute set.
				create array elements to add to the $items array.
			*/
		}

		return Mage::helper('core')->jsonEncode($items);
	}

	/**
	 * Retrieve Unused in Attribute Set Attribute Tree as JSON
	 *
	 * @return string
	 */
	public function getAttributeTreeJson()
	{
		$items = array();
		$setId = $this->_getSetId();

		/*
		get list of default attributes that have no group.
		 */

		$collection = Mage::getResourceModel('catalog/product_attribute_collection')
			->setAttributeSetFilter($setId)
			->load();

		$attributesIds = array('0');
		/* @var $item Mage_Eav_Model_Entity_Attribute */
		foreach ($collection->getItems() as $item) {
			$attributesIds[] = $item->getAttributeId();
		}

		$attributes = Mage::getResourceModel('catalog/product_attribute_collection')
			->setAttributesExcludeFilter($attributesIds)
			->addVisibleFilter()
			->load();

		foreach ($attributes as $child) {
			/*
			if child is not in the list
				remove it
			 */
			$attr = array(
				'text'              => $child->getAttributeCode(),
				'id'                => $child->getAttributeId(),
				'cls'               => 'leaf',
				'allowDrop'         => false,
				'allowDrag'         => true,
				'leaf'              => true,
				'is_user_defined'   => $child->getIsUserDefined(),
				'is_configurable'   => false,
				'entity_id'         => $child->getEntityId()
			);
			$items[] = $attr;
		}

		if (count($items) == 0) {
			$items[] = array(
				'text'      => Mage::helper('catalog')->__('Empty'),
				'id'        => 'empty',
				'cls'       => 'folder',
				'allowDrop' => false,
				'allowDrag' => false,
			);
		}
		/*
		foreach attribute left in the list.
			add the attribute to the set
		 */
		return Mage::helper('core')->jsonEncode($items);
	}
}

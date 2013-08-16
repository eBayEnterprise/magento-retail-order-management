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
		Mage::log('getGroupTreeJson called');
		$items = array();
		$setId = $this->_getSetId();

		/* @var $groups Mage_Eav_Model_Mysql4_Entity_Attribute_Group_Collection */
		$groups = Mage::getModel('eav/entity_attribute_group')
			->getResourceCollection()
			->setAttributeSetFilter($setId)
			->setSortOrder()
			->load();

		$configurable = Mage::getResourceModel('catalog/product_type_configurable_attribute')
			->getUsedAttributes($setId);

		// get default attribute config as an array
		// use array keys to get the list of attributes to add
		// create a temporary group cache

		/* @var $node Mage_Eav_Model_Entity_Attribute_Group */
		foreach ($groups as $node) {
			$item = array();
			$item['text']       = $node->getAttributeGroupName();
			$item['id']         = $node->getAttributeGroupId();
			$item['cls']        = 'folder';
			$item['allowDrop']  = true;
			$item['allowDrag']  = true;

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
					// add the group to the cache as $naode->getattributegroupname() => group model
					// if $child->getEntityTypeId() === attributes->_getDefaultEntityTypeId()
						// if isset config[$child->getAttributeCode()]
								// remove it from the default attributes code list

					$item['children'][] = $attr;
				}
			}
			$items[] = $item;

		}
		// foreach of the remaining codes in list for this group
			// create empty attribute model
			// update model
				// get default data
				// update the data array
					// for each field/value pair in attribute config
						// map field to data array key
						// apply value to array element by key
			// save the model
		return Mage::helper('core')->jsonEncode($items);
	}
}

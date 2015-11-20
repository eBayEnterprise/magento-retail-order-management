<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Catalog_Helper_Map_Attribute extends Mage_Core_Helper_Abstract
{
    const TURN_OFF_MANAGE_STOCK_XML = '<Item><SalesClass>advanceOrderOpen</SalesClass></Item>';
    const SALES_CLASS_NODE = 'SalesClass';

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
     * Color is a set of nodes - a Code (as the color is known to ROM) and one or more
     * Description nodes, each node's value a different language.
     *
     * @param DOMNodeList $nodeList DOM nodes extracted from the feed
     * @param $product
     * @return int - the OptionId added or updated | null - the attribute does not exist
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function extractColorValue(DOMNodeList $nodeList, $product)
    {
        return $this->addAdminMappedOption('color', 'Code', 'Description', $nodeList);
    }

    /**
     * Size is a set of nodes - a Code (as the size is known to ROM) and one or more
     * Description nodes, each node's value a different language.
     *
     * @param DOMNodeList $nodeList DOM nodes extracted from the feed
     * @return int - the OptionId added or updated | null - the attribute does not exist
     */
    public function extractSizeValue(DOMNodeList $nodeList)
    {
        return $this->addAdminMappedOption('size', 'Code', 'Description', $nodeList);
    }

    /**
     * Extract the item status which may be one of several possible options.
     * If an invalid option is encountered, do not change the existing value.
     *
     * @param DOMNodeList
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    public function extractItemStatus(DOMNodeList $nodeList, Mage_Catalog_Model_Product $product)
    {
        return $this->_getAttributeOptionId('item_status', Mage::helper('eb2ccore')->extractNodeVal($nodeList)) ?: $product->getItemStatus();
    }

    /**
     * Extract the catalog class which may be one of several possible options.
     * If an invalid option is encountered, do not change the existing value.
     *
     * @param DOMNodeList
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    public function extractCatalogClass(DOMNodeList $nodeList, Mage_Catalog_Model_Product $product)
    {
        return $this->_getAttributeOptionId('catalog_class', Mage::helper('eb2ccore')->extractNodeVal($nodeList)) ?: $product->getCatalogClass();
    }

    /**
     * get a list of option from a given attribute code and store id
     * @param string $attributeCode the attribute code (color, size)
     * @param string $optionValue
     * @return int list of label/value
     */
    protected function _getAttributeOptionId($attributeCode, $optionValue)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Option */
        $option = $this->_getAttributeOptionModel($attributeCode, $optionValue);
        return (!is_null($option))? (int) $option->getOptionId() : 0;
    }

    /**
     * Search and get the first eav entity attribute option model from a collection
     * by attribute code and option value.
     *
     * @param  string
     * @param  string
     * @return Mage_Eav_Model_Entity_Attribute_Option | null
     */
    protected function _getAttributeOptionModel($attributeCode, $optionValue)
    {
        return Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($this->_getAttributeIdByName($attributeCode))
            ->addFieldToFilter('tdv.value', $optionValue)
            ->setStoreFilter(Mage_Core_Model_App::ADMIN_STORE_ID)
            ->setPageSize(1)
            ->getFirstItem();
    }

    /**
     * Normalize the raw style id. Prepends the catalog id to the style id.
     * @param sting $styleId
     * @return string
     */
    protected function _normalizeStyleId($styleId)
    {
        return Mage::helper('ebayenterprise_catalog')->normalizeSku($styleId, Mage::helper('eb2ccore')->getConfigModel()->catalogId);
    }

    /**
     * given a DOMNodeList and a Mage_Catalog_Model_Product make sure this is
     * a product of type configurable and then extract the configurable attribute from the node list
     * then get the configurable attribute array for each configured attributes
     * @param DOMNOdeList $nodes the node with configurableAttributes data
     * @param Mage_Catalog_Model_Product $product
     * @return array | null an array of configurable attribute data if the given product is configurable otherwise null
     */
    public function extractConfigurableAttributesData(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        // We are ensuring that the given product is a parent configurable product by first checking if the product sku
        // doesn't match the product style id. If this condition is met then we know we have a child product and we won't
        // proceed otherwise we know we have a parent product and proceed continue.
        if ($product->getSku() !== $this->_normalizeStyleId($product->getStyleId())) {
            return null;
        }
        $typeInstance = $product->getTypeInstance(true);
        // making sure the right type instance is set on the product
        if (!$typeInstance instanceof Mage_Catalog_Model_Product_Type_Configurable) {
            $product->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                ->setTypeInstance(Mage_Catalog_Model_Product_Type::factory($product, true), true);
        }

        $data = null; // purposely setting this to null just in cause all the attribute already exists
        // we need to know which configurable attribute we already have for this product
        // so that we don't try to create the same super attribute relationship which will
        // cause unique key duplication SQL constraint to be thrown
        $existedData = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        foreach (explode(',', strtolower(Mage::helper('eb2ccore')->extractNodeVal($nodes))) as $attributeCode) {
            // if we don't currently have a super attribute relationship then get the
            // configurable attribute data
            if (!$this->_isSuperAttributeExists($existedData, $attributeCode) && $this->_isAttributeInSet($attributeCode, $product)) {
                $data[] = $this->_getConfiguredAttributeData($attributeCode);
            }
        }
        // At this point we know we are dealing with a configurable product; therefore,
        // this is the right place to make sure manage stock get turn off.
        $this->_turnOffManageStock($product);
        return $data;
    }
    /**
     * Turn off manage stock for configurable products because they may not be in ItemMaster
     * feed.
     * @param Mage_Catalog_Model_Product $product
     * @return self
     */
    protected function _turnOffManageStock(Mage_Catalog_Model_Product $product)
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(static::TURN_OFF_MANAGE_STOCK_XML);
        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
        Mage::helper('ebayenterprise_catalog/map_stock')->extractStockData(
            $xpath->query(static::SALES_CLASS_NODE, $doc->documentElement),
            $product
        );
        return $this;
    }

    /**
     * return true if the is in a product's attribute set
     *
     * @param $attributeCode
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function _isAttributeInSet($attributeCode, $product)
    {
        $attrs = $product->getTypeInstance(true)->getSetAttributes($product);
        return array_key_exists($attributeCode, $attrs);
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
    /**
     * This function creates options that can be mapped in and out of ROM. It assigns the value
     * found at mapCodeNodeName to the ADMIN Value. The description values found at descriptionNodeName
     * are then assigned to all stores using the description's language.
     * @param string $attributeCode Magento attribute_code
     * @param string $mapCodeNodeName The node name at which we find the value that must map to ROM. Should be 1 and only 1.
     * @param string $descriptionNodeName The (maybe >1) node names at which we find the language-specific values
     * @param DOMNodeList $nodeList as pulled from a feed
     * @return int The newly updated or added attributeOptionId
     */
    public function addAdminMappedOption($attributeCode, $mapCodeNodeName, $descriptionNodeName, DOMNodeList $nodeList)
    {
        $xpath            = new DOMXPath($nodeList->item(0)->ownerDocument);
        $valueCode        = $xpath->query("./$mapCodeNodeName", $nodeList->item(0))->item(0)->nodeValue;
        $translationNodes = $xpath->query("./$descriptionNodeName", $nodeList->item(0));
        $translations     = array();
        foreach ($translationNodes as $node) {
            $translations[$node->getAttribute('xml:lang')] = $node->nodeValue;
        }
        return $this->_setOptionValues($attributeCode, $valueCode, $translations);
    }

    /**
     * Set the translations (values) for a specific option id. The option id is identified by adminValueText
     *
     * @param string $attributeCodeText a product attribute that has options; e.g. color, size, etc.
     * @param string $adminValueText This is the Code we received for this option. It is used as the default option value,
     *  and is used to retrieve the option
     * @param $translations array Option values indexed by a language code, e.g. array['en-us'] = 'English Text'.
     * @return int the created/ updated Attribute Option's Id
     */
    protected function _setOptionValues($attributeCodeText, $adminValueText, $translations)
    {
        $attributeId = $this->_getAttributeIdByName($attributeCodeText);
        if (!$attributeId) {
            return 0; // _getAttributeIdByName guarantees us non-zero value for valid attributes.
        }
        $attributeOptions = []; // An array holding the format Magento wants for options
        /** @var Mage_Eav_Model_Entity_Attribute_Option */
        $option = $this->_getAttributeOptionModel($attributeCodeText, $adminValueText);
        $attributeOptionId = $option ? (int) $option->getOptionId() : 0;
        $attribute         = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);

        $attributeOptions['attribute_id'] = $attributeId;
        /*
		attributeOptionId deserves special attention, as Magento will act differently according to whether it's 0 or not.
		It will be 0 if the option matching adminValueText is not found - this signals that we must add it.
		Otherwise, it will be an int > 0.

		The options array must be constructed exactly the same either way:
		New:
			attributeOptions['value'][0][Mage_Core_Model_App::ADMIN_STORE] = 'ROM code for this option';
			attributeOptions['value'][0][store1] = 'text for store1';
			attributeOptions['value'][0][store2] = 'text for store2';
		Existing:
			attributeOptions['value'][144][Mage_Core_Model_App::ADMIN_STORE] = 'ROM code for this option';
			attributeOptions['value'][144][store1] = 'text for store1';
			attributeOptions['value'][144][store2] = 'text for store2';

		Despite the fact that they are constructed exactly the same way, they must be added or updated via
		different methods.

		The other keys in attributeOptions are:
			attributeOptions['delete'] = attributeOptionIdToDelete (and all values along with it)
			attributeOptions['order'] = the sort order for the option

		Most of this was gleaned from Mage_Eav_Model_Resource_Entity_Attribute::_saveOption
		*/
        // Get existing values, if any, because update will overwrite everything.
        $attributeOptions['value'][$attributeOptionId] = $this->_getAllOptionValues($attribute, $attributeOptionId);
        $attributeOptions['order'][$attributeOptionId] = $option ? $option->getSortOrder() : 0;
        // The Default (always stored at Admin) Value
        $attributeOptions['value'][$attributeOptionId][Mage_Core_Model_App::ADMIN_STORE_ID] = $adminValueText;
        foreach ($translations as $lang => $desc) {
            // For each language, update all stores using that language
            foreach (Mage::helper('eb2ccore/languages')->getStores($lang) as $store) {
                $attributeOptions['value'][$attributeOptionId][$store->getId()] = $desc;
            }
        }
        if ($attributeOptionId) {
            // Previously existing attributeOption is being updated:
            $attribute->addData(['option' => $attributeOptions])->save();
        } else {
            // New attributeOption is being added:
            Mage::getModel('eav/entity_setup', 'core_setup')->addAttributeOption($attributeOptions);
            $attributeOptionId = $this->_getAttributeOptionId($attributeCodeText, $adminValueText);
        }
        return $attributeOptionId;
    }

    /**
     * Returns an array keyed by store id, each holding that store's value for the given option id
     * Return empty array if there's no option id
     *
     * @param $attribute
     * @param $attributeOptionId
     * @return array
     */
    protected function _getAllOptionValues($attribute, $attributeOptionId)
    {
        $allOptionValues = array();
        if ($attributeOptionId) {
            $defaultOptionValueText = $this->_getOptionValueText($attribute, $attributeOptionId);
            $stores = Mage::helper('eb2ccore/languages')->getStores();
            foreach ($stores as $store) {
                $storeId = $store->getId();
                $optionValueText = $this->_getOptionValueText($attribute, $attributeOptionId, $storeId);
                if ($optionValueText !== $defaultOptionValueText) {
                    $allOptionValues[$storeId] = $optionValueText;
                }
            }
        }
        return $allOptionValues;
    }
    /**
     * Returns the text of the attributeOption for the given store
     *
     * @param string $attribute a Magento Attribute object
     * @param string $attributeOptionId the id of the option whose text value we want
     * @param int $storeId which store view we want
     * @return string
     */
    protected function _getOptionValueText($attribute, $attributeOptionId, $storeId = Mage_Core_Model_App::ADMIN_STORE_ID)
    {
        return $attribute->setStoreId($storeId)->getSource()->getOptionText($attributeOptionId);
    }
}

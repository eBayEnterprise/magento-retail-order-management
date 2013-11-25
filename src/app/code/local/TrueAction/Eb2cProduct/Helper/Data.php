<?php
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	private $_customAttributeCodeSets = array();

	protected $_types;
	/**
	 * @var array boilerplate for initializing a new product with limited information.
	 */
	protected $_prodTplt;
	/**
	 * @return array the static defaults for a new product
	 */
	protected function _getProdTplt()
	{
		if (!$this->_prodTplt) {
			$cfg = $this->getConfigModel();
			if (!$this->hasProdType($cfg->dummyTypeId)) {
				throw new TrueAction_Eb2cProduct_Model_Config_Exception('Config Error: dummy type id is invalid.');
			}
			$defStockData = array(
				'is_in_stock'  => $cfg->dummyInStockFlag,
				'manage_stock' => $cfg->dummyManageStockFlag,
				'qty'          => (int) $cfg->dummyStockQuantity,
			);
			$this->_prodTplt = array(
				'attribute_set_id'  => (int) $this->_getDefProdAttSetId(),
				'category_ids'      => array($this->_getDefStoreRootCatId()),
				'description'       => $cfg->dummyDescription,
				'price'             => (float) $cfg->dummyPrice,
				'short_description' => $cfg->dummyShortDescription,
				'status'            => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
				'stock_data'        => $defStockData,
				'store_ids'         => array($this->_getDefStoreId()),
				'tax_class_id'      => (int) $cfg->dummyTaxClassId,
				'type_id'           => $cfg->dummyTypeId,
				'visibility'        => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
				'website_ids'       => $this->_getAllWebsiteIds(),
				'weight'            => (int) $cfg->dummyWeight,
			);
		}
		return $this->_prodTplt;
	}
	/**
	 * @return array all website ids
	 */
	protected function _getAllWebsiteIds()
	{
		return (array) Mage::getModel('core/website')->getCollection()->getAllIds();
	}
	/**
	 * @return int the default store id
	 */
	protected function _getDefStoreId()
	{
		return (int) Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();
	}
	/**
	 * @return int the root category id for the default store
	 */
	protected function _getDefStoreRootCatId()
	{
		return (int) Mage::app()->getStore()->getRootCategoryId();
	}
	/**
	 * @return int the default attribute set id for all products.
	 */
	protected function _getDefProdAttSetId()
	{
		return (int) Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getDefaultAttributeSetId();
	}

	/**
	 * @return string the default locale language code 
	 */
	public function getDefaultLanguageCode()
	{
		return Mage::helper('eb2ccore')->mageToXmlLangFrmt(Mage::app()->getLocale()->getLocaleCode());
	}

	/**
	 * @return int default attribute set id; possibly un-necessary function @_@
	 */
	public function getDefaultProductAttributeSetId()
	{
		return $this->_getDefProdAttSetId();
	}

	/**
	 * Parse a string into a boolean.
	 * @param string $s the string to parse
	 * @return bool
	 */
	public function parseBool($s)
	{
		if (!is_string($s)) {
			return (bool) $s;
		}
		switch (strtolower($s)) {
			case '1':
			case 'on':
			case 't':
			case 'true':
			case 'y':
			case 'yes':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Get Product config instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getModel('eb2cproduct/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * @return bool true if the eav config has at least one instance of the given attribute.
	 * @param string $attr
	 */
	public function hasEavAttr($at)
	{
		return 0 < (int) Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Product::ENTITY, $at)
			->getId();
	}

	/**
	 * Get a catalog product eav attribute id for the attribute identified by
	 * the given code.
	 * @param  string $attributeCode Attribute code for the product attribute
	 * @return int                   ID of the attribute
	 */
	public function getProductAttributeId($attributeCode)
	{
		return Mage::getModel('eav/entity_attribute')
			->loadByCode('catalog_product', $attributeCode)
			->getId();
	}

	/**
	 * @return bool true if Magento knows about the product type.
	 * @param string $type
	 */
	public function hasProdType($type)
	{
		if (!$this->_types) {
			$this->_types = array_keys(Mage_Catalog_Model_Product_Type::getTypes());
		}
		return in_array($type, $this->_types);
	}

	/**
	 * extract node value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 */
	public function extractNodeVal(DOMNodeList $nodeList)
	{
		return ($nodeList->length)? $nodeList->item(0)->nodeValue : null;
	}

	/**
	 * extract node attribute value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 */
	public function extractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		return ($nodeList->length)? $nodeList->item(0)->getAttribute($attributeName) : null;
	}

	/**
	 * get a model loaded with the data for $sku if it exists;
	 * otherwise, get a new _UNSAVED_ model populated with dummy data.
	 * @param  string $sku
	 * @return Mage_Catalog_Model_Product
	 */
	public function prepareProductModel($sku, $name='')
	{
		$product = $this->loadProductBySku($sku);
		if (!$product->getId()) {
			$this->_applyDummyData($product, $sku, $name);
		}
		return $product;
	}

	/**
	 * Fill a product model with dummy data so that it can be saved and edited later.
	 * @see http://www.magentocommerce.com/boards/viewthread/289906/
	 * @param Mage_Catalog_Model_Product $prod product model to be autofilled
	 * @param string $sku the new product's sku
	 * @param string $name the new product's name
	 * return Mage_Catalog_Model_Product
	 */
	protected function _applyDummyData(Mage_Catalog_Model_Product $prod, $sku, $name)
	{
		$prodData = $this->_getProdTplt();
		$prodData['name'] = $name ?: "Invalid Product: $sku";
		$prodData['sku'] = $prodData['url_key'] = $sku;
		return $prod->addData($prodData);
	}
	/**
	 * load product by sku
	 * @param string $sku, the product sku to filter the product table
	 * @return Mage_Catalog_Model_Product
	 */
	public function loadProductBySku($sku)
	{
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*');
		$products->getSelect()
			->where('e.sku = ?', $sku);
		return $products->getFirstItem();
	}

	/**
	 * Return an array of attribute_codes
	 * @return array
	 */
	public function getCustomAttributeCodeSet($attributeSetId)
	{
		if( empty($this->_customAttributeCodeSets[$attributeSetId]) ) {
			$codeSet = array();
			$attributeSet = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);
			foreach ($attributeSet as $attribute) {
				$codeSet[]=$attribute['code'];
			}
			$this->_customAttributeCodeSets[$attributeSetId] = $codeSet;
		}
		return $this->_customAttributeCodeSets[$attributeSetId];
	}

	/**
	 * Flattens translations into arrays keyed by language
	 * @return array in the form a['lang-code'] = 'localized value'
	 */
	public function parseTranslations($languageSet)
	{
		$parsedLanguages = array();
		if (!empty($languageSet)) {
			foreach ($languageSet as $language) {
				$parsedLanguages[$language['lang']] = $language['description'];
			}
		}
		return $parsedLanguages;
	}
}

<?php
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	private $_types;

	/**
	 * convert a string into a boolean value.
	 * @see  http://php.net/manual/en/function.is-bool.php
	 * @param   string
	 * @return  string
	 */
	public function convertToBoolean($value)
	{
		return in_array(
			strtolower($value),
			array('true', '1', 'on', 'yes', 'y'),
			true
		) ? '1' : '0';
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
	public function prepareProductModel($sku, $type='simple')
	{
		$product = $this->loadProductBySku($sku);
		if (!$product->getId()) {
			$this->applyDummyData($product, $sku);
			if ($type !== 'simple') {
				$product->setData('type_id', $type);
			}
		}
		return $product;
	}

	/**
	 * fill a product model with dummy data so that it can be saved and edited later
	 * @see http://www.magentocommerce.com/boards/viewthread/289906/
	 * @param  Mage_Catalog_Model_Product $product product model to be autofilled
	 * @return Mage_Catalog_Model_Product
	 */
	public function applyDummyData($product, $sku)
	{
		try{
			$product->setData(
				array(
					'type_id' => 'simple', // default product type
					'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
					'attribute_set_id' => $product->getResource()->getEntityType()->getDefaultAttributeSetId(),
					'name' => 'Invalid Product: ' . $sku,
					'status' => 0, // default - disabled
					'sku' => $sku,
					'website_ids' => $this->getAllWebsiteIds(),
					'category_ids' => $this->_getDefaultCategoryIds(),
					'description' => 'This product is invalid. If you are seeing this product, please do not attempt to purchase and contact customer service.',
					'short_description' => 'Invalid product. Please do not attempt to purchase.',
					'price' => 0,
					'weight' => 0,
					'url_key' => $sku,
					'store_ids' => array($this->getDefaultStoreId()),
					'stock_data' => array('is_in_stock' => 1, 'qty' => 999, 'manage_stock' => 1),
					'tax_class_id' => 0,
				)
			);
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] Failed to apply dummy data to product: %s',
					__CLASS__,
					$e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $product;
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
	 * @return array list containing the integer id for the root-category of the default store
	 * @codeCoverageIgnore
	 * No coverage needed since this is almost all external code.
	 */
	protected function _getDefaultCategoryIds()
	{
		$storeId = $this->getDefaultStoreId();
		return array(Mage::app()->getStore($storeId)->getRootCategoryId());
	}

	public function getAllWebsiteIds()
	{
		return Mage::getModel('core/website')->getCollection()->getAllIds();
	}

	public function getDefaultStoreId()
	{
		return null;
	}
}

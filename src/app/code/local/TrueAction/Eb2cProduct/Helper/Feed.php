<?php
class TrueAction_Eb2cProduct_Helper_Feed
{
	/**
	 * fill a product model with dummy data so that it can be saved and edited later
	 * @see http://www.magentocommerce.com/boards/viewthread/289906/
	 * @param  Mage_Catalog_Model_Product $product product model to be autofilled
	 * @return Mage_Catalog_Model_Product
	 */
	public function applyDummyData($product, $sku)
	{
		try{
			$product->setId(null)
				->addData(
					array(
						'type_id' => 'simple', // default product type
						'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
						'attribute_set_id' => $this->getDefaultAttributeSetId(),
						'name' => 'Invalid Product: ' . $sku,
						'status' => 0, // default - disabled
						'sku' => $sku,
						'website_ids' => $this->_getAllWebsiteIds(),
						'category_ids' => $this->_getDefaultCategoryIds(),
						'description' => 'This product is invalid. If you are seeing this product, please do not attempt to purchase and contact customer service.',
						'short_description' => 'Invalid product. Please do not attempt to purchase.',
						'price' => 0,
						'weight' => 0,
						'url_key' => $sku,
						'store_ids' => array($this->_getDefaultStoreId()),
						'stock_data' => array('is_in_stock' => 1, 'qty' => 999, 'manage_stock' => 1),
						'tax_class_id' => 0,
					)
				);
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf('[ %s ] The following error has occurred while creating dummy product for iShip Feed (%d)',	__CLASS__, $e->getMessage()),
				Zend_Log::ERR
			);
		}

		return $product;
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

	protected function _getAllWebsiteIds()
	{
		return Mage::getModel('core/website')->getCollection()->getAllIds();
	}

	protected function _getDefaultStoreId()
	{
		return null;
	}
}

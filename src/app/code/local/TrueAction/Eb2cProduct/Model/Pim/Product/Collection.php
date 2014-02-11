<?php

class TrueAction_Eb2cProduct_Model_Pim_Product_Collection
	extends Varien_Data_Collection
{
	// Format string used to format item ids
	const ID_FORMAT = '%s-%s-%s';
	/**
	 * Crate an item id string from a sku, client id and catalog id per the
	 * formatting defined in the ID_FORMAT format string.
	 * @param  string $sku
	 * @param  string $clientId
	 * @param  string $catalogId
	 * @return string
	 */
	protected function _formatId($sku, $clientId, $catalogId)
	{
		return sprintf(static::ID_FORMAT, $sku, $clientId, $catalogId);
	}
	/**
	 * Get the item id for an item based on the item's sku, client id
	 * and catalog id.
	 * @param  Varien_Object $item
	 * @return string
	 */
	protected function _getItemId(Varien_Object $item)
	{
		return $this->_formatId(
			$item->getSku(),
			$item->getClientId(),
			$item->getCatalogId()
		);
	}
	/**
	 * Get an item in the collection for the given product using the sku
	 * of the product and the configured client id and catalog id for the store
	 * the product was loaded within the context of.
	 * @param  Mage_Catalog_Model_Product $product
	 * @return TrueAction_Eb2cProduct_Model_Pim_Product
	 */
	public function getItemForProduct(Mage_Catalog_Model_Product $product)
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel($product->getStore());
		return $this->getItemById(
			$this->_formatId(
				$product->getSku(),
				$cfg->clientId,
				$cfg->catalogId
			)
		);
	}
	/**
	 * Get the first item in the collection. As the inherited behavior of creating
	 * a new empty item when the collection is empty cannot be achieved here,
	 * (@see self::getNewEmptyItem) this method will throw an exception if called
	 * on an empty collection.
	 * @return TrueAction_Eb2cProduct_Pim_Product
	 * @throws TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception If collection is empty
	 */
	public function getFirstItem()
	{
		if (empty($this->_items)) {
			throw new TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception(
				sprintf('%s cannot get item from an empty collection', __METHOD__)
			);
		}
		return parent::getFirstItem();
	}
	/**
	 * Get the last item in the collection. As the inherited behavior of creating
	 * a new empty item when the collection is empty cannot be achieved here,
	 * (@see self::getNewEmptyItem) this method will throw an exception if called
	 * on an empty collection.
	 * @return TrueAction_Eb2cProduct_Pim_Product
	 * @throws TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception If collection is empty
	 */
	public function getLastItem()
	{
		if (empty($this->_items)) {
			throw new TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception(
				sprintf('%s cannot get item from an empty collection', __METHOD__)
			);
		}
		return parent::getLastItem();
	}
	/**
	 * The items represented by this collection cannot be instantiated as empty
	 * items so this method cannot be implemented for this collection.
	 * @throws TrueAction_Eb2cCore_Exception_NotImplemented Always
	 */
	public function getNewEmptyItem()
	{
		throw new TrueAction_Eb2cCore_Exception_NotImplemented(
			sprintf('%s is not implemented', __METHOD__)
		);
	}
}

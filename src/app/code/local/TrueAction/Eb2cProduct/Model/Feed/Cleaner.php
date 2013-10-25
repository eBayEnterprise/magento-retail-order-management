<?php
/**
 * "Second pass" of the product feed import.
 * Responsible for finding any products that have incomplete
 * product links and updating them. This class should update
 * upsell, related, cross-sell links as well as configurable
 * product links.
 */
class TrueAction_Eb2cProduct_Model_Feed_Cleaner
{

	/**
	 * Go through all products that need to be cleaned and clean them.
	 * @return $this object
	 */
	public function cleanAllProducts()
	{

	}

	/**
	 * Get a collection of products that need to be cleaned.
	 * @return Mage_Catalog_Model_Resource_Product_Collection Collection of "dirty" products.
	 */
	public function getProductsToClean()
	{

	}

	/**
	 * Update any product links.
	 * @param  Mage_Catalog_Model_Product $product [description]
	 * @return [type]                              [description]
	 */
	public function cleanProduct(Mage_Catalog_Model_Product $product)
	{

	}

	/**
	 * Determine if the product has been sufficiently cleaned.
	 * @param  Mage_Catalog_Model_Product $product Product to check
	 * @return boolean                             Is the product clean
	 */
	public function isProductClean(Mage_Catalog_Model_Product $product)
	{

	}

}

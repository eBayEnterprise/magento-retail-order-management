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

	// map eb2c link types to Magento product link data attribute
	protected $_linkTypes = array(
		'related' => array(
			'data_attribute' => 'related_link_data',
			'linked_products_getter_method' => 'getRelatedProducts',
		),
		'upsell' => array(
			'data_attribute' => 'up_sell_link_data',
			'linked_products_getter_method' => 'getUpSellProducts',
		),
		'crosssell' => array(
			'data_attribute' => 'cross_sell_link_data',
			'linked_products_getter_method' => 'getCrossSellProducts',
		),
	);

	/**
	 * Go through all products that need to be cleaned and clean them.
	 * @return $this object
	 */
	public function cleanAllProducts()
	{
		foreach ($this->getProductsToClean() as $product) {
			$this->cleanProduct($product);
		}
		return $this;
	}

	/**
	 * Get a collection of products that need to be cleaned.
	 * @return Mage_Catalog_Model_Resource_Product_Collection Collection of "dirty" products.
	 */
	public function getProductsToClean()
	{
		return Mage::getModel('catalog/product')->getCollection()
			->addFieldToFilter('is_clean', false)
			// @todo - trim this down to only the necessary attributes
			->addAttributeToSelect('*');
	}

	/**
	 * Update any product links.
	 * @param  Mage_Catalog_Model_Product $product [description]
	 * @return $this object
	 */
	public function cleanProduct(Mage_Catalog_Model_Product $product)
	{
		// resolved any hanging linked products
		$this->_resolveProductLinks($product);
		// update configurable relationships
		if ($product->getTypeId() === 'configurable') {
			$this->_addUsedProducts($product);
		} else if ($product->getSku() !== $product->getStyleId()) {
			$this->_addToCofigurableProduct($product);
		}
		$product->setIsClean($this->isProductClean($product));
		$product->save();
		return $this;
	}

	/**
	 * Resolved linked products - related, up sell, cross sell
	 * @param  Mage_Catalog_Model_Product $product Product to add links for
	 * @return $this object
	 */
	protected function _resolveProductLinks(Mage_Catalog_Model_Product $product)
	{
		// array(
		//   array(
		//     'link_type' => 'related|upsell|crosssell',
		//     'operation_type' => 'Add|Delete',
		//     'link_to_unique_id' => 'sku'
		//   )
		// )
		$missingLinks = unserialize($product->getUnresolvedProductLinks());
		if (!empty($missingLinks)) {
			// keep track of links that are not resolved
			$unresolvedLinks = array();
			foreach (array_keys($this->_linkTypes) as $linkType) {
				$unresolvedLinks = array_merge(
					$unresolvedLinks,
					$this->_linkProducts(
						$product,
						array_filter($missingLinks, function ($el) use ($linkType) { return $linkType === $el['link_type']; }),
						$linkType
					)
				);
			}
			$product->setUnresolvedProductLinks(empty($unresolvedLinks) ? '' : serialize($unresolvedLinks));
		}
		return $this;
	}

	/**
	 * Update product links for the given type of links - up sell, related, cross sell
	 * @param  Mage_Catalog_Model_Product $product     Product to add links to
	 * @param  array                      $linkUpdates Product links data
	 * @param  string                     $linkType    Type of product links to create
	 * @return array                                   Any links that could not be added
	 */
	protected function _linkProducts(Mage_Catalog_Model_Product $product, $linkUpdates, $linkType)
	{
		$opFilter = function ($operation) {
			return function ($el) use ($operation) { return strtolower($el['operation_type']) === $operation; };
		};
		$skuMap = function ($link) {
			return $link['link_to_unique_id'];
		};

		// get list of skus to add and remove
		$addSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('add')));
		$deleteSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('delete')));

		// get all currently linked products of this link type
		$linkedProductsGetter = $this->_linkTypes[$linkType]['linked_products_getter_method'];
		$linkedProducts = $product->$linkedProductsGetter();

		// remove any links that are supposed to be getting deleted
		$linkedProducts = array_filter(
			$linkedProducts,
			function ($prod) use ($deleteSkus) { return !in_array($prod->getSku(), $deleteSkus); }
		);

		$helper = Mage::helper('eb2cproduct');
		// get a list of all products that should be linked
		$linkIds = array_filter(array_unique(array_merge(
			array_map(function ($prod) { return $prod->getId(); }, $linkedProducts),
			// @todo - what happens when this lookup fails
			array_map(function ($sku) use ($helper) { return $helper->loadProductBySku($sku)->getId(); }, $addSkus)
		)));

		$linkData = array();
		foreach ($linkIds as $id) {
			$linkData[$id] = array('position' => '');
		}

		// add the updated links to the product
		$product->setData($this->_linkTypes[$linkType]['data_attribute'], $linkData);
		return array();
	}

	/**
	 * Add products used to configure this product.
	 * @param Mage_Catalog_Model_Product $product Configurable parent product to add children to.
	 */
	protected function _addUsedProducts(Mage_Catalog_Model_Product $product)
	{

	}

	/**
	 * Simple product that needs to be added as a configuration to a configurable product.
	 * @param Mage_Catalog_Model_Product $product Simple product used to configure a configurable product.
	 */
	protected function _addToCofigurableProduct(Mage_Catalog_Model_Product $product)
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

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
		$productsToClean = $this->getProductsToClean();
		Mage::log(sprintf('[ %s ]: Cleaning %d products.', __CLASS__, $productsToClean->count()));
		foreach ($productsToClean as $product) {
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
			// @todo - order products are processed may be optimizable
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
		} elseif ($product->getStyleId() && $product->getStyleId() !== $product->getSku()) {
			$this->_addToCofigurableProduct($product);
		}
		$this->markProductClean($product);
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

		// get all currently linked products of this link type
		$linkedProductsGetter = $this->_linkTypes[$linkType]['linked_products_getter_method'];
		$linkedProducts = $product->$linkedProductsGetter();

		// remove any links that are supposed to be getting deleted
		$deleteSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('delete')));
		$linkedProducts = array_filter(
			$linkedProducts,
			function ($prod) use ($deleteSkus) { return !in_array($prod->getSku(), $deleteSkus); }
		);

		$addSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('add')));

		// Go through the skus to add and look up the product id for each one.
		// If any are missing, add the product link for that sku to a list of links that
		// cannot be resolved yet.
		$missingLinks = array();
		$idsToAdd = array();
		$helper = Mage::helper('eb2cproduct');
		foreach ($addSkus as $sku) {
			$id = $helper->loadProductBySku($sku)->getId();
			if ($id) {
				$idsToAdd[] = $id;
			} else {
				$missingLinks[] = $this->_buildProductLinkForSku($sku, $linkType, 'Add');
			}
		}

		// get a list of all products that should be linked
		$linkIds = array_filter(array_unique(array_merge(
			array_map(function ($prod) { return $prod->getId(); }, $linkedProducts),
			$idsToAdd
		)));

		$linkData = array();
		foreach ($linkIds as $id) {
			$linkData[$id] = array('position' => '');
		}

		// add the updated links to the product
		$product->setData($this->_linkTypes[$linkType]['data_attribute'], $linkData);
		// return links that were not resolved
		return $missingLinks;
	}

	/**
	 * Build out the product link map for a given sku.
	 * @param  string $sku       Sku the link should point to
	 * @param  string $type      Type of link to create
	 * @param  string $operation Type of operation, "Add" or "Delete"
	 * @return array             Map of product link data
	 */
	protected function _buildProductLinkForSku($sku, $type, $operation)
	{
		return array(
			'link_to_unique_id' => $sku,
			'link_type' => $type,
			'operation_type' => $operation
		);
	}

	/**
	 * Add products used to configure this product.
	 * @param Mage_Catalog_Model_Product $product Configurable parent product to add children to.
	 * @return $this object
	 */
	protected function _addUsedProducts(Mage_Catalog_Model_Product $product)
	{
		// look up used products by style_id, will match sku of configurable product
		$addProductIds = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('style_id', array('eq' => $product->getSku()))
			->addFieldToFilter('entity_id', array('neq' => $product->getId()))
			->getAllIds();
		// merge the found products with any existing links, filtering out any duplicates
		$existingIds = $product->getTypeInstance()->getUsedProductIds();
		$usedProductIds = array_unique(array_merge($existingIds, $addProductIds));
		// only update used products when there are new products to add to the list
		if (array_diff($existingIds, $usedProductIds)) {
			Mage::getResourceModel('catalog/product_type_configurable')
				->saveProducts($product, array_unique($usedProductIds));
			$product
				->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				->save();
		} elseif (empty($usedProductIds)) {
			Mage::log(
				sprintf('[ %s ]: Expected to find products to use for configurable product %s but found none.', __CLASS__, $product->getSku()),
				Zend_Log::DEBUG
			);
		}
		return $this;
	}

	/**
	 * Simple product that needs to be added as a configuration to a configurable product.
	 * @param Mage_Catalog_Model_Product $product Simple product used to configure a configurable product.
	 * @return $this object
	 */
	protected function _addToCofigurableProduct(Mage_Catalog_Model_Product $product)
	{
		$configurableProduct = Mage::helper('eb2cproduct')->loadProductBySku($product->getStyleId());
		if ($configurableProduct->getId()) {
			$usedProductIds   = $configurableProduct->getTypeInstance()->getUsedProductIds();
			$usedProductIds[] = $product->getId();
			Mage::getResourceModel('catalog/product_type_configurable')
				->saveProducts($configurableProduct, array_unique($usedProductIds));
			// @todo: Could this be done more efficiently? Test if it's not already enabled?
			$configurableProduct
				->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				->save();
		} else {
			Mage::log(
				sprintf(
					'[ %s ]: Expected to find configurable product with sku %s to add product with sku %s to but no such product found',
					__CLASS__, $product->getStyleId(), $product->getSku()
				),
				Zend_Log::DEBUG
			);
		}
		return $this;
	}

	/**
	 * Determine if the product has been sufficiently cleaned.
	 * @param  Mage_Catalog_Model_Product $product Product to check
	 * @return $this object
	 */
	public function markProductClean(Mage_Catalog_Model_Product $product)
	{
		$isClean = false;
		// lingering unresolved links will need to be checked again in a later pass, considered dirty
		$unresolvedLinks = unserialize($product->getUnresolvedProductLinks());
		$isClean = empty($unresolvedLinks);

		// update flag on product
		$product->setIsClean(Mage::helper('eb2cproduct')->parseBool($isClean));
		if (!$isClean) {
			Mage::log(sprintf('[ %s ]: Product, %s, has not be fully cleaned.', __CLASS__, $product->getSku()), Zend_Log::DEBUG);
		}
		return $this;
	}

}

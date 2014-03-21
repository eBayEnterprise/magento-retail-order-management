<?php

class TrueAction_Eb2cProduct_Model_Pim
{
	/**
	 * name of the document element node
	 */
	const DOCUMENT_NODE_NAME = 'MageMaster';
	/**
	 * document object used when building the feed contents
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc;
	/**
	 * core feed model used to handle the file system
	 * @var TrueAction_Eb2cCore_Model_Feed
	 */
	protected $_coreFeed;
	/**
	 * collection of PIM Product instances
	 * @var TrueAction_Eb2cProduct_Model_Pim_Product_Collection
	 */
	protected $_pimProducts;
	/**
	 * Set up the doc and core feed
	 */
	public function __construct(array $initParams=array())
	{
		if (isset($initParams['doc']) && !$initParams['doc'] instanceof TrueAction_Dom_Document) {
			trigger_error(
				sprintf('%s called with invalid doc. Must be instance of TrueAction_Dom_Document', __METHOD__),
				E_USER_ERROR
			);
		}
		$this->_doc = isset($initParams['doc']) ? $initParams['doc'] : Mage::helper('eb2ccore')->getNewDomDocument();
		if (isset($initParams['core_feed']) && !$initParams['core_feed'] instanceof TrueAction_Eb2cCore_Model_Feed) {
			trigger_error(
				sprintf('%s called with invalid core feed. Must be instance of TrueAction_Eb2cCore_Model_Feed', __METHOD__),
				E_USER_ERROR
			);
		}
		$this->_coreFeed = isset($initParams['core_feed']) ? $initParams['core_feed'] : $this->_setUpCoreFeed();
	}
	/**
	 * generate the outgoing product feed.
	 * @param  array  $productIds list of product id's to include in the feed.
	 * @return string             full filepath where the feed file was saved.
	 */
	public function buildFeed(array $productIds)
	{
		$feedDataSet = $this->_createFeedDataSet($productIds);
		$feedDoc = $this->_createDomFromFeedData($feedDataSet);
		$feedFilePath = $this->_getFeedFilePath();
		$feedDoc->save($feedFilePath);
		return $feedFilePath;
	}
	/**
	 * Build all PIM Product instances used to build the feed.
	 * @param  array  $productIds entity id's of products
	 * @return TrueAction_Eb2cProduct_Model_Pim_Product_Collection collection of PIM Product instances
	 */
	protected function _createFeedDataSet(array $productIds)
	{
		$pimProducts = Mage::getModel('eb2cproduct/pim_product_collection');
		foreach (Mage::helper('eb2ccore/languages')->getStores() as $store) {
			$pimProducts = $this->_processProductCollection(
				$this->_createProductCollectionForStore($productIds, $store),
				$pimProducts
			);
		}
		return $pimProducts;
	}
	/**
	 * build out the dom document with the supplied feed data.
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Product_Collection $feedData collection of PIM Product instances
	 * @return TrueAction_Dom_Document                             dom document representing the feed
	 */
	protected function _createDomFromFeedData(TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts)
	{
		$this->_startDocument();
		$pimRoot = $this->_doc->documentElement;
		foreach ($pimProducts->getItems() as $pimProduct)
		{
			$itemFragment = $this->_buildItemNode($pimProduct);
			if ($itemFragment->hasChildNodes()) {
				$pimRoot->appendChild($itemFragment);
			}
		}
		$this->_validateDocument();
		return $this->_doc;
	}
	/**
	 * setup a collection of products for a specific store.
	 * @param  array                              $productIds list of product entity id's
	 * @param  Mage_Core_Model_Store              $store      magento store
	 * @return Mage_Catalog_Model_Resource_Product_Collection product collection
	 */
	protected function _createProductCollectionForStore(array $productIds, Mage_Core_Model_Store $store)
	{
		return Mage::getResourceModel('catalog/product_collection')
			->setStore($store)
			->addExpressionAttributeToSelect('pim_language_code', "('" . $store->getLanguageCode() . "')", array())
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('in' => $productIds));
	}
	/**
	 * Process all of the products within a given store.
	 * @param  Mage_Catalog_Model_Resource_Product_Collection      $products    products for a specific store
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 * @return TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 */
	protected function _processProductCollection(
		Mage_Catalog_Model_Resource_Product_Collection $products,
		TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts
	) {
		$config = Mage::helper('eb2cproduct')->getConfigModel($products->getStoreId());
		$clientId = $config->clientId;
		$catalogId = $config->catalogId;
		foreach ($products->getItems() as $product) {
			$pimProduct = $pimProducts->getItemForProduct($product);
			if (!$pimProduct) {
				$pimProduct = Mage::getModel('eb2cproduct/pim_product', array(
					'client_id' => $clientId, 'catalog_id' => $catalogId, 'sku' => $product->getSku()
				));
				$pimProducts->addItem($pimProduct);
			}
			$pimProduct->loadPimAttributesByProduct($product, $this->_doc);
		}
		return $pimProducts;
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Product $pimProduct
	 * @return DOMDocumentFragment
	 */
	protected function _buildItemNode(TrueAction_Eb2cProduct_Model_Pim_Product $pimProduct)
	{
		$fragment = $this->_doc->createDocumentFragment();
		$itemNode = $fragment->appendChild($this->_doc->createElement('Item'));
		$itemNode->addAttributes(array(
			'gsi_client_id' => $pimProduct->getClientId(), 'catalog_id' => $pimProduct->getCatalogId()
		));
		$attributes = $pimProduct->getPimAttributes();
		sort($attributes, SORT_STRING);
		foreach (array_unique($attributes) as $pimAttribute) {
			$this->_appendAttributeValue($itemNode, $pimAttribute);
		}
		return $fragment;
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given TrueAction_Dom_Element.
	 * @param  TrueAction_Dom_Element                     $itemNode     container node
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Attribute $pimAttribute attribute value model
	 * @return self
	 */
	protected function _appendAttributeValue(
		TrueAction_Dom_Element $itemNode,
		TrueAction_Eb2cProduct_Model_Pim_Attribute $pimAttribute
	) {
		$attributeNode = $itemNode->setNode($pimAttribute->destinationXpath);
		$attributeNode->appendChild($this->_doc->importNode($pimAttribute->value, true));
		if ($pimAttribute->language) {
			$attributeNode->addAttributes(array('xml:lang' => $pimAttribute->language));
		}
		return $this;
	}
	/**
	 * validate the dom against the configured xsd.
	 * @return self
	 */
	protected function _validateDocument()
	{
		Mage::getModel('eb2ccore/api')
			->schemaValidate($this->_doc, Mage::helper('eb2cproduct')->getConfigModel()->pimExportXsd);
		return $this;
	}
	/**
	 * set up the core feed model need to manage directories.
	 * @return TrueAction_Eb2cCore_Model_Feed core feed model
	 */
	protected function _setUpCoreFeed()
	{
		return Mage::getModel('eb2ccore/feed', array(
			'feed_config' => Mage::helper('eb2cproduct')->getConfigModel()->pimExportFeed
		));
	}
	/**
	 * generate the full file path for the outbound feed file.
	 * @return string file path
	 */
	protected function _getFeedFilePath()
	{
		$helper = Mage::helper('eb2cproduct');
		return $this->_coreFeed->getLocalDirectory() . DS .
			$helper->generateFileName(
				self::DOCUMENT_NODE_NAME,
				$helper->getConfigModel()->pimExportFilenameFormat
			);
	}
	/**
	 * setup the document with the root node and the message header.
	 * @return self
	 */
	protected function _startDocument()
	{
		$this->_doc->loadXml(sprintf(
			'<%1$s imageDomain="%3$s">%2$s</%1$s>',
			self::DOCUMENT_NODE_NAME,
			Mage::helper('eb2cproduct')->generateMessageHeader('PIMExport'),
			Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
		));
		return $this;
	}
}

<?php

class TrueAction_Eb2cProduct_Model_Pim
{
	const PIM_CONFIG_PATH = 'eb2cproduct/feed_pim_mapping';
	const XML_TEMPlATE = '<%1$s xmlns:xsi="%2$s" xsi:schemaLocation="%3$s">%4$s</%1$s>';
	const XMLNS = 'http://www.w3.org/2001/XMLSchema-instance';

	const KEY_EVENT_TYPE = 'event_type';
	const KEY_SCHEMA_LOCATION = 'schema_location';
	const KEY_ROOT_NODE = 'root_node';
	const KEY_FILE_PATTERN = 'file_pattern';
	const KEY_MAPPINGS = 'mappings';
	const KEY_IS_VALIDATE = 'is_validate';
	const KEY_ITEM_NODE = 'item_node';

	const KEY_DOCS = 'docs';
	const KEY_CORE_FEED = 'core_feed';

	const ERROR_INVALID_DOC = '%s called with invalid doc. Must be instance of TrueAction_Dom_Document';
	const ERROR_INVALID_CORE_FEED = '%s called with invalid core feed. Must be instance of TrueAction_Eb2cCore_Model_Feed';

	/**
	 * document object used when building the feed contents
	 * @var array of TrueAction_Dom_Document
	 */
	protected $_docs = array();
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
	 * @var array map between event type and feed root node
	 */
	protected $_feedsMap = array();
	/**
	 * Set up the doc and core feed
	 */
	public function __construct(array $initParams=array())
	{

		if (isset($initParams[self::KEY_DOCS]) && empty($initParams[self::KEY_DOCS])) {
			Mage::helper('eb2ccore')->triggerError(sprintf(self::ERROR_INVALID_DOC, __METHOD__));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_docs = isset($initParams[self::KEY_DOCS]) ? $initParams[self::KEY_DOCS] : $this->_getFeedDoms();
		if (isset($initParams[self::KEY_CORE_FEED]) && !$initParams[self::KEY_CORE_FEED] instanceof TrueAction_Eb2cCore_Model_Feed) {
			Mage::helper('eb2ccore')->triggerError(sprintf(self::ERROR_INVALID_CORE_FEED, __METHOD__));
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		$this->_coreFeed = isset($initParams[self::KEY_CORE_FEED]) ?
			$initParams[self::KEY_CORE_FEED] :
			$this->_setUpCoreFeed();
	}
	/**
	 * getting an array of DOMDocument object per event type
	 * @return array
	 */
	protected function _getFeedDoms()
	{
		$helper = Mage::helper('eb2ccore');
		$docs = array();
		foreach (array_keys($this->_getFeedsMap()) as $key) {
			$docs[$key] = $helper->getNewDomDocument();
		}
		return $docs;
	}
	/**
	 * @see self::_feedsMap caching the mapping in this class property
	 * @return array
	 */
	protected function _getFeedsMap()
	{
		if (empty($this->_feedsMap)) {
			$this->_feedsMap = Mage::helper('eb2ccore/feed')->getConfigData(self::PIM_CONFIG_PATH);
		}

		return $this->_feedsMap;
	}
	/**
	 * generate the outgoing product feed.
	 * @param  array  $productIds list of product id's to include in the feed.
	 * @return array             array of full filepath where the feed files was saved.
	 */
	public function buildFeed(array $productIds)
	{
		$feedFilePath = array();
		foreach (array_keys($this->_getFeedsMap()) as $key) {
			$feedDataSet = $this->_createFeedDataSet($productIds, $key);
			$feedDoc = $this->_createDomFromFeedData($feedDataSet, $key);
			$feedFilePath[$key] = $this->_getFeedFilePath($key);
			$feedDoc->save($feedFilePath[$key]);
		}
		return $feedFilePath;
	}
	/**
	 * Build all PIM Product instances used to build the feed.
	 * @param  array  $productIds entity id's of products
	 * @param  string $key
	 * @return TrueAction_Eb2cProduct_Model_Pim_Product_Collection collection of PIM Product instances
	 */
	protected function _createFeedDataSet(array $productIds, $key)
	{
		$pimProducts = Mage::getModel('eb2cproduct/pim_product_collection');
		foreach (Mage::helper('eb2ccore/languages')->getStores() as $store) {
			$pimProducts = $this->_processProductCollection(
				$this->_createProductCollectionForStore($productIds, $store),
				$pimProducts,
				$key
			);
		}
		return $pimProducts;
	}
	/**
	 * build out the dom document with the supplied feed data.
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Product_Collection $feedData collection of PIM Product instances
	 * @param  string $key
	 * @return TrueAction_Dom_Document                    dom document representing the feed
	 */
	protected function _createDomFromFeedData(TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts, $key)
	{
		$this->_startDocument();
		$doc = $this->_docs[$key];
		$map = $this->_getFeedsMap();

		$pimRoot = $doc->documentElement;
		$itemNode = $map[$key][self::KEY_ITEM_NODE];
		foreach ($pimProducts->getItems() as $pimProduct) {
			$itemFragment = $this->_buildItemNode($pimProduct, $itemNode, $key);
			if ($itemFragment->hasChildNodes()) {
				$pimRoot->appendChild($itemFragment);
			}
		}
		if (Mage::helper('eb2cproduct')->parseBool($map[$key][self::KEY_IS_VALIDATE])) {
			$this->_validateDocument($key);
		}

		return $doc;
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
	 * @param  string $key
	 * @return TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts collection of PIM Product instances
	 */
	protected function _processProductCollection(
		Mage_Catalog_Model_Resource_Product_Collection $products,
		TrueAction_Eb2cProduct_Model_Pim_Product_Collection $pimProducts, $key
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

			$pimProduct->loadPimAttributesByProduct($product, $this->_docs[$key], $key, $this->_getFeedAttributes($key));
		}
		return $pimProducts;
	}
	/**
	 * given a key in the feed configuration maps get all the mapping attributes
	 * @param string $key
	 * @return array
	 */
	protected function _getFeedAttributes($key)
	{
		$map = $this->_getFeedsMap();
		return array_keys($map[$key][self::KEY_MAPPINGS]);
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Product $pimProduct
	 * @param string $childNode
	 * @param string $key
	 * @return DOMDocumentFragment
	 */
	protected function _buildItemNode(TrueAction_Eb2cProduct_Model_Pim_Product $pimProduct, $childNode, $key)
	{
		$fragment = $this->_docs[$key]->createDocumentFragment();
		$itemNode = $fragment->appendChild($this->_docs[$key]->createElement($childNode));
		$attributes = $pimProduct->getPimAttributes();
		sort($attributes, SORT_STRING);
		foreach (array_unique($attributes) as $pimAttribute) {
			$this->_appendAttributeValue($itemNode, $pimAttribute, $key);
		}
		return $fragment;
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given TrueAction_Dom_Element.
	 * @param  TrueAction_Dom_Element                     $itemNode     container node
	 * @param  TrueAction_Eb2cProduct_Model_Pim_Attribute $pimAttribute attribute value model
	 * @param string $key
	 * @return self
	 */
	protected function _appendAttributeValue(
		TrueAction_Dom_Element $itemNode,
		TrueAction_Eb2cProduct_Model_Pim_Attribute $pimAttribute, $key
	) {
		if ($pimAttribute->value instanceof DOMAttr) {
			$itemNode->setAttribute($pimAttribute->value->name, $pimAttribute->value->value);
		} elseif ($pimAttribute->value instanceof DOMNode) {
			$attributeNode = $itemNode->setNode($pimAttribute->destinationXpath);
			$attributeNode->appendChild($this->_docs[$key]->importNode($pimAttribute->value, true));
			if ($pimAttribute->language) {
				$attributeNode->addAttributes(array('xml:lang' => $pimAttribute->language));
			}
		}
		return $this;
	}
	/**
	 * validate the dom against the configured xsd.
	 * @param string $key
	 * @return self
	 */
	protected function _validateDocument($key)
	{
		Mage::getModel('eb2ccore/api')
			->schemaValidate($this->_docs[$key], Mage::helper('eb2cproduct')->getConfigModel()->pimExportXsd);
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
	 * @param string $key
	 * @return string file path
	 */
	protected function _getFeedFilePath($key)
	{
		$helper = Mage::helper('eb2cproduct');
		$map = $this->_getFeedsMap();
		return $this->_coreFeed->getLocalDirectory() . DS .
			$helper->generateFileName($map[$key][self::KEY_EVENT_TYPE], $map[$key][self::KEY_FILE_PATTERN]);
	}
	/**
	 * setup the document with the root node and the message header.
	 * @return self
	 */
	protected function _startDocument()
	{
		foreach ($this->_getFeedsMap() as $key => $map) {
			$this->_docs[$key]->loadXml(sprintf(
				self::XML_TEMPlATE,
				$map[self::KEY_ROOT_NODE],
				self::XMLNS,
				$map[self::KEY_SCHEMA_LOCATION],
				Mage::helper('eb2cproduct')->generateMessageHeader($map[self::KEY_EVENT_TYPE]),
				Mage::helper('eb2ccore')->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
			));
		}
		return $this;
	}
}

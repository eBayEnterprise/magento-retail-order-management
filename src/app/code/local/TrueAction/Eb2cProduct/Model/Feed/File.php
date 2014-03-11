<?php

class TrueAction_Eb2cProduct_Model_Feed_File
{
	/**
	 * hold the deleted XSLT stylesheet template file
	 */
	const XSLT_DELETED_SKU = 'delete-template.xsl';

	/**
	 * the root XPath to query individual SKU node
	 */
	const DELETED_BASE_XPATH = 'sku';

	/**
	 * hold the default language XSLT stylesheet template file
	 */
	const XSLT_DEFAULT_TEMPLATE_PATH = 'default-language-template.xsl';

	/**
	 * hold the single language XSLT stylesheet template file
	 */
	const XSLT_SINGLE_TEMPLATE_PATH = 'single-language-template.xsl';

	/**
	 * XPath expression used to chunk the feed file into separate items
	 */
	const BASE_ITEM_XPATH = '/Items/Item';

	/**
	 * XPath expression to get all SKUs in a feed file
	 */
	const ALL_SKUS_XPATH = '/Items/Item/ItemId/ClientItemId|/Items/Item/UniqueID|/Items/Item/ClientItemId';

	/**
	 * Array of information about the feed file to be processed. Expected to be
	 * passed to the constructor and *must* contain the following keys:
	 * 'error_file' => filename of the file used for the error confirmation response feed
	 * 'doc' => TrueAction_Dom_Document with the file to be processed loaded in
	 * @var array
	 */
	protected $_feedDetails;

	/**
	 * @var array hold an list of sku to be imported
	 */
	protected $_importedSkus = array();
	/**
	 * The Mage factory method only allows passing a single array of data as
	 * arguments to constructors. As the constructor requires certain data to be
	 * included in the array of data, treat the required keys as much like actual
	 * function arguments as possible - ensure they are all included and if any
	 * are missing, trigger an error indicating the missing keys.
	 * Array data must include the following keys:
	 * 'doc' and 'error_file'
	 * @see self::_feedDetails
	 * @param  array $feedDetails
	 */
	public function __construct(array $feedDetails)
	{
		if ($missingKeys = array_diff(array('doc', 'error_file'), array_keys($feedDetails))) {
			trigger_error(
				sprintf('%s called without required feed details: %s missing.', __METHOD__, implode(', ', $missingKeys)),
				E_USER_ERROR
			);
		}
		if (!$feedDetails['doc'] instanceof TrueAction_Dom_Document) {
			trigger_error(
				sprintf('%s called with invalid doc. Must be instance of TrueAction_Dom_Document', __METHOD__),
				E_USER_ERROR
			);
		}
		$this->_feedDetails = $feedDetails;
	}
	/**
	 * @see self::_feedDetails
	 * @return TrueAction_Dom_Document
	 * @codeCoverageIgnore
	 */
	public function getDoc()
	{
		return $this->_feedDetails['doc'];
	}
	/**
	 * @see self::_feedDetails
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getErrorFile()
	{
		return $this->_feedDetails['error_file'];
	}

	/**
	 * Process the file - making necessary deletes and adds/updates.
	 * @return self
	 */
	public function process()
	{
		$this->deleteProducts();

		$siteFilters = Mage::helper('eb2cproduct')->loadWebsiteFilters();
		foreach($siteFilters as $siteFilter) {
			$this->_importedSkus = array();
			$this->processWebsite($siteFilter)->processTranslations($siteFilter);
		}

		Mage::dispatchEvent(
			'product_feed_process_operation_type_error_confirmation',
			array('feed_detail' => $this->_feedDetails, 'skus' => $this->_importedSkus, 'operation_type' => 'import')
		);
		return $this;
	}
	/**
	 * Get a list of SKUs marked for deletion in the feed, load all products to
	 * delete into a single collection and delete the collection.
	 * @return self
	 */
	public function deleteProducts()
	{
		$skus = $this->_getSkusToDelete();
		Mage::log(sprintf('[%s] deleting %d skus', __CLASS__, count($skus)), Zend_Log::DEBUG);
		if (!empty($skus)) {
			Mage::getResourceModel('catalog/product_collection')->addFieldToFilter('sku', $skus)
				->addAttributeToSelect(array('entity_id'))
				->load()
				->delete();

			Mage::dispatchEvent(
				'product_feed_process_operation_type_error_confirmation',
				array('feed_detail' => $this->_feedDetails, 'skus' => $skus, 'operation_type' => 'delete')
			);
		}
		return $this;
	}
	/**
	 * Get an array of SKUs to be deleted from the feed file being processed.
	 * @return array Array of SKUs to delete
	 */
	protected function _getSkusToDelete()
	{
		$result = array();
		$dlDoc = Mage::helper('eb2cproduct')->splitDomByXslt($this->getDoc(), $this->_getXsltPath(self::XSLT_DELETED_SKU));
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($dlDoc);
		foreach ($xpath->query(self::DELETED_BASE_XPATH, $dlDoc->documentElement) as $sku) {
			$result[] = $sku->nodeValue;
		}
		return $result;
	}
	/**
	 * Get an array of SKUs included in the given DOMXPath.
	 * @param  DOMXPath $xpath
	 * @return array
	 */
	protected function _getSkusToUpdate(DOMXPath $xpath)
	{
		if (empty($this->_importedSkus)) {
			$updateSkuNodes = $xpath->query(self::ALL_SKUS_XPATH);
			foreach ($updateSkuNodes as $skuNode) {
				$this->_importedSkus[] = $skuNode->nodeValue;
			}
		}
		return $this->_importedSkus;
	}
	/**
	 * Get the path to the given XSLT template. Methods assumes all XSLTs are
	 * in an XSLT directory within the eb2cproduct module directory.
	 * @param  string $templateName File name of the XSLT
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getXsltPath($templateName)
	{
		return Mage::getModuleDir('', 'TrueAction_Eb2cProduct') . DS . 'xslt' . DS . $templateName;
	}
	/**
	 * Get a new DOMDocument including only the data that should be set for a given
	 * language using the specified XSLT.
	 * @param string $languageCode
	 * @param string $template
	 * @return TrueAction_Dom_Document
	 */
	protected function _splitByLanguageCode($languageCode, $template)
	{
		return Mage::helper('eb2cproduct')->splitDomByXslt(
			$this->getDoc(),
			$this->_getXsltPath($template),
			array('lang_code' => $languageCode)
		);
	}
	/**
	 * Get a new DOMDocument including only the data that should be set for a given
	 * language using the specified XSLT.
	 * @param array $websiteFilter 
	 * @param string $template
	 * @return TrueAction_Dom_Document
	 */
	protected function _splitByFilter($websiteFilter, $template)
	{
		return Mage::helper('eb2cproduct')->splitDomByXslt(
			$this->getDoc(),
			$this->_getXsltPath($template),
			array('lang_code' => $websiteFilter['lang_code']),
			array($this, 'xslCallBack'), // Call Back to massage XSL after initial load
			$websiteFilter // Site Context
		);
	}
	/**
	 * load a product collection base on a given set of product data and apply
	 * the product data to the collection and then save
	 * product data is expected to have known SKU in order to load the collection
	 * @param DOMDocument $productDataDoc
	 * @param int $storeId
	 * @return self
	 */
	protected function _importExtractedData(DOMDocument $productDataDoc, $storeId)
	{
		$feedXPath = Mage::helper('eb2ccore')->getNewDomXPath($productDataDoc);
		$productCollection = $this->_buildProductCollection($this->_getSkusToUpdate($feedXPath));
		$productCollection->setStore($storeId);
		foreach ($feedXPath->query(self::BASE_ITEM_XPATH) as $itemNode) {
			$this->_updateItem($feedXPath, $itemNode, $productCollection);
		}
		Mage::log(sprintf('[%s] saving collection of %d products', __CLASS__, $productCollection->count()), Zend_Log::DEBUG);
		$productCollection->save();
		return $this;
	}
	/**
	 * Update a single product with data from the feed. Should check for the
	 * product to already exist in the collection and when it does, update the
	 * product in the collection. When the product doesn't exist yet, it should
	 * create a new product, set the extracted data on it and add it to the
	 * colleciton.
	 * @param  DOMXPath $feedXPath
	 * @param  DOMNode $itemNode
	 * @param  TrueAction_Eb2cProduct_Model_Resource_Feed_Product_Collection $productCollection
	 * @return self
	 */
	protected function _updateItem(
		DOMXPath $feedXPath,
		DOMNode $itemNode,
		TrueAction_Eb2cProduct_Model_Resource_Feed_Product_Collection $productCollection
	)
	{
		$extractor = Mage::getSingleton('eb2cproduct/feed_extractor');
		$helper = Mage::helper('eb2cproduct');
		$sku = $extractor->extractSku($feedXPath, $itemNode);
		$websiteId = Mage::getModel('core/store')->load($productCollection->getStoreId())->getWebsiteId();
		$product = $productCollection->getItemById($sku);
		if (is_null($product)) {
			$product = $helper->createNewProduct($sku);
			$product->setWebsiteIds(array($websiteId));
			$productCollection->addItem($product);
			Mage::log(sprintf('[%s] creating new product %s', __CLASS__, $sku), Zend_Log::DEBUG);
		}
		$product->setStoreId($productCollection->getStoreId());
		$webSiteIds = array_unique(array_merge($product->getWebsiteIds(), array($websiteId)));
		$product->setWebsiteIds($webSiteIds);
		$product->addData($extractor->extractItem($feedXPath, $itemNode, $product));
		return $this;
	}
	/**
	 * Get all languages configured in Magento and process product data for
	 * each language.
	 * @return self
	 */
	public function processTranslations()
	{
		Mage::log(sprintf('[%s] processing translation', __CLASS__), Zend_Log::DEBUG);
		foreach (Mage::helper('eb2ccore/languages')->getLanguageCodesList() as $language) {
			$this->processForLanguage($language);
		}
		return $this;
	}
	/**
	 * Process the feed for a single language by extracting data for the given
	 * language and then importing the data for each store view with that language.
	 * @param string $languageCode
	 * @return self
	 */
	public function processForLanguage($languageCode)
	{
		Mage::log(sprintf('[%s] processing %s language', __CLASS__, $languageCode), Zend_Log::DEBUG);
		$splitDoc = $this->_splitByLanguageCode(
			$languageCode,
			TrueAction_Eb2cProduct_Model_Feed_File::XSLT_SINGLE_TEMPLATE_PATH
		);
		foreach (Mage::helper('eb2ccore/languages')->getStores($languageCode) as $store) {
			// do not reprocess the default store
			$storeId = $store->getId();
			if ($storeId !== Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
				$this->_importExtractedData($splitDoc, $storeId);
			}
		}
		return $this;
	}
	/**
	 * Process the feed for this website. Should process the file using
	 * the default language XSLT and the language the default store view is
	 * configured for.
	 * @return self
	 */
	public function processWebsite($websiteFilter)
	{
		Mage::log(sprintf('[%s] processing %s', __METHOD__, print_r($websiteFilter,true)), Zend_Log::DEBUG);
		$mageStoreId = $websiteFilter['mage_store_id'];
		return $this->_importExtractedData(
			$this->_splitByFilter(
				$websiteFilter,
				TrueAction_Eb2cProduct_Model_Feed_File::XSLT_DEFAULT_TEMPLATE_PATH
			),
			$mageStoreId
		);
		return $this;
	}
	/**
	 * This is a callback; adds additional template handling for configurable variables that XSLT 1.0 just doesn't do.
	 * @return
	 */
	public function xslCallBack(DOMDocument $xslDoc, array $websiteFilter)
	{
		$helper = Mage::helper('eb2cproduct');
		foreach( array('Item', 'PricePerItem', 'Content') as $nodeToMatch) {
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@catalog_id and @catalog_id!='{$websiteFilter['catalog_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_client_id and @gsi_client_id!='{$websiteFilter['client_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_store_id and @gsi_store_id!='{$websiteFilter['store_id']}')]");
		}
		Mage::log(sprintf('[%s] XSLT now %s', __METHOD__, $xslDoc->saveXML()), Zend_Log::DEBUG);
		$xslDoc->loadXML($xslDoc->saveXML());
		return;
	}
	/**
	 * Create a product collection containing any product with a SKU in the
	 * given list. This will only load products that already exist in Magento.
	 * Ensure all attributes are loaded for products in the collection. This
	 * forces the object's origData to be properly populated with all existing
	 * data, which then ensures that:
	 * 1. attributes not set on a given pass are not wiped out
	 * 2. attributes set to the same value as the default are not duplicated
	 * @param  array $skus
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _buildProductCollection(array $skus=array())
	{
		return Mage::getResourceModel('eb2cproduct/feed_product_collection')
			// load all attributes to prevent previously set attributes from being
			// lost when saving updates to only some attributes
			->addAttributeToSelect(array('*'))
			->addAttributeToFilter(array(array('attribute' => 'sku', 'in' => $skus)))
			->load();
	}
}

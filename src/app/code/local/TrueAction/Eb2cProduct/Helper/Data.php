<?php
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * @var int, the default category id
	 */
	protected $_defaultParentCategoryId = null;

	/**
	 * @see self::getCustomAttributeCodeSet - method
	 */
	protected $_customAttributeCodeSets = array();
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
				'is_in_stock' => $cfg->dummyInStockFlag,
				'manage_stock' => $cfg->dummyManageStockFlag,
				'qty' => (int) $cfg->dummyStockQuantity,
			);
			$this->_prodTplt = array(
				'attribute_set_id' => (int) $this->_getDefProdAttSetId(),
				'category_ids' => array($this->_getDefStoreRootCatId()),
				'description' => $cfg->dummyDescription,
				'price' => (float) $cfg->dummyPrice,
				'short_description' => $cfg->dummyShortDescription,
				'status' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
				'stock_data' => $defStockData,
				'store_ids' => array($this->_getDefStoreId()),
				'type_id' => $cfg->dummyTypeId,
				'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
				'website_ids' => $this->_getAllWebsiteIds(),
				'weight' => (int) $cfg->dummyWeight,
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
	 * abstracting getting locale code
	 * @return string, the locale code
	 * @codeCoverageIgnore
	 */
	protected function _getLocaleCode()
	{
		return Mage::app()->getLocale()->getLocaleCode();
	}
	/**
	 * @return string the default locale language code
	 */
	public function getDefaultLanguageCode()
	{
		return Mage::helper('eb2ccore')->mageToXmlLangFrmt($this->_getLocaleCode());
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
	 * @param string $attributeCode Attribute code for the product attribute
	 * @return int id of the attribute
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
		$types = Mage_Catalog_Model_Product_Type::getTypes();
		return isset($types[$type]);
	}
	/**
	 * extract node value
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 */
	public function extractNodeVal(DOMNodeList $nodeList)
	{
		return ($nodeList->length) ? $nodeList->item(0)->nodeValue : null;
	}
	/**
	 * extract node attribute value
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 */
	public function extractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		return ($nodeList->length) ? $nodeList->item(0)->getAttribute($attributeName) : null;
	}
	/**
	 * get a model loaded with the data for $sku if it exists;
	 * otherwise, get a new _UNSAVED_ model populated with dummy data.
	 * @param string $sku
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
	 * instantiate new product object and apply dummy data to it
	 * @param string $sku
	 * @return Mage_Catalog_Model_Product
	 */
	public function createNewProduct($sku, $name='')
	{
		return $this->_applyDummyData(Mage::getModel('catalog/product'), $sku, $name);
	}

	/**
	 * Fill a product model with dummy data so that it can be saved and edited later.
	 * @see http://www.magentocommerce.com/boards/viewthread/289906/
	 * @param Mage_Catalog_Model_Product $prod product model to be autofilled
	 * @param string $sku the new product's sku
	 * @param string $name the new product's name
	 * @return Mage_Catalog_Model_Product
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
	 * @param string $sku product sku
	 * @param null|string|bool|int|Mage_Core_Model_Store $store magento store
	 * @return Mage_Catalog_Model_Product
	 */
	public function loadProductBySku($sku, $store=null)
	{
		return Mage::helper('catalog/product')->getProduct(
			$sku,
			$store,
			'sku'
		);
	}
	/**
	 * Return an array of attribute_codes
	 * @param int $attributeSetId
	 * @return array
	 */
	public function getCustomAttributeCodeSet($attributeSetId)
	{
		if( empty($this->_customAttributeCodeSets[$attributeSetId]) ) {
			$codeSet = array();
			$attributeSet = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);
			foreach ($attributeSet as $attribute) {
				$codeSet[] = $attribute['code'];
			}
			$this->_customAttributeCodeSets[$attributeSetId] = $codeSet;
		}
		return $this->_customAttributeCodeSets[$attributeSetId];
	}
	/**
	 * Flattens translations into arrays keyed by language
	 * @param array $languageSet
	 * @return array in the form a['lang-code'] = 'localized value'; emtpy array if $languageSet is null
	 */
	public function parseTranslations(array $languageSet=null)
	{
		$parsedLanguages = array();
		if (!empty($languageSet)) {
			foreach ($languageSet as $language) {
				$parsedLanguages[$language['lang']] = $language['description'];
			}
		}
		return $parsedLanguages;
	}
	/**
	 * Sets configurable_attributes_data
	 * @param string $productTypeId ('configurable', 'simple' etc).
	 * @param Varien_Object $source the source data field
	 * @param Mage_Catalog_Model_Product the product we are setting
	 * @return array of configurable_attributes_data
	 */
	public function getConfigurableAttributesData($productTypeId, Varien_Object $source, Mage_Catalog_Model_Product $product)
	{
		if ($product->getId()
				&& $product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
				&& $productTypeId === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
				&& $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product))
		{
			Mage::log('Can\'t change existing configurable attributes; update discarded for sku ' . $product->getSku(),
				Zend_log::WARN);
			return null;
		}
		return $source->getData('configurable_attributes_data');
	}

	/**
	 * mapped pattern element with actual values
	 * @param array $keyMap a composite array with map key value
	 * @param string $pattern the string pattern
	 */
	public function mapPattern(array $keyMap, $pattern)
	{
		return array_reduce(array_keys($keyMap), function($result, $key) use ($keyMap, $pattern) {
				$result = (trim($result) === '')? $pattern : $result;
				return str_replace(sprintf('{%s}', $key), $keyMap[$key], $result);
			});
	}

	/**
	 * generate file name by feed type
	 * @param string $feedType Type of feed to be processed
	 * @param string $format Filename format string to use to build the filename
	 * @return string the errorconfirmations file name
	 */
	public function generateFileName($feedType, $format)
	{
		return $this->mapPattern(
			Mage::helper('eb2ccore/feed')->getFileNameConfig($feedType),
			$format
		);
	}

	/**
	 * generate message header by feed type
	 * @param string $feedType
	 * @return string message header content xml nodes and child nodes
	 */
	public function generateMessageHeader($feedType)
	{
		$cfg = $this->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		return $this->mapPattern(Mage::helper('eb2ccore/feed')->getHeaderConfig($feedType), $cfg->feedHeaderTemplate);
	}

	/**
	 * Get the absolute path to the global processing directory set
	 * in configuration.
	 * @return string
	 */
	public function getProcessingDirectory()
	{
		$helper = Mage::helper('eb2ccore');
		$path = Mage::getBaseDir('var') . DS .  $this->getConfigModel()->feedProcessingDirectory;
		$helper->createDir($path);
		if (!$helper->isDir($path)){
			throw new TrueAction_Eb2cCore_Exception_Feed_File("Can not create the following directory (${path})");
		}
		return $path;
	}

	/**
	 * build the outbound file name by feed type
	 * @param string $feedType
	 * @return string
	 */
	public function buildErrorFeedFilename($feedType)
	{
		return $this->getProcessingDirectory() . DS .
			$this->generateFileName(
				$feedType,
				$this->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
					->errorFeedFilenameFormat
			);
	}

	/**
	 * get store root category id
	 * @return int store root category id
	 * @codeCoverageIgnore
	 */
	public function getStoreRootCategoryId()
	{
		return Mage::app()->getWebsite(true)->getDefaultStore()->getRootCategoryId();
	}

	/**
	 * abstracting getting an array of stores
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getStores()
	{
		return Mage::app()->getStores();
	}

	/**
	 * get the storeview language/country code (en-US, fr-FR)
	 * @param Mage_Core_Model_Store $store
	 * @return string|null
	 */
	public function getStoreViewLanguage(Mage_Core_Model_Store $store)
	{
		$storeCodeParsed = explode('_', $store->getName(), 3);
		if (count($storeCodeParsed) > 2) {
			return strtolower(Mage::helper('eb2ccore')->mageToXmlLangFrmt($storeCodeParsed[2]));
		}

		return null;
	}

	/**
	 * get parent default category id
	 * @return int default parent category id
	 */
	public function getDefaultParentCategoryId()
	{
		if (is_null($this->_defaultParentCategoryId)) {
			$this->_defaultParentCategoryId = Mage::getResourceModel('catalog/category_collection')
			->addAttributeToSelect('entity_id')
			->addAttributeToFilter('parent_id', array('eq' => 0))
			->getFirstItem()
			->getId();
		}
		return $this->_defaultParentCategoryId;
	}

	/**
	 * take dom document object and string xslt file template to transform
	 * whatever the passed in xslt file template transform the new doc to
	 * @param TrueAction_Dom_Document $doc the document got get the nodelist
	 * @param string $xsltFilePath the xslt stylesheet template file absolute fulle path
	 * @param array $params parameters for the xslt
	 * @param function $postXsltLoadCall function to be called after loading XSLT, but before Processing it
	 * @return TrueAction_Dom_Document
	 */
	public function splitDomByXslt(TrueAction_Dom_Document $doc, $xsltFilePath, array $params=array(), $postXsltLoadCall=null, $websiteFilter=array())
	{
		$helper = Mage::helper('eb2ccore');
		// create a DOMDocument for the xsl
		$xslDom = $helper->getNewDomDocument();
		$xslDom->load($xsltFilePath);
		if (is_callable($postXsltLoadCall)) {
			call_user_func($postXsltLoadCall, $xslDom, $websiteFilter);
		}
		// load the xsl document into a XSLProcessor
		$xslProcessor = $helper->getNewXsltProcessor();
		$xslProcessor->importStyleSheet($xslDom);
		$xslProcessor->setParameter('', $params);
		// create a DOMDocument from the transformed XML
		$transformed = $helper->getNewDomDocument();
		$transformed->loadXML($xslProcessor->transformToXML($doc));
		preg_match_all('/<ClientItemId ?.*>(.*)<\/ClientItemId>/', $transformed->saveXML(), $matches);
		Mage::log(sprintf("[%s] transformed, SKUs eligible: (%s)", __METHOD__, implode(", ", $matches[1])), Zend_Log::DEBUG);
		return $transformed;
	}

	/**
	 * Appends an <xsl:template match='' /> node to the XSLT DOM.
	 * XSLT 1.0 won't let us use a variable reference, we have to form the
	 * xpath as a string, and build a node and insert it.
	 * @param DOMDocument xslDoc an already loaded DOM
	 * @param string xpathExpression to match
	 * @return boolean true (node inserted), or false (node insert failed)
	 */
	function appendXslTemplateMatchNode($xslDoc, $xpathExpression)
	{
		$templateNode          = $xslDoc->createElement('xsl:template', '', 'http://www.w3.org/1999/XSL/Transform');
		$matchAttribute        = $xslDoc->createAttribute('match');
		$matchAttribute->value = $xpathExpression;
		$templateNode->appendChild($matchAttribute);
		$rc = $xslDoc->documentElement->insertBefore($templateNode);
		return $rc;
	}

	/**
	 * Loads a key/ value pair with the relevant config fields of each Magento Web Store which allows us
	 * to match an incoming feed to that specific destination.
	 *
	 * @return array of key/value pairs mapping an inbound feed to the given Magento Web Store.
	 */
	protected function _loadWebsiteFilter($mageStoreId)
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->setStore($mageStoreId)
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		return array (
			'catalog_id'      => $config->catalogId,
			'client_id'       => $config->clientId,
			'store_id'        => $config->storeId,
			'lang_code'       => $config->languageCode,
			'mage_store_id'   => $mageStoreId,
			'mage_website_id' => Mage::getModel('core/store')->load($mageStoreId)->getWebsiteId(),
		);
	}

	/**
	 * Loads the relevant config fields of each Magento Web Site that allows us
	 * to match an incoming feed to the appropriate destination.
	 *
	 * @return array of unique key/value pairs mapping an inbound feed to a Magento Web Site.
	 */
	public function loadWebsiteFilters()
	{
		$allWebsites = array();
		// Default Store it has its own special configuration.
		$allWebsites[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID] = $this->_loadWebSiteFilter(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				foreach ($group->getStores() as $store) {
					$allWebsites[$store->getId()] = $this->_loadWebsiteFilter($store->getId());
				}
			}
		}
		// We're keyed by Mage Store Id. But some Store Ids could point to the same incoming feed. We de-dupe to avoid processing twice.
		// Similarly, if every website uses the default store configuration, we have but one incoming-website-match to worry about.
		$uniqueSites = array_map("unserialize", array_unique(array_map("serialize", $allWebsites)));
		return $uniqueSites;
	}
}

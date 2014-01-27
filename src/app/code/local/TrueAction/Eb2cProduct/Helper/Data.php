<?php
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	const PATH_PATTERN = '%s/%s%s/';
	const ABSOLUTE_PATH_PATTERN = '%s%s';

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
	 * @var array hold map value between feed type and config feed local path
	 */
	protected $_feedTypeMap;
	/**
	 * @return array the feed type map config local path
	 */
	public function getFeedTypeMap()
	{
		if (!$this->_feedTypeMap) {
			$cfg = $this->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
			$this->_feedTypeMap = array(
				'ItemMaster' => array(
					'local_path' => $cfg->itemFeedLocalPath,
				),
				'Content' => array(
					'local_path' => $cfg->contentFeedLocalPath,
				),
				'iShip' => array(
					'local_path' => $cfg->iShipFeedLocalPath,
				),
				'Price' => array(
					'local_path' => $cfg->pricingFeedLocalPath,
				)
			);
		}
		return $this->_feedTypeMap;
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
	 * @param string $feedType known feed types are (ItemMaster, Content, iShip, Price, ImageMaster, ItemInventories)
	 * @return string the errorconfirmations file name
	 */
	public function generateFileName($feedType)
	{
		$cfg = $this->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		return $this->mapPattern(Mage::helper('eb2ccore/feed')->getFileNameConfig($feedType), $cfg->errorFeedFilePattern);
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
	 * generate file path by feed type, check if directory exists if not created the directory
	 * @param string $feedType known feed types are (ItemMaster, Content, iShip, Price, ImageMaster, ItemInventories)
	 * @param TrueAction_Eb2cProduct_Helper_Struct_Feedpath $dir
	 * @return string the file path
	 */
	public function generateFilePath($feedType, TrueAction_Eb2cProduct_Helper_Struct_Feedpath $dir)
	{
		$type = $this->getFeedTypeMap();
		if (!isset($type[$feedType])) {
			return '';
		}
		$helper = Mage::helper('eb2ccore');
		$path = sprintf(self::PATH_PATTERN, Mage::getBaseDir('var'), $type[$feedType]['local_path'], $dir->getValue());
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
	public function buildFileName($feedType)
	{
		return sprintf(
			self::ABSOLUTE_PATH_PATTERN,
			$this->generateFilePath($feedType, Mage::helper('eb2cproduct/struct_outboundfeedpath')),
			$this->generateFileName($feedType)
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
	 * load category by name
	 * @param string $categoryName the category name to filter the category table
	 * @return Mage_Catalog_Model_Category
	 */
	public function loadCategoryByName($categoryName)
	{
		return Mage::getModel('catalog/category')
			->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('name', array('eq' => $categoryName))
			->load()
			->getFirstItem();
	}

	/**
	 * get parent default category id
	 * @return int default parent category id
	 */
	public function getDefaultParentCategoryId()
	{
		return Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('parent_id', array('eq' => 0))
			->load()
			->getFirstItem()
			->getId();
	}

	/**
	 * take dom document object and string xslt file template to transform
	 * whatever the passed in xslt file template transform the new doc to
	 * @param TrueAction_Dom_Document $doc the document got get the nodelist
	 * @param string $xsltFilePath the xslt stylesheet template file absolute fulle path
	 * @param array $params parameters for the xslt
	 * @return TrueAction_Dom_Document
	 */
	public function splitDomByXslt(TrueAction_Dom_Document $doc, $xsltFilePath, array $params=array())
	{
		$helper = Mage::helper('eb2ccore');
		// create a new DOMDocument for the xsl
		$xslDom = $helper->getNewDomDocument();
		$xslDom->load($xsltFilePath);
		// load the xsl document into a XSLProcessor
		$xslProcessor = $helper->getNewXsltProcessor();
		$xslProcessor->importStyleSheet($xslDom);
		$xslProcessor->setParameter('', $params);
		// create a new DOMDocument from the transformed XML
		$transformed = $helper->getNewDomDocument();
		$transformed->loadXML($xslProcessor->transformToXML($doc));
		return $transformed;
	}
}

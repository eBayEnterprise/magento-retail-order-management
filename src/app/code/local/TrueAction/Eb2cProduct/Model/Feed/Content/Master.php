<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Content_Master
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('color' => 'ExtendedAttributes/ColorAttributes/Color'),
				array('code' => 'Code/text()')
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('product_links' => 'ExtendedAttributes/ProductLinks/ProductLink'),
				array(
					// Type of link relationship.
					'link_type' => './@link_type',
					// Operation to take with the product link. ("Add", "Delete")
					'operation_type' => './@operation_type',
					// Unique ID (SKU) for the linked product.
					'link_to_unique_id' => 'LinkToUniqueId/text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('category_links' => 'ExtendedAttributes/CategoryLinks/CategoryLink'),
				array(
					// if category is the default
					'default' => './@default', // (bool)
					// Used to link products across catalogs.
					'catalog_id' => './@catalog_id',
					// Operation to take with the category.
					'import_mode' => './@import_mode',
					// Unique ID (SKU) for the linked product.
					'name' => 'Name/text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('title' => 'BaseAttributes/Title'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// Localized product title
					'title' => './text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('color' => 'ExtendedAttributes/ColorAttributes/Color'),
				array('code' => 'Code/text()')
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('long_description' => 'ExtendedAttributes/LongDescription'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// Localized product title
					'description' => './text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('short_description' => 'ExtendedAttributes/ShortDescription'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// short description of the item.
					'description' => './text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('title' => 'ExtendedAttributes/SearchKeywords'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// search keywords of the item.
					'search_keywords' => './text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('title' => 'CustomAttributes/Attribute'),
				array(
					// Custom attribute name.
					'name' => './@name',
					// Operation to take with the attribute. ("Add", "Change", "Delete")
					'operation_type' => './@operation_type',
					// Operation to take with the product link. ("Add", "Delete")
					'lang' => './@xml:lang',
					// Unique ID (SKU) for the linked product.
					'value' => 'Value/text()',
				)
			)),
		);
		$this->_baseXpath = '/ContentMaster/Content';
		$this->_feedLocalPath = $this->_config->contentFeedLocalPath;
		$this->_feedRemotePath = $this->_config->contentFeedRemotePath;
		$this->_feedFilePattern = $this->_config->contentFeedFilePattern;
		$this->_feedEventType = $this->_config->contentFeedEventType;
	}

	protected $_extractMap = array(
		'gift_wrapping_available' => 'ExtendedAttributes/GiftWrap/text()', // bool
		'catalog_id' => './@catalog_id',
		'gsi_client_id' => './@gsi_client_id',
		'gsi_store_id' => './@gsi_store_id',
		'unique_id' => 'UniqueID/text()',
		'style_id' => 'StyleID/text()',
	);

	/**
	 * getting category attribute set id.
	 *
	 * @return int, the category attribute set id
	 */
	protected function _getCategoryAttributeSetId()
	{
		return (int) Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'attribute_set_id')
			->getEntityType()
			->getDefaultAttributeSetId();
	}

	/**
	 * getting the attribute selected option.
	 *
	 * @param string $attribute, the string attribute code to get the attribute config
	 * @param string $option, the string attribute option label to get the attribute
	 *
	 * @return Mage_Eav_Model_Config
	 */
	protected function _getAttributeOptionId($attribute, $option)
	{
		$optionId = 0;
		$attributes = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
		$attributeOptions = $attributes->getSource()->getAllOptions();
		foreach ($attributeOptions as $attrOption) {
			if (strtoupper(trim($attrOption['label'])) === strtoupper(trim($option))) {
				$optionId = $attrOption['value'];
			}
		}

		return $optionId;
	}

	/**
	 * load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _loadProductBySku($sku)
	{
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('*');
		$products->getSelect()
			->where('e.sku = ?', $sku);

		return $products
			->load()
			->getFirstItem();
	}

	/**
	 * load category by name
	 *
	 * @param string $categoryName, the category name to filter the category table
	 *
	 * @return Mage_Catalog_Model_Category
	 */
	protected function _loadCategoryByName($categoryName)
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
	 *
	 * @return int, default parent category id
	 */
	protected function _getDefaultParentCategoryId()
	{
		return Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('parent_id', array('eq' => 0))
			->load()
			->getFirstItem()
			->getId();
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return self
	 */
	public function processFeeds()
	{
		$coreHelperFeed = Mage::helper('eb2ccore/feed');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$feedModel = $this->getFeedModel();

		$feedModel->fetchFeedsFromRemote(
			$cfg->contentFeedRemoteReceivedPath,
			$cfg->contentFeedFilePattern
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$feeds = $feedModel->lsInboundDir();
		Mage::log(sprintf('[ %s ] Found %d files to import', __CLASS__, count($feeds)), Zend_Log::DEBUG);
		foreach ($feeds as $feed) {
			// load feed files to dom object
			$doc->load($feed);
			Mage::log(sprintf('[ %s ] Loaded xml file %s', __CLASS__, $feed), Zend_Log::DEBUG);

			// validate feed header
			if ($coreHelperFeed->validateHeader($doc, $cfg->contentFeedEventType)) {
				// processing feed Contents
				$this->_contentMasterActions($doc);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$feedModel->mvToArchiveDir($feed);
			}
		}

		Mage::log(sprintf('[ %s ] Complete', __CLASS__), Zend_Log::DEBUG);

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		Mage::helper('eb2ccore')->clean();

		return $this;
	}

	/**
	 * determine which action to take for Content master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return self
	 */
	protected function _contentMasterActions(DOMDocument $doc)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$cfgCatId = $cfg->catalogId;
		$cfgClientId = $cfg->clientId;
		$items = $this->getExtractor()->extract(new DOMXPath($doc));
		$numItems = count($items);

		if (!$numItems) {
			Mage::log(sprintf('[ %s ] Found no items in file to import.', __CLASS__), Zend_Log::WARN);
			return $this;
		}
		foreach ($items as $i => $item) {
			Mage::log(sprintf('[ %s ] Attempting to import %d of %d items.', __CLASS__, $i, $numItems), Zend_Log::DEBUG);
			$catId = $item->getCatalogId();
			$clientId = $item->getGsiClientId();
			if ($catId !== $cfgCatId) {
				Mage::log(
					sprintf("[ %s ] Item catalog_id '%s' doesn't match configured catalog_id '%s'.", __CLASS__, $catId, $cfgCatId),
					Zend_Log::WARN
				);
			} elseif ($clientId !== $cfgClientId) {
				Mage::log(
					sprintf("[ %s ] Item client_id '%s' doesn't match configured client_id '%s'.", __CLASS__, $clientId, $cfgClientId),
					Zend_Log::WARN
				);
			} else {
				$this->_synchProduct($item);
			}
		}
		return $this;
	}

	/**
	 * prepared related, crosssell, upsell array to be set to a product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return array, composite array contain key for related, crosssell, and upsell data
	 */
	protected function _preparedProductLinkData(Varien_Object $dataObject)
	{
		// Product Links
		$links = array(
			'related' => array(),
			'upsell' => array(),
			'crosssell' => array(),
		);
		$pos = array(
			'related' => 0,
			'upsell' => 0,
			'crosssell' => 0,
		);
		foreach ($dataObject->getProductLinks() as $link) {
			if ($link instanceof Varien_Object && strtoupper(trim($link->getOperationType())) === 'ADD') {
				$linkId = $this->_loadProductBySku($dataObject->getLinkToUniqueId())->getId();
				$linkType = strtolower(trim($link->getLinkType()));
				if ($linkId) {
					if (isset($pos[$linkType])) {
						$links[$linkType][$linkId]['position'] = $pos[$linkType]++;
					} else {
						Mage::log(
							sprintf('[ %s ] Encountered unknown link type "%s".', __CLASS__, $linkType),
							Zend_Log::WARN
						);
					}
				}
			}
		}
		return $links;
	}

	/**
	 * prepared category data.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return array, category data
	 */
	protected function _preparedCategoryLinkData(Varien_Object $dataObject)
	{
		// Product Category Link
		$categoryLinks = $dataObject->getCategoryLinks();
		$fullPath = 0;

		if (!empty($categoryLinks)) {
			foreach ($categoryLinks as $link) {
				if ($link instanceof Varien_Object) {
					$categories = explode('-', $link->getName());
					if (strtoupper(trim($link->getImportMode())) === 'DELETE') {
						foreach($categories as $category) {
							$this->setCategory($this->_loadCategoryByName(ucwords($category)));
							if ($this->getCategory()->getId()) {
								// we have a valid category in the system let's delete it
								$this->getCategory()->delete();
							}
						}
					} else {
						// adding or changing category import mode
						$path = $this->getDefaultRootCategoryId();
						foreach($categories as $category) {
							$path .= '/' . $this->_addCategory(ucwords($category), $path);
						}
						$fullPath .= '/' . $path;
					}
				}
			}
		}
		return explode('/', $fullPath);
	}

	/**
	 * update product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return self
	 */
	protected function _synchProduct(Varien_Object $dataObject)
	{
		if (trim($dataObject->getUniqueId()) !== '') {
			$this->setProduct($this->_loadProductBySku($dataObject->getUniqueId()));
			$productObject = $this->getProduct()->getId() ? $this->getProduct() : $this->_getDummyProduct($dataObject);

			try {
				// get product link data
				$linkData = $this->_preparedProductLinkData($dataObject);

				// get extended attributes data containing (gift wrap, color, long/short descriptions)
				$extendedData = $this->_getExtendAttributeData($dataObject);

				// getting product name/title
				$productTitle = trim($this->_getDefaultLocaleTitle($dataObject));
				$productObject->addData(
					array(
						// setting related data
						'related_link_data' => (!empty($linkData['related']))? $linkData['related'] : array(),
						// setting upsell data
						'up_sell_link_data' => (!empty($linkData['upsell']))? $linkData['upsell'] : array(),
						// setting crosssell data
						'cross_sell_link_data' => (!empty($linkData['crosssell']))? $linkData['crosssell'] : array(),
						// setting category data
						'category_ids' => $this->_preparedCategoryLinkData($dataObject),
						// Setting product name/title from base attributes
						'name' => ($productTitle !== '')? $productTitle : $productObject->getName(),
						// setting gift_wrapping_available
						'gift_wrapping_available' => $extendedData['gift_wrap'],
						// setting color attribute
						'color' => $extendedData['color_attributes'],
						// setting the product long description according to the store language setting
						'description' => $extendedData['long_description'],
						// setting the product short description according to the store language setting
						'short_description' => $extendedData['short_description'],
						'website_ids' => $this->getWebsiteIds(),
						'store_ids' => array($this->getDefaultStoreId()),
						'url_key' => $dataObject->getUniqueId(),
					)
				)->save(); // saving the product

			} catch (Mage_Core_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while updating the product for Content Master Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while updating the product for Content Master Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}

			// adding product custom attributes
			$this->_addCustomAttributeToProduct($dataObject, $productObject);
		}

		return $this;
	}

	/**
	 * Create dummy products and return new dummy product object
	 *
	 * @param Varien_Object $dataObject, the object with data needed to create dummy product
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _getDummyProduct(Varien_Object $dataObject)
	{
		// getting product name/title
		$productTitle = $this->_getDefaultLocaleTitle($dataObject);
		$productObject = $this->getProduct()->load(0);
		try {
			$productObject->unsId()
				->addData(
					array(
						'type_id' => 'simple', // default product type
						'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
						'attribute_set_id' => $this->getDefaultAttributeSetId(),
						'name' => (trim($productTitle) !== '')? $productTitle : 'temporary-name - ' . uniqid(),
						'status' => 0, // default - disabled
						'sku' => $dataObject->getUniqueId(),
						'website_ids' => $this->getWebsiteIds(),
						'store_ids' => array($this->getDefaultStoreId()),
						'stock_data' => array('is_in_stock' => 1, 'qty' => 999, 'manage_stock' => 1),
						'tax_class_id' => 0,
						'url_key' => $dataObject->getUniqueId(),
					)
				)
				->save();
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while creating dummy product for Content Master Feed (%d)',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while creating dummy product for Content Master Feed (%d)',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $productObject;
	}

	/**
	 * getting default locale title that match magento default locale
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the default product title
	 *
	 * @return string
	 */
	protected function _getDefaultLocaleTitle(Varien_Object $dataObject)
	{
		$title = '';
		// Setting product name/title from base attributes
		$baseAttributes = $dataObject->getBaseAttributes();
		foreach ($baseAttributes as $baseAttribute) {
			if ($baseAttribute instanceof Varien_Object && trim(strtoupper($baseAttribute->getLang())) === trim(strtoupper($this->getDefaultStoreLanguageCode())) &&
			trim($baseAttribute->getTitle()) !== '') {
				// setting the product title according to the store language setting
				$title = $baseAttribute->getTitle();
			}
		}
		return $title;
	}

	/**
	 * extract extended attribute data such as (gift_wrap
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the extended attribute product data
	 *
	 * @return array, composite array containing description data, gift wrap, color... etc
	 */
	protected function _getExtendAttributeData(Varien_Object $dataObject)
	{
		$data = array('gift_wrap' => 0, 'color_attributes' => 0, 'long_description' => null, 'short_description' => null);

		$extendedAttributes = $dataObject->getExtendedAttributes();
		if (!empty($extendedAttributes)) {
			$giftWrap = $extendedAttributes['gift_wrap'];
			if ($giftWrap instanceof Varien_Object) {
				// extracting gift_wrapping_available
				$data['gift_wrap'] = (trim(strtoupper($giftWrap->getGiftWrap())) === 'Y')? 1 : 0;
			}

			if (isset($extendedAttributes['color_attributes'])) {
				$colorAttributes = $extendedAttributes['color_attributes'];
				if ($colorAttributes instanceof Varien_Object) {
					// extracting color attribute
					$data['color_attributes'] = $this->_getAttributeOptionId('color', $colorAttributes->getCode());
				}
			}

			if (isset($extendedAttributes['long_description'])) {
				// get long description data
				$longDescriptions = $extendedAttributes['long_description'];
				foreach ($longDescriptions as $longDescription) {
					if ($longDescription instanceof Varien_Object &&
					trim(strtoupper($longDescription->getLang())) === trim(strtoupper($this->getDefaultStoreLanguageCode()))) {
						// extracting the product long description according to the store language setting
						$data['long_description'] = $longDescription->getLongDescription();
					}
				}
			}

			if (isset($extendedAttributes['short_description'])) {
				// get short description data
				$shortDescriptions = $extendedAttributes['short_description'];
				foreach ($shortDescriptions as $shortDescription) {
					if ($shortDescription instanceof Varien_Object &&
					trim(strtoupper($shortDescription->getLang())) === trim(strtoupper($this->getDefaultStoreLanguageCode()))) {
						// setting the product short description according to the store language setting
						$data['short_description'] = $shortDescription->getShortDescription();
					}
				}
			}
		}

		return $data;
	}

	/**
	 * adding custom attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 *
	 * @return self
	 */
	protected function _addCustomAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$customData = array();
		$customAttributes = $dataObject->getCustomAttributes();
		if (!empty($customAttributes)) {
			foreach ($customAttributes as $customAttribute) {
				if ($customAttribute instanceof Varien_Object && trim(strtoupper($customAttribute->getLang())) === trim(strtoupper($this->getDefaultStoreLanguageCode()))) {
					// getting the custom attribute into a valid magento attribute format
					$attributeName = $this->_underscore($customAttribute->getName());
					if (Mage::helper('eb2cproduct')->hasEavAttr($attributeName)) {
						// attribute does exists in magento store, let check it's operation type
						if (trim(strtoupper($customAttribute->getOperationType())) === 'DELETE') {
							// set the attribute value to null to remove it
							$customData[$attributeName] = null;
						} else {
							// for add an change operation type just set the attribute value
							$customData[$attributeName] = $customAttribute->getValue();
						}
					}
				}
			}
		}

		// we have valid custom data let's add it and save it to the product object
		if (!empty($customData)) {
			try{
				$productObject->addData($customData)->save();
			} catch (Mage_Core_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding custom attributes to product for Content Master Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding custom attributes to product for Content Master Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this;
	}

	/**
	 * add category to magento, check if already exist and return the category id
	 *
	 * @param string $categoryName, the category to either add or get category id from magento
	 * @param string $path, delimited string of the category depth path
	 *
	 * @return int, the category id
	 */
	protected function _addCategory($categoryName, $path)
	{
		$categoryId = 0;
		if (trim($categoryName) !== '') {
			// let's check if category already exists
			$this->setCategory($this->_loadCategoryByName($categoryName));
			$categoryId = $this->getCategory()->getId();
			if (!$categoryId) {
				// category doesn't currently exists let's add it.
				try {
					$this->getCategory()->setAttributeSetId($this->getDefaultCategoryAttributeSetId())
						->setStoreId($this->getDefaultStoreId())
						->addData(
							array(
								'name' => $categoryName,
								'path' => $path, // parent relationship..
								'description' => $categoryName,
								'is_active' => 1,
								'is_anchor' => 0, //for layered navigation
								'page_layout' => 'default',
								'url_key' => Mage::helper('catalog/product_url')->format($categoryName), // URL to access this category
								'image' => null,
								'thumbnail' => null,
							)
						)
						->save();

					$categoryId = $this->getCategory()->getId();
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
					Mage::log(
						sprintf(
							'[ %s ] The following error has occurred while adding categories product for Content Master Feed (%d)',
							__CLASS__, $e->getMessage()
						),
						Zend_Log::ERR
					);
				}
			}
		}

		return $categoryId;
	}
}

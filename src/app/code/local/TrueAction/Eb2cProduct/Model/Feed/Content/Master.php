<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Content_Master extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		// getting the default magento language
		$storeLocaleData = explode('_', Mage::app()->getLocale()->getLocaleCode());
		$storeLang = 'EN-GB'; // this is the default store language
		if (sizeof($storeLocaleData) > 1) {
			$storeLang = strtoupper($storeLocaleData[0]) . '-GB';
		}

		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$this->setExtractor(Mage::getModel('eb2cproduct/feed_content_extractor')) // Magically setting an instantiated extractor object
			->setProduct(Mage::getModel('catalog/product'))
			->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'))
			->setEavConfig(Mage::getModel('eav/config'))
			->setCategory(Mage::getModel('catalog/category')) // magically setting catalog/category model object
			->setDefaultStoreLanguageCode($storeLang) // setting default store language
			->setFeedModel(Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs))
			->setBaseDir($cfg->contentFeedLocalPath);

		return $this;
	}

	/**
	 * checking product catalog eav config attributes.
	 *
	 * @param string $attribute, the string attribute code to check if exists for the catalog_product
	 *
	 * @return bool, true the attribute exists, false otherwise
	 */
	protected function _isAttributeExists($attribute)
	{
		return ((int) $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute)->getId() > 0)? true : false;
	}

	/**
	 * helper method to get attribute into the right format.
	 *
	 * @param string $attribute, the string attribute
	 *
	 * @return string, the correct attribute format
	 */
	protected function _attributeFormat($attribute)
	{
		$attributeData = preg_split('/(?=[A-Z])/', trim($attribute));
		$correctFormat = '';
		$index = 0;
		$size = sizeof($attributeData);
		foreach ($attributeData as $attr) {
			if (trim($attr) !== '') {
				$correctFormat .= strtolower($attr);
				if ($index < $size) {
					$correctFormat .= '_';
				}
			}
			$index++;
		}
		return $correctFormat;
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
		$attributes = $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
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
	 * @return catalog/product
	 */
	protected function _loadProductBySku($sku)
	{
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*');
		$products->getSelect()
			->where('e.sku = ?', $sku);

		$products->load();

		return $products->getFirstItem();
	}

	/**
	 * load category by name
	 *
	 * @param string $categoryName, the category name to filter the category table
	 *
	 * @return catalog/category
	 */
	protected function _loadCategoryByName($categoryName)
	{
		$categories = Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('name', array('eq' => $categoryName))
			->load();

		return $categories->getFirstItem();
	}

	/**
	 * Get the content inventory feed from eb2c.
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getContentMasterFeeds()
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$coreHelper = Mage::helper('eb2ccore');
		$remoteFile = $cfg->contentFeedRemoteReceivedPath;
		$configPath = $cfg->configPath;
		$feedHelper = Mage::helper('eb2ccore/feed');
		$productHelper = Mage::helper('eb2cproduct');

		// only attempt to transfer file when the FTP setting is valid
		if ($coreHelper->isValidFtpSettings()) {
			// Download feed from eb2c server to local server
			Mage::helper('filetransfer')->getFile(
				$this->getFeedModel()->getInboundDir(),
				$remoteFile,
				$feedHelper::FILETRANSFER_CONFIG_PATH
			);
		} else {
			// log as a warning
			Mage::log(
				'[' . __CLASS__ . '] Content Master Feed: can\'t transfer file from eb2c server because of invalid FTP setting on the magento store.',
				Zend_Log::WARN
			);
		}
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$productHelper = Mage::helper('eb2cproduct');
		$coreHelper = Mage::helper('eb2ccore');
		$coreHelperFeed = Mage::helper('eb2ccore/feed');
		$this->_getContentMasterFeeds();
		$domDocument = $coreHelper->getNewDomDocument();
		foreach ($this->getFeedModel()->lsInboundFolder() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $productHelper->getConfigModel()->contentFeedEventType;
			$expectHeaderVersion = $productHelper->getConfigModel()->contentFeedHeaderVersion;

			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// processing feed Contents
				$this->_contentMasterActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$this->getFeedModel()->mvToArchiveFolder($feed);
			}
		}

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * determine which action to take for Content master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _contentMasterActions($doc)
	{
		$productHelper = Mage::helper('eb2cproduct');
		$feedContentCollection = $this->getExtractor()->extractContentMasterFeed($doc);
		if ($feedContentCollection){
			// we've import our feed data in a varien object we can work with
			foreach ($feedContentCollection as $feedContent) {
				// Ensure this matches the catalog id set in the Magento admin configuration.
				// If different, do not update the Content and log at WARN level.
				if ($feedContent->getCatalogId() !== $productHelper->getConfigModel()->catalogId) {
					Mage::log(
						'Content Master Feed Catalog_id (' . $feedContent->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$productHelper->getConfigModel()->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this Content and log at WARN level.
				if ($feedContent->getGsiClientId() !== $productHelper->getConfigModel()->clientId) {
					Mage::log(
						'Content Master Feed Client_id (' . $feedContent->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$productHelper->getConfigModel()->clientId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// process content feed data
				$this->_updateContent($feedContent);
			}
		}
	}

	/**
	 * prepared related, crosssell, upsell array to be set to a product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return array, composite array contain key for related, crosssell, and upsell data
	 */
	protected function _preparedProductLinkData	($dataObject)
	{
		// Product Link
		$relatedLink = array();
		$upsellLink = array();
		$crosssellLink = array();
		$relatedPosition = 0;
		$upsellPosition = 0;
		$crosssellPosition = 0;
		$productLinks = $dataObject->getProductLinks();
		if (!empty($productLinks)) {
			foreach ($productLinks as $link) {
				if ($link instanceof Varien_Object) {
					if (strtoupper(trim($link->getOperationType())) === 'ADD') {
						$linkProductObject = $this->_loadProductBySku($dataObject->getLinkToUniqueId());
						if ($linkProductObject->getId()) {
							if (strtoupper(trim($link->getLinkType())) === 'RELATED') {
								$relatedLink[$linkProductObject->getId()]['position'] = $relatedPosition;
								$relatedPosition++;
							} elseif (strtoupper(trim($link->getLinkType())) === 'UPSELL') {
								$upsellLink[$linkProductObject->getId()]['position'] = $upsellPosition;
								$upsellPosition++;
							} elseif (strtoupper(trim($link->getLinkType())) === 'CROSSSELL') {
								$crosssellLink[$linkProductObject->getId()]['position'] = $crosssellPosition;
								$crosssellPosition++;
							}
						}
					}
				}
			}
		}

		return array('related' => $relatedLink, 'upsell' => $upsellLink, 'crosssell' => $crosssellLink);
	}

	/**
	 * prepared category data.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return array, category data
	 */
	protected function _preparedCategoryLinkData($dataObject)
	{
		// Product Category Link
		$categoryLinks = $dataObject->getCategoryLinks();
		$fullPath = '0';

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
						$path = '';
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
	 * @return void
	 */
	protected function _updateContent($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getUniqueID()) !== '') {
				// we have a valid Content, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getUniqueID()));

				if ($this->getProduct()->getId()) {
					try {
						$productObject = $this->getProduct();

						// get product link data
						$linkData = $this->_preparedProductLinkData($dataObject);

						// setting related data
						if (!empty($linkData['related'])) {
							$productObject->setRelatedLinkData($linkData['related']);
						}

						// setting upsell data
						if (!empty($linkData['upsell'])) {
							$productObject->setUpSellLinkData($linkData['upsell']);
						}

						// setting crosssell data
						if (!empty($linkData['crosssell'])) {
							$productObject->setCrossSellLinkData($linkData['crosssell']);
						}

						// setting category data
						$productObject->setCategoryIds($this->_preparedCategoryLinkData($dataObject));

						// Setting product name/title from base attributes
						$baseAttributes = $dataObject->getBaseAttributes();
						if ($baseAttributes) {
							foreach ($baseAttributes as $baseAttribute) {
								if ($baseAttribute instanceof Varien_Object) {
									if (trim(strtoupper($baseAttribute->getLang())) === $this->getDefaultStoreLanguageCode()) {
										// setting the product title according to the store language setting
										$productObject->setName($baseAttribute->getTitle());
									}
								}
							}
						}

						// Setting product short/long description from extended attributes
						$extendedAttributes = $dataObject->getExtendedAttributes();
						if ($extendedAttributes) {
							$giftWrap = $extendedAttributes['gift_wrap'];
							if ($giftWrap instanceof Varien_Object) {
								// setting gift_wrapping_available
								$productObject->setGiftWrappingAvailable((trim(strtoupper($giftWrap->getGiftWrap())) === 'Y')? 1 : 0);
							}

							$colorAttributes = $extendedAttributes['color_attributes'];
							if ($colorAttributes instanceof Varien_Object) {
								// setting color attribute
								$productObject->setColor($this->_getAttributeOptionId('color', $colorAttributes->getCode()));
							}

							// get long description data
							$longDescriptions = $extendedAttributes['long_description'];
							foreach ($longDescriptions as $longDescription) {
								if ($longDescription instanceof Varien_Object) {
									if (trim(strtoupper($longDescription->getLang())) === $this->getDefaultStoreLanguageCode()) {
										// setting the product long description according to the store language setting
										$productObject->setDescription($longDescription->getLongDescription());
									}
								}
							}

							// get short description data
							$shortDescriptions = $extendedAttributes['short_description'];
							foreach ($shortDescriptions as $shortDescription) {
								if ($shortDescription instanceof Varien_Object) {
									if (trim(strtoupper($shortDescription->getLang())) === $this->getDefaultStoreLanguageCode()) {
										// setting the product short description according to the store language setting
										$productObject->setShortDescription($shortDescription->getShortDescription());
									}
								}
							}
						}

						// Setting product custom attributes
						$customAttributes = $dataObject->getCustomAttributes();
						if ($customAttributes) {
							foreach ($customAttributes as $customAttribute) {
								if ($customAttribute instanceof Varien_Object) {
									if (trim(strtoupper($customAttribute->getLang())) === $this->getDefaultStoreLanguageCode()) {
										// getting the custom attribute into a valid magento attribute format
										$attributeName = $this->_attributeFormat($customAttribute->getName());
										if ($this->_isAttributeExists($attributeName)) {
											// attribute does exists in magento store, let check it's operation type
											if (trim(strtoupper($customAttribute->getOperationType())) === 'DELETE') {
												// set the attribute value to null to remove it
												$productObject->setData($attributeName, null);
											} else {
												// for add an change operation type just set the attribute value
												$productObject->setData($attributeName, $customAttribute->getValue());
											}
										}
									}
								}
							}
						}

						// saving the product
						$productObject->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this Content doesn't exists in magento let simply log it
					Mage::log('Content Master Feed for SKU (' . $dataObject->getUniqueID() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
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
					$categoryData = array(
						'name' => $categoryName,
						'path' => $path, // parent relationship..
						'description' => $categoryName,
						'is_active' => 1,
						'is_anchor' => 0, //for layered navigation
						'page_layout' => 'default',
						'url_key' => Mage::helper('catalog/product_url')->format($categoryName) // URL to access this category
					);

					$this->getCategory()->addData($categoryData);
					$this->getCategory()->save();
					$categoryId = $this->getCategory()->getId();
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				}
			}
		}

		return $categoryId;
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return void
	 */
	protected function _clean()
	{
		try {
			// CLEAN CACHE
			Mage::app()->cleanCache();

			// STOCK STATUS
			$this->getStockStatus()->rebuild();
		} catch (Exception $e) {
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}

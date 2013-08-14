<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Master extends Mage_Core_Model_Abstract
{
	/**
	 * hold magento collection of product type id
	 *
	 * @var array
	 */
	protected $_productTypeId;

	/**
	 * hold magento default store id
	 *
	 * @var int
	 */
	protected $_defaultStoreId;

	/**
	 * hold magento collection of website ids
	 *
	 * @var array
	 */
	protected $_websiteIds;

	/**
	 * hold a collection of data to be added
	 *
	 * @var array
	 */
	protected $_addQueue;

	/**
	 * hold a collection of data to be updated
	 *
	 * @var array
	 */
	protected $_updateQueue;

	/**
	 * hold a collection of data to be deleted
	 *
	 * @var array
	 */
	protected $_deleteQueue;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->setHelper(Mage::helper('eb2cproduct'));
		$this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
		$this->setProduct(Mage::getModel('catalog/product'));
		$this->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'));
		$this->setFeedModel(Mage::getModel('eb2ccore/feed'));
		$this->setFastImport(Mage::getModel('fastsimpleimport/import'));

		// Magento product type ids
		$this->_productTypeId = array('simple', 'grouped', 'giftcard', 'downloadable', 'virtual', 'configurable', 'bundle');

		// set the default store id
		$this->_defaultStoreId = Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();

		// set array of website ids
		$this->_websiteIds = Mage::getModel('core/website')->getCollection()->getAllIds();

		$this->_addQueue = array();
		$this->_updateQueue = array();
		$this->_deleteQueue = array();

		return $this;
	}

	/**
	 * validating the product type
	 *
	 * @param string $type, the product type to validated
	 *
	 * @return bool, true the inputed type match what's in magento else doesn't match
	 */
	protected function _isValidProductType($type)
	{
		return in_array($type, $this->_productTypeId);
	}

	/**
	 * generating friendly url from a given string
	 *
	 * @param string $url, the string to be converted to a friendly url
	 *
	 * @return string, the friendly url
	 */
	protected function _friendlyUrl($url)
	{
		$friendlyUrl = '';
		$urlArr = explode(' ',  $url);
		$index = 0;
		foreach ($urlArr as $key) {
			$index++;
			if (trim($key) !== '') {
				$friendlyUrl .= $key;
				if ($index < sizeof($urlArr)) {
					$friendlyUrl .= '-';
				}
			}
		}
		$friendlyUrl = strtolower(str_replace("'", '-', $friendlyUrl));
		return $friendlyUrl;
	}

	/**
	 * Get the item inventory feed from eb2c.
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getItemMasterFeeds()
	{
		$this->getFeedModel()->setBaseFolder( $this->getHelper()->getConfigModel()->feedLocalPath );
		$remoteFile = $this->getHelper()->getConfigModel()->feedRemoteReceivedPath;
		$configPath =  $this->getHelper()->getConfigModel()->configPath;

		// downloading feed from eb2c server down to local server
		$this->getHelper()->getFileTransferHelper()->getFile($this->getFeedModel()->getInboundFolder(), $remoteFile, $configPath, null);
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$this->_getItemMasterFeeds();
		$domDocument = $this->getHelper()->getDomDocument();
		foreach ($this->getFeedModel()->lsInboundFolder() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $this->getHelper()->getConfigModel()->feedEventType;
			$expectHeaderVersion = $this->getHelper()->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if ($this->getHelper()->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// run adding item to their respective queue
				$this->_itemMasterActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
				$this->getFeedModel()->mvToArchiveFolder($feed);
			}
		}

		// let process adding product to the Magento database using fast import
		$this->processAddQueue();

		// let process updating product to the Magento database using fast import
		$this->processUpdateQueue();

		// let process deleting product to the Magento database using fast import
		$this->processDeleteQueue();

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * processing importing add queue.
	 *
	 * @return void
	 */
	public function processAddQueue()
	{
		if (!empty($this->_addQueue)) {
			try {
				// before we begin, let's clean the import database
				$this->getFastImport()->cleanBunches();
				$this->getFastImport()->setPartialIndexing(true)
					->setBehavior(Mage_ImportExport_Model_Import::BEHAVIOR_APPEND)
					->processProductImport($this->_addQueue);

				// after we finish mass import, let's clean the import database
				$this->getFastImport()->cleanBunches();

				// let's reset the add queue
				$this->_addQueue = array();
			} catch (Exception $e) {
				Mage::log('The following error occurred while processing Add Queue for Item Master (' . $this->getFastImport()->getErrorMessages() . ')', Zend_Log::ERR);
			}
		}
	}

	/**
	 * processing importing update queue.
	 *
	 * @return void
	 */
	public function processUpdateQueue()
	{
		if (!empty($this->_updateQueue)) {
			try {
				// before we begin, let's clean the import database
				$this->getFastImport()->cleanBunches();
				$this->getFastImport()->setPartialIndexing(true)
					->setBehavior(Mage_ImportExport_Model_Import::BEHAVIOR_REPLACE)
					->processProductImport($this->_updateQueue);

				// after we finish mass import, let's clean the import database
				$this->getFastImport()->cleanBunches();

				// let's reset the update queue
				$this->_updateQueue = array();
			} catch (Exception $e) {
				Mage::log('The following error occurred while processing update Queue for Item Master (' . $this->getFastImport()->getErrorMessages() . ')', Zend_Log::ERR);
			}
		}
	}

	/**
	 * processing importing delete queue.
	 *
	 * @return void
	 */
	public function processDeleteQueue()
	{
		if (!empty($this->_deleteQueue)) {
			try {
				// before we begin, let's clean the import database
				$this->getFastImport()->cleanBunches();
				$this->getFastImport()->setPartialIndexing(true)
					->setBehavior(Mage_ImportExport_Model_Import::BEHAVIOR_DELETE)
					->processProductImport($this->_deleteQueue);

				// after we finish mass import, let's clean the import database
				$this->getFastImport()->cleanBunches();

				// let's reset the delete queue
				$this->_deleteQueue = array();
			} catch (Exception $e) {
				Mage::log('The following error occurred while processing delete Queue for Item Master (' . $this->getFastImport()->getErrorMessages() . ')', Zend_Log::ERR);
			}
		}
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _itemMasterActions($doc)
	{
		$feedXpath = new DOMXPath($doc);

		$master = $feedXpath->query('//Item');
		foreach ($master as $item) {
			$catalogId = $item->getAttribute('catalog_id');
			// Ensure this matches the catalog id set in the Magento admin configuration.
			// If different, do not update the item and log at WARN level.
			if ($catalogId !== $this->getHelper()->getConfigModel()->catalogId) {
				Mage::log(
					"Item Master Feed Catalog_id (${catalogId}), doesn't match Magento Eb2c Config Catalog_id (" .
					$this->getHelper()->getConfigModel()->catalogId . ")",
					Zend_Log::WARN
				);
				continue;
			}

			$gsiClientId = $item->getAttribute('gsi_client_id');

			// Ensure that the client_id field here matches the value supplied in the Magento admin.
			// If different, do not update this item and log at WARN level.
			if ($gsiClientId !== $this->getHelper()->getConfigModel()->clientId) {
				Mage::log(
					"Item Master Feed Client_id (${gsiClientId}), doesn't match Magento Eb2c Config Client_id (" .
					$this->getHelper()->getConfigModel()->clientId . ")",
					Zend_Log::WARN
				);
				continue;
			}

			// Defines the action requested for this item. enum:("Add", "Change", "Delete")
			$operationType = (string) $item->getAttribute('operation_type');

			$dataObject = new Varien_Object();

			// SKU used to identify this item from the client system.
			$clientItemId = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ItemId/ClientItemId');
			if ($clientItemId->length) {
				$dataObject->setClientItemId(trim($clientItemId->item(0)->nodeValue));
			}

			// Allows for control of the web store display.
			$catalogClass = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/CatalogClass');
			if ($catalogClass->length) {
				$dataObject->setCatalogClass(trim($catalogClass->item(0)->nodeValue));
			}

			// Indicates the item if fulfilled by a drop shipper.
			// New attribute.
			$isDropShipped = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/IsDropShipped');
			if ($isDropShipped->length) {
				$dataObject->setDropShipped(trim($isDropShipped->item(0)->nodeValue));
			}

			// Short description in the catalog's base language.
			$itemDescription = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/ItemDescription');
			if ($itemDescription->length) {
				$dataObject->setItemDescription(trim($itemDescription->item(0)->nodeValue));
			}

			// Identifies the type of item.
			$itemType = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/ItemType');
			if ($itemType->length) {
				// This will be mapped by the product hub to Magento product types.
				// If the ItemType does not specify a Magento type, do not process the product and log at WARN level.
				$feedItemType = trim($itemType->item(0)->nodeValue);
				if (!$this->_isValidProductType($feedItemType)) {
					Mage::log(
						"Item Master Feed item_type (${feedItemType}), doesn't match Magento available Item Types (" .
						implode(',', $this->_productTypeId) . ")",
						Zend_Log::WARN
					);
					continue;
				}
				$dataObject->setItemType($feedItemType);
			}

			// Indicates whether an item is active, inactive or other various states.
			$itemStatus = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/ItemStatus');
			if ($itemStatus->length) {
				$dataObject->setItemStatus( (strtoupper(trim($itemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0 );
			}

			// Tax group the item belongs to.
			$taxCode = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BaseAttributes/TaxCode');
			if ($taxCode->length) {
				$dataObject->setTaxCode(trim($taxCode->item(0)->nodeValue));
			}

			$bundleDataObject = null;
			// Items included if this item is a bundle product.
			$bundleContents = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BundleContents');
			if ($bundleContents->length) {
				// Since we have bundle product let save these to a Varien_Object
				$bundleDataObject = new Varien_Object();

				// All items in the bundle must ship together.
				$bundleDataObject->setShipTogether((bool) $bundle->getAttribute('ship_together'));

				// Child item of this item
				$bundleItems = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BundleContents/BundleItems');
				if ($bundleItems->length) {
					foreach ($bundleItems as $bundleItem) {
						$bundleDataObject->setOperationType((string) $bundleItem->getAttribute('operation_type'));
						$bundleCatalogId = (string) $bundleItem->getAttribute('catalog_id');
						$bundleDataObject->setCatalogId($bundleCatalogId);

						// Client or vendor id (SKU) for the item to be included in the bundle.
						$itemID = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BundleContents/BundleItems[@catalog_id="' . $bundleCatalogId . '"]/ItemID');
						if ($itemID->length) {
							$bundleDataObject->setItemID(trim($itemID->item(0)->nodeValue));
						}

						// How many of the child item come in the bundle.
						$quantity = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/BundleContents/BundleItems[@catalog_id="' . $bundleCatalogId . '"]/Quantity');
						if ($quantity->length) {
							$bundleDataObject->setQuantity((int)$quantity->item(0)->nodeValue);
						}
					}
				}
			}

			$dataObject->setBundleContents($bundleDataObject);

			$dropShipDataObject = null;
			// Encapsulates data for drop shipper fulfillment. If the item is fulfilled by a drop shipper, these values are required.
			$dropShipSupplierInformation = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/DropShipSupplierInformation');
			if ($dropShipSupplierInformation->length) {
				// let save drop Ship Supplier Information to a Varien_Object
				$dropShipDataObject = new Varien_Object();

				// Name of the Drop Ship Supplier fulfilling the item
				$supplierName = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/DropShipSupplierInformation/SupplierName');
				if ($supplierName->length) {
					$dropShipDataObject->setSupplierName(trim($supplierName->item(0)->nodeValue));
				}

				// Unique code assigned to this supplier.
				$supplierNumber = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/DropShipSupplierInformation/SupplierNumber');
				if ($supplierNumber->length) {
					$dropShipDataObject->setSupplierNumber(trim($supplierNumber->item(0)->nodeValue));
				}

				// Id or SKU used by the drop shipper to identify this item.
				$supplierPartNumber = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/DropShipSupplierInformation/SupplierPartNumber');
				if ($supplierPartNumber->length) {
					$dropShipDataObject->setSupplierPartNumber(trim($supplierPartNumber->item(0)->nodeValue));
				}

			}
			$dataObject->setDropShipSupplierInformation($dropShipDataObject);

			$extendedAttributesObject = null;
			// Additional named attributes. None are required.
			$extendedAttributes = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes');
			if ($extendedAttributes->length) {
				// let save drop Extended Attributes to a Varien_Object
				$extendedAttributesObject = new Varien_Object();

				// If false, customer cannot add a gift message to the item.
				$allowGiftMessage = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/AllowGiftMessage');
				if ($allowGiftMessage->length) {
					$extendedAttributesObject->setAllowGiftMessage((bool) $allowGiftMessage->item(0)->nodeValue);
				}

				// Item is able to be back ordered.
				$backOrderable = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/BackOrderable');
				if ($backOrderable->length) {
					$extendedAttributesObject->setBackOrderable(trim($backOrderable->item(0)->nodeValue));
				}

				$colorAttributesObject = null;
				// Item color
				$colorAttributes = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ColorAttributes');
				if ($colorAttributes->length) {
					// let save drop color Attributes to a Varien_Object
					$colorAttributesObject = new Varien_Object();

					// Color value/name with a locale specific description.
					// Name of the color used as the default and in the admin.
					$colorCode = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ColorAttributes/Color/Code');
					if ($colorCode->length) {
						$colorAttributesObject->setColorCode((string) $colorCode->item(0)->nodeValue);
					}

					// Description of the color used for specific store views/languages.
					$colorDescription = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ColorAttributes/Color/Description');
					if ($colorDescription->length) {
						$colorAttributesObject->setColorDescription(trim($colorDescription->item(0)->nodeValue));
					}
				}
				$extendedAttributesObject->setColorAttributes($colorAttributesObject);

				// Country in which goods were completely derived or manufactured.
				$countryOfOrigin = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/CountryOfOrigin');
				if ($countryOfOrigin->length) {
					$extendedAttributesObject->setCountryOfOrigin(trim($countryOfOrigin->item(0)->nodeValue));
				}

				/*
				 *  Type of gift card to be used for activation.
				 * 		SD - TRU Digital Gift Card
				 *		SP - SVS Physical Gift Card
				 *		ST - SmartClixx Gift Card Canada
				 *		SV - SVS Virtual Gift Card
				 *		SX - SmartClixx Gift Card
				 */
				$giftCartTenderCode = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/GiftCartTenderCode');
				if ($giftCartTenderCode->length) {
					$extendedAttributesObject->setGiftCartTenderCode(trim($giftCartTenderCode->item(0)->nodeValue));
				}

				$itemDimensionsShippingObject = null;
				// Dimensions used for shipping the item.
				$itemDimensionsShipping = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ItemDimensions/Shipping');
				if ($itemDimensionsShipping->length) {
					// let save ItemDimensions/Shipping to a Varien_Object
					$itemDimensionsShippingObject = new Varien_Object();

					// Shipping weight of the item.
					$mass = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ItemDimensions/Shipping/Mass');
					if ($mass->length) {
						$itemDimensionsShippingObject->setMassUnitOfMeasure((string) $mass->getAttribute('unit_of_measure'));

						// Shipping weight of the item.
						$weight = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/ItemDimensions/Shipping/Mass/Weight');
						if ($weight->length) {
							$itemDimensionsShippingObject->setWeight((float) $weight->item(0)->nodeValue);
						}
					}
				}
				$extendedAttributesObject->setItemDimensionsShipping($itemDimensionsShippingObject);

				// Manufacturers suggested retail price. Not used for actual price calculations.
				$msrp = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/MSRP');
				if ($msrp->length) {
					$extendedAttributesObject->setMsrp((string) $msrp->item(0)->nodeValue);
				}

				// Default price item is sold at. Required only if the item is new.
				$price = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/Price');
				if ($price->length) {
					$extendedAttributesObject->setPrice((float) $price->item(0)->nodeValue);
				}

				$sizeAttributesObject = null;
				// Dimensions used for shipping the item.
				$sizeAttributes = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/SizeAttributes/Size');
				if ($sizeAttributes->length) {
					// let save ItemDimensions/Shipping to a Varien_Object
					$sizeAttributesObject = new Varien_Object();
					$sizeData = array();
					foreach ($sizeAttributes as $sizeRecord) {
						// Language code for the natural language of the size data.
						$sizeLang = $sizeRecord->getAttribute('xml:lang');

						// Size code.
						$sizeCode = '';
						$sizeCodeElement = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/SizeAttributes/Size/Code');
						if ($sizeCodeElement->length) {
							$sizeCode = (string) $sizeCodeElement->item(0)->nodeValue;
						}

						// Size Description.
						$sizeDescription = '';
						$sizeDescriptionElement = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/ExtendedAttributes/SizeAttributes/Size/Description');
						if ($sizeDescriptionElement->length) {
							$sizeDescription = (string) $sizeDescriptionElement->item(0)->nodeValue;
						}

						$sizeData[] = array(
							'lang' => $sizeLang,
							'code' => $sizeCode,
							'description' => $sizeDescription,
						);
					}

					$sizeAttributesObject->setSize($sizeData);
				}
				$extendedAttributesObject->setSizeAttributes($sizeAttributesObject);
			}
			$dataObject->setExtendedAttributes($extendedAttributesObject);

			$customAttributesObject = null;
			// Name value paris of additional attributes for the product.
			$customAttributes = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/CustomAttributes/Attribute');
			if ($customAttributes->length) {
				// let save CustomAttributes/Attribute to a Varien_Object
				$customAttributesObject = new Varien_Object();
				$attributeData = array();
				foreach ($customAttributes as $attributeRecord) {
					// The name of the attribute.
					$attributeName = $attributeRecord->getAttribute('name');

					// Type of operation to take with this attribute. enum: ("Add", "Change", "Delete")
					$attributeOperationType = $attributeRecord->getAttribute('operation_type');

					// Language code for the natural language or the <Value /> element.
					$attributeLang = $attributeRecord->getAttribute('xml:lang');

					// Value of the attribute.
					$attributeValue = '';
					$attributeValueElement = $feedXpath->query('//Item[@catalog_id="' . $catalogId . '"]/CustomAttributes/Attribute/Value');
					if ($attributeValueElement->length) {
						$attributeValue = (string) $attributeValueElement->item(0)->nodeValue;
					}

					$attributeData[] = array(
						'name' => $attributeName,
						'operationType' => $attributeOperationType,
						'lang' => $attributeLang,
						'value' => $attributeValue,
					);
				}

				$customAttributesObject->setAttributes($attributeData);
			}
			$dataObject->setCustomAttributes($customAttributesObject);

			switch (trim(strtoupper($operationType))) {
				case 'ADD':
					$this->_queueAdd($dataObject);
					break;
				case 'CHANGE':
					$this->_queueUpdate($dataObject);
					break;
				case 'DELETE':
					$this->_queueDelete($dataObject);
					break;
			}
		}
	}

	/**
	 * form data into an importable for format.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return array
	 */
	protected function _importDataFormat($dataObject)
	{
		return array(
			'sku' => $dataObject->getClientItemId(),
			'_type' => $dataObject->getItemType(),
			'_attribute_set' => 'Default',
			'_product_websites' => 'base',
			'price' => $dataObject->getExtendedAttributes()->getPrice(),
			'description' => $dataObject->getItemDescription(),
			'short_description' => $dataObject->getItemDescription(),
			'meta_title' => $dataObject->getClientItemId() . ' | ' . $dataObject->getItemDescription(),
			'meta_description' => $dataObject->getItemDescription(),
			'meta_keywords' => $dataObject->getClientItemId(),
			'weight' => $dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight(),
			'status' => $dataObject->getItemStatus(),
			'visibility' => 4,
			'tax_class_id' => 0,
			'qty' => 1,
			'is_in_stock' => 9999,
			'enable_googlecheckout' => '1',
			'gift_message_available' => '0',
			'url_key' => _friendlyUrl($dataObject->getClientItemId() . ' ' . $dataObject->getItemDescription()),
		);
	}

	/**
	 * add product to quote to queue to be imported.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return void
	 */
	protected function _queueAdd($dataObject)
	{
		if ($dataObject) {
			if ($dataObject->getClientItemId() !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->getProduct()->loadByAttribute('sku', $dataObject->getClientItemId());

				if (!$this->getProduct()->getId()) {
					$this->_addQueue[] = $this->_importDataFormat($dataObject);
				} else {
					// this item currently exists in magento let simply log it
					Mage::log("Item Master Feed Add Operation for SKU (" . $dataObject->getClientItemId() . "), already exists in Magento", Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * update product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return void
	 */
	protected function _queueUpdate($dataObject)
	{
		if ($dataObject) {
			if ($dataObject->getClientItemId() !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->getProduct()->loadByAttribute('sku', $dataObject->getClientItemId());

				if ($this->getProduct()->getId()) {
					$this->_updateQueue[] = $this->_importDataFormat($dataObject);
				} else {
					// this item doesn't exists in magento let simply log it
					Mage::log("Item Master Feed Update Operation for SKU (" . $dataObject->getClientItemId() . "), does not exists in Magento", Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * delete product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to delete the product
	 *
	 * @return void
	 */
	protected function _queueDelete($dataObject)
	{
		if ($dataObject) {
			if ($dataObject->getClientItemId() !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->getProduct()->loadByAttribute('sku', $dataObject->getClientItemId());

				if ($this->getProduct()->getId()) {
					$this->_deleteQueue[] = $this->_importDataFormat($dataObject);
				} else {
					// this item doesn't exists in magento let simply log it
					Mage::log("Item Master Feed Delete Operation for SKU (" . $dataObject->getClientItemId() . "), does not exists in Magento", Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return void
	 */
	protected function _clean()
	{
		try {
			// STOCK STATUS
			$this->getStockStatus()->rebuild();

			// CLEAN CACHE
			Mage::app()->cleanCache();
		} catch (Exception $e) {
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}

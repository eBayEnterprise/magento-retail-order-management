<?php
/**
 * Export images "the best we can"
 */
class TrueAction_Eb2cProduct_Model_Image_Export extends Varien_Object
{
	/**
	 * Return configuration registry
	 * @return eb2ccore/config_registry
	 */
	protected function _getConfig() {
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2cproduct/image_export_config'))
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}

	/**
	 * Returns an array of store Ids
	 * @return array
	 */
	protected function _getAllStoreIds()
	{
		$stores = array();
		foreach (Mage::app()->getStores(true) as $store) {
			$stores[] = $store->getStoreId();
		}
		return $stores;
	}

	/**
	 * Get a single store
	 * @return store
	 */
	protected function _getStoreUrl($storeId)
	{
		return Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
	}

	/**
	 * Set the current store context
	 * @return self
	 */
	protected function _setCurrentStore($storeId)
	{
		Mage::app()->setCurrentStore($storeId);
		return self;
	}

	/**
	 * Builds the Image Export DOM - creates the export file, validates the schema,  and then sends it.
	 * @param sourceStoredId a store id, or null for all stores
	 * @return Number of Stores examined for images
	 */
	public function buildExport($sourceStoreId=null)
	{
		$stores = array();

		$numberOfStoresProcessed = 0;

		if ($sourceStoreId !== null ) {
			$stores[0] = $sourceStoreId;
		} else {
			$stores = $this->_getAllStoreIds();
		}

		foreach ($stores as $storeId) {
			$this->_setCurrentStore($storeId);
			$dom = Mage::helper('eb2ccore')->getNewDomDocument();
			$dom->formatOutput = true;

			$domainParts = parse_url($this->_getStoreUrl($storeId));
			$itemImages = $dom->addElement('ItemImages', null, $this->_getConfig()->apiXmlNs)->firstChild;
			$itemImages->setAttribute('imageDomain', $domainParts['host']);
			$itemImages->setAttribute('clientId', $this->_getConfig()->clientId);
			$itemImages->setAttribute('timestamp', date('H:i:s'));

			$this->_buildMessageHeader($itemImages->createChild('MessageHeader'));

			if ($this->_buildItemImages($itemImages) > 0) {
				Mage::getModel('eb2ccore/api')->schemaValidate($dom, $this->_getConfig()->xsdFileImageExport);
				$this->_createFileFromDom($dom, $storeId);
			}

			$dom = null;
			$this->_setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$numberOfStoresProcessed++;
		}
		return $numberOfStoresProcessed;
	}
	/**
	 * Create a file from the dom, and return its full path.
	 * 'protected' so we can test around it.
	 * @param TrueAction_Dom_Document dom
	 * @param storeId
	 * @return string File name
	 */
	protected function _createFileFromDom(TrueAction_Dom_Document $dom, $storeId)
	{
		$coreFeed = Mage::getModel('eb2ccore/feed',
			array(
				'dir_config' => $this->_getConfig()->imageFeedDirectoryConfig
			)
		);
		$filename = $coreFeed->getLocalDirectory() . DS . date($this->_getConfig()->filenameFormat) . "_$storeId.xml";
		$dom->save($filename);
		return $filename;
	}

	/**
	 * Build an item's worth of images
	 * @param DOMelement node into which itemImages are placed
	 * @return int number of images for all products in this store
	 */
	protected function _buildItemImages(TrueAction_Dom_Element $itemImages)
	{
		$numberOfImages = 0;
		foreach (Mage::getModel('catalog/product')->getCollection() as $mageProduct) {
			if ($mageProduct->load($mageProduct->getId())) {
				if ($mageProduct->getMediaGalleryImages()->count()) {
					$mageImageViewMap = $this->_getMageImageViewMap($mageProduct);
					$item = $itemImages->createChild('Item');
					$item->setAttribute('id', $mageProduct->getSku());

					$images = $item->createChild('Images');

					foreach( $mageProduct->getMediaGalleryImages() as $mageImage ) {
						$hasNamedView = false;
						// A single image can be used for more than 1 'named view'
						foreach( array_keys($mageImageViewMap, $mageImage->getFile()) as $imageViewName ) {
							$hasNamedView = true;
							$this->_populateImageNode($images->createChild('Image'), $mageImage, $imageViewName);
						}
						// You can have images that do not have a 'named view' - they're just part of the Media Gallery.
						if (!$hasNamedView) {
							$this->_populateImageNode($images->createChild('Image'), $mageImage, '');
						}
						$numberOfImages++;
					}
				}
			}
		}
		return $numberOfImages;
	}

	/**
	 * Populates a single image node within an images node. All of the values are stored as attributes.
	 * @param type image node
	 * @param type mageImage Magento Image
	 * @param type viewName Name of the View
	 * @return self
	 */
	protected function _populateImageNode($image, $mageImage, $viewName)
	{
		list($w, $h) = getimagesize( (file_exists($mageImage->getPath())) ? $mageImage->getPath() : $mageImage->getUrl() );
		$image->setAttribute('imageview', $viewName);
		$image->setAttribute('imagename', $mageImage->getLabel());
		$image->setAttribute('imageurl', $mageImage->getUrl());
		$image->setAttribute('imagewidth', $w);
		$image->setAttribute('imageheight', $h);

		return $this;
	}

	/**
	 * Build Message Header from configuration into the passed DOMElement
	 * @param DOMElement node in which to place Header
	 * @return self
	 */
	protected function _buildMessageHeader(DOMElement $header)
	{
		$header->createChild('Standard', $this->_getConfig()->standard);
		$header->createChild('HeaderVersion', $this->_getConfig()->headerVersion);

		$sourceData = $header->createChild('SourceData');
		$sourceData->createChild('SourceId', $this->_getConfig()->sourceId);
		$sourceData->createChild('SourceType', $this->_getConfig()->sourceType);

		$destinationData = $header->createChild('DestinationData');
		$destinationData->createChild('DestinationId', $this->_getConfig()->destinationId);
		$destinationData->createChild('DestinationType', $this->_getConfig()->destinationType);

		$header->createChild('EventType', $this->_getConfig()->eventType);

		$messageData = $header->createChild('MessageData');
		$messageData->createChild('MessageId', $this->_getConfig()->messageId);
		$messageData->createChild('CorrelationId', $this->_getConfig()->correlationId);

		$header->createChild('CreateDateAndTime', date('m/d/y H:i:s'));

		return $this;
	}

	/**
	 * Searchs for all media_image type attributes for this product's attribute set, and creates a hash matching
	 * the attribute code to its value, which is a media path. The attribute code is used as the
	 * image 'view', and we use array_search to match based on media path.
	 * @return array of view_names => image_paths
	 */
	protected function _getMageImageViewMap($mageProduct)
	{
		$imageViewMap = array();
		$attributes = $mageProduct->getAttributes();
		foreach ($attributes as $attribute) {
			if (!strcmp($attribute->getFrontendInput(), 'media_image')) {
				$imageViewMap[$attribute->getAttributeCode()] = $mageProduct->getData($attribute->getAttributeCode());
			}
		}
		return $imageViewMap;
	}
}

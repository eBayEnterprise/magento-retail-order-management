<?php
/**
 * Export images "the best we can"
 */
class TrueAction_Eb2cProduct_Model_Image_Export extends Varien_Object
{
	protected $_coreFeed;

	public function _construct()
	{
		$this->_cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2cproduct/image_export_config'))
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
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
			$stores[] = $sourceStoreId;
		} else {
			foreach (Mage::app()->getStores(true) as $store) {
				$stores[] = $store->getStoreId();
			}
		}

		foreach ($stores as $storeId) {
			Mage::app()->setCurrentStore($storeId);
			$dom = Mage::helper('eb2ccore')->getNewDomDocument();
			$dom->formatOutput = true;

			$domainParts = parse_url(Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
			$itemImages = $dom->addElement('ItemImages', null, $this->_cfg->apiXmlNs)->firstChild;
			$itemImages->setAttribute('imageDomain', $domainParts['host']);
			$itemImages->setAttribute('clientId', $this->_cfg->clientId);
			$itemImages->setAttribute('timestamp', date('H:i:s'));

			$this->_buildMessageHeader($itemImages->createChild('MessageHeader'));

			if ($this->_buildItemImages($itemImages) > 0) {
				$this->_validateDom($dom)->_sendXml($dom, $storeId);
			}

			$dom = null;
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$numberOfStoresProcessed++;
		}

		return $numberOfStoresProcessed;
	}

	/**
	 * Validate the DOM
	 * @param TrueAction_Dom_Document
	 * @throws TrueAction_Eb2cCore_Exception if validation fails
	 * @return self
	 */
	protected function _validateDom(TrueAction_Dom_Document $dom)
	{
		$api = Mage::getModel('eb2ccore/api', array('xsd' => $this->_cfg->xsdFileImageExport));
		if (!$api->schemaValidate($dom)) {
			throw new TrueAction_Eb2cCore_Exception('Schema validation failed.'); // Inbound validation throws this, so I'm doing the same outbound.
		}
		return $this;
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
				'base_dir' => Mage::getBaseDir('var') . $this->_cfg->localPath
			)
		);
		$filename = $coreFeed->getOutboundPath() . DS . date($this->_cfg->filenameFormat) . "_$storeId.xml";
		$dom->save($filename);
		return $filename;
	}

	/**
	 * Send the file
	 * @param TrueAction_Dom_Document dom
	 * @param storeId
	 * @return self
	 */
	protected function _sendXml(TrueAction_Dom_Document $dom, $storeId)
	{
		$filename = $this->_createFileFromDom($dom, $storeId);
		$sftp = Mage::getModel('filetransfer/protocol_types_sftp');
		try {
			$sftp->sendFile($filename, $this->remotePath);
		} catch(Exception $e) {
			throw new TrueAction_Eb2cCore_Exception_Feed_Failure('Error sending file: ' . $e->getMessage());
		}
		return $this;
	}

	/**
	 * Build an item's worth of images
	 * @param DOMelement node into which itemImages are placed
	 * @return int number of images for all products in this store
	 */
	protected function _buildItemImages(DOMElement $itemImages)
	{
		$numberOfImages = 0;
		foreach (Mage::getModel('catalog/product')->getCollection() as $mageProduct) {
			$mageProduct->load($mageProduct->getId());
			if ($mageProduct->getMediaGalleryImages()->count() || $this->_cfg->includeEmptyGalleries) {
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
		$header->createChild('Standard', $this->_cfg->standard);
		$header->createChild('HeaderVersion', $this->_cfg->headerVersion);

		$sourceData = $header->createChild('SourceData');
		$sourceData->createChild('SourceId', $this->_cfg->sourceId);
		$sourceData->createChild('SourceType', $this->_cfg->sourceType);

		$destinationData = $header->createChild('DestinationData');
		$destinationData->createChild('DestinationId', $this->_cfg->destinationId);
		$destinationData->createChild('DestinationType', $this->_cfg->destinationType);

		$header->createChild('EventType', $this->_cfg->eventType);

		$messageData = $header->createChild('MessageData');
		$messageData->createChild('MessageId', $this->_cfg->messageId);
		$messageData->createChild('CorrelationId', $this->_cfg->correlationId);

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

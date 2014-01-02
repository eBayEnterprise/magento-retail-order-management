<?php
/**
 * Export images "the best we can"
 *
 */
class TrueAction_Eb2cProduct_Model_Image_Export extends Varien_Object
{
	private $_attributeImageMaps = array();
	protected $_coreFeed;

	public function _construct()
	{
		$this->_cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2cproduct/image_export_config'))
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}

	/**
	 * Builds the DOM for the specified sourceStoreId. If not given, builds a feed for all stores
	 * 	individually.
	 *
	 */
	public function buildExport($sourceStoreId=null)
	{
		$stores = array();

		if ($sourceStoreId) {
			$stores[] = $sourceStoreId;
		} else {
			foreach (Mage::app()->getStores(true) as $store) {
				$stores[] = $store->getStoreId();
			}
		}

		foreach ($stores as $storeId) {
			$dom = Mage::helper('eb2ccore')->getNewDomDocument();
			$dom->formatOutput = true;

			$domainParts = parse_url(Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
			$itemImages = $dom->addElement('ItemImages', null, $this->_cfg->apiXmlNs)->firstChild;
			$itemImages->setAttribute('imageDomain', $domainParts['host']);
			$itemImages->setAttribute('clientId', $this->_cfg->clientId);
			$itemImages->setAttribute('timestamp', date('H:i:s'));

			$this->_buildMessageHeader($itemImages->createChild('MessageHeader'))
				->_buildItemImages($itemImages, $storeId)
				->_sendDom($dom, $storeId);
		}

		return $this;
	}

	/**
	 * Send the file
	 *
	 */
	private function _sendDom($dom, $storeId)
	{
		//* @todo getting outbound path with 'var' should be closer to core
		$coreFeed = Mage::getModel('eb2ccore/feed', array('base_dir' => Mage::getBaseDir('var') . DS . $this->_cfg->localPath) );
		$filename = $coreFeed->getOutboundPath() . DS . date($this->_cfg->filenameFormat) . "-$storeId.xml";
		$dom->save($filename);

		// @todo xsd into core (somehow).
		$reflector = new ReflectionClass(get_class($this));
		$xsdFile = dirname($reflector->getFileName()) . DS . 'xsd' . DS . $this->_cfg->xsdFileImageExport;
		$api = Mage::getModel('eb2ccore/api', array('xsd' => $xsdFile));
		if (!$api->schemaValidate($dom)) {
			Mage::throwException('[ ' . __CLASS__ . ' ] Schema ' . $xsdFile . ' validation failed.');
		}

		$sftp = Mage::getModel('filetransfer/protocol_types_sftp');
		try {
			$sftp->sendFile($filename, $this->remotePath);
		} catch(Exception $e) {
			Mage::log('Error sending file: ' . $e->getMessage(), Zend_log::ERR);
		}
		return $this;
	}

	/**
	 * Build an item's worth of images
	 *
	 */
	private function _buildItemImages(DOMelement $itemImages, $storeId)
	{
		foreach (Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId) as $mageProduct) {
//		foreach (Mage::getModel('catalog/product')->getCollection() as $mageProduct) {
			$mageProduct->load($mageProduct->getId());
			if ($mageProduct->getMediaGalleryImages()->count() || $this->_cfg->includeEmptyGalleries) {
				$mageImageViewMap = $this->_getMageImageViewMap($mageProduct);
				$item = $itemImages->createChild('Item');
				$item->setAttribute('id', $mageProduct->getSku());

				$images = $item->createChild('Images');

				foreach( $mageProduct->getMediaGalleryImages() as $mageImage ) {
					$hasNamedView = false;
					foreach( array_keys($mageImageViewMap,$mageImage->getFile()) as $imageViewName ) {
						$hasNamedView = true;
						$this->_populateImageNode($images->createChild('Image'), $mageImage, $imageViewName);
					}
					if (!$hasNamedView) {
						$this->_populateImageNode($images->createChild('Image'), $mageImage, 'UNNAMED_VIEW'); 
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @param type $image node
	 * @param type $mageImage Magento Image
	 * @param type $viewName Name of the View
	 */
	private function _populateImageNode($image, $mageImage, $viewName)
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
	 * Build Message Header
	 *
	 */
	private function _buildMessageHeader(DOMelement $header)
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
	 *
	 */
	private function _getMageImageViewMap($mageProduct)
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
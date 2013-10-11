<?php
/**
 * Export images "the best we can"
 *
 */
class TrueAction_Eb2cProduct_Model_Image_Export extends Varien_Object
{
	private   $_dom;
	private   $_includeEmptyGalleries;
	protected $_coreFeed;

	public function _construct() {
		$this->_coreFeed = Mage::getModel('eb2ccore/feed');

		$this->_dom = Mage::helper('eb2ccore')->getNewDomDocument();

        $this->_cfg = Mage::getModel('eb2ccore/config_registry')
            ->addConfigModel(Mage::getSingleton('eb2cproduct/image_export_config'))
            ->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		$this->_dom->formatOutput = true;

		$this->_includeEmptyGalleries = false;
	}

	/**
	 * Build the DOM
	 *
	 */
	public function buildExportFeed()
	{
		$domainParts = parse_url(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));

		$itemImages = $this->_dom->addElement('ItemImages', null, $this->_cfg->apiXmlNs)->firstChild;
		$itemImages->setAttribute('imageDomain', $domainParts['host']);
		$itemImages->setAttribute('clientId', $this->_cfg->clientId);
		$itemImages->setAttribute('timestamp', date('H:i:s'));

		$this->_buildMessageHeader($itemImages->createChild('MessageHeader'))
			->_buildItemImages($itemImages);

		echo $this->_dom->saveXML() . "\n";
	}

	/**
	 * Build an item's worth of images
	 *
	 */
	private function _buildItemImages(DOMelement $itemImages)
	{
		foreach( Mage::getModel('catalog/product')->getCollection() as $mageProduct ) {
			$mageProduct->load($mageProduct->getId());

			if( $mageProduct->getMediaGalleryImages()->count()
				|| $this->_includeEmptyGalleries )
			{
				$item = $itemImages->createChild('Item');
				$item->setAttribute('id', $mageProduct->getSku());

				$images = $item->createChild('Images');

				foreach( $mageProduct->getMediaGalleryImages() as $mageImage ) {
					$image = $images->createChild('Image');
					$image->setAttribute('imagename', $mageImage->getLabel());
					$image->setAttribute('imageurl', $mageImage->getUrl());
				}
			}
		}
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
}

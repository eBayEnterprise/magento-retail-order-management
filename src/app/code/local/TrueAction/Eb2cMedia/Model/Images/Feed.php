<?php
/**
 * Product Images Feed
 * The abstract class defines some functions we all want to use.
 * The interface defines how feeds get invoked, which to me is separate from how they are configured
 *  and manage their files, and anyway it's where I started.
 *
 */
class TrueAction_Eb2cMedia_Model_Images_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	const EB2CMEDIA_IMAGES_MODEL = 'eb2cmedia/images';

	private $_cdnDomain; // We're making a guess here - if imageDomain is provided, it's a CDN installation
	private $_imageAttrs = array('imagewidth', 'imageview', 'imageurl', 'imagename', 'imageheight');

	protected function _construct()
	{
		$this->setFeedConfig(Mage::helper('eb2cmedia')->getConfigModel());
		$this->addData(
			array(
				'feed_event_type'   => $this->getFeedConfig()->imagesFeedEventType,
				'feed_file_pattern' => $this->getFeedConfig()->imagesFeedFilePattern,
				'feed_local_path'   => $this->getFeedConfig()->imagesFeedLocalPath,
				'feed_remote_path'  => $this->getFeedConfig()->imagesFeedRemotePath,
			)
		);

		parent::_construct();
	}

	/**
	 * The abstract 'magically' hands us the DOM, we just pull it apart.
	 *
	 * @return TrueAction_Eb2cMedia_Model_Images_Feed 
	 */
	public function processDom(TrueAction_Dom_Document $dom)
	{
		$imagesProcessed = 0;

		// ItemImages is the root node of each Image Feed file.
		foreach( $dom->getElementsByTagName('ItemImages') as $itemImagesNode ) {
			$this->_cdnDomain = $itemImagesNode->getAttribute('imageDomain');

			foreach( $dom->getElementsByTagName('Item') as $itemNode ) {
				$itemImageInfo = array();
				$itemImageInfo['sku'] = $itemNode->getAttribute('id');
				foreach( $itemNode->getElementsByTagName('Images') as $itemImagesNode ) {
					foreach( $itemImagesNode->getElementsByTagName('Image') as $itemImageNode ) {
						$oneImageInfo = array();
						foreach( $this->_imageAttrs as $attr ) {
							$oneImageInfo[$attr] = $itemImageNode->getAttribute($attr);
						}
						$itemImageInfo['images'][] = $oneImageInfo;
					}
					$imagesProcessed += $this->_processItemImageSet($itemImageInfo);
					$itemImageInfo = $oneImageInfo = null;
				}
			}
		}
		return $this;
	}

	/** 
	 * Process a single item image set. There could be multiple sets of images for a given item (I guess), but I 
	 * just read an Item node in; inside that node are images, I apply them.
	 *
	 * @return int number of images applied.
	 */
	private function _processItemImageSet($imageInfo)
	{
		$imageId = 0;
		$imagesProcessed = 0;

		$mageProduct = Mage::getModel('catalog/product');
		if (!$mageProduct->getIdBySku($imageInfo['sku'])) {
			// @todo - SKU not found, we just skip this. When SuperFeed complete, maybe it will have a 'dummy-item' method?
			Mage::log( '[' . __CLASS__ . '] SKU not found ' . $imageInfo['sku']);
			return 0;
		}

		$imageModel = Mage::getModel(self::EB2CMEDIA_IMAGES_MODEL);
		foreach( $imageInfo['images'] as $image ) {
			if( $this->_cdnDomain ) {
				$imageData = array(
					'sku'        => $imageInfo['sku'],
					'view'       => $image['imageview'],
					'name'       => $image['imagename'],
					'height'     => $image['imageheight'],
					'url'        => $image['imageurl'],
					'width'      => $image['imagewidth'],
				);

				$imageId = $imageModel->getIdByViewAndName($imageInfo['sku'], $image['imageview'], $image['imagename']);
				if( $imageId ) {
					$imageModel->load($imageId);
					$imageData['updated_at'] = Mage::getModel('core/date')->timestamp(time());
					$imageModel->addData($imageData)->save();
				} else {
					$imageModel->setData($imageData)->save();
				}
			}
			$imagesProcessed++;
		}
		return $imagesProcessed;
	}
}

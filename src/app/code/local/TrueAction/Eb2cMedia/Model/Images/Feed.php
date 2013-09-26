<?php
/**
 * Product Images Feed
 * The abstract class defines some functions we all want to use.
 * The interface defines how feeds get invoked, which to me is separate from how they are configured
 *  and manage their files, and anyway it's where I started.
 * @todo: Finish this Feed
 */
class TrueAction_Eb2cMedia_Model_Images_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	const EB2CMEDIA_IMAGES_MODEL   = 'eb2cmedia/images';

	private $_cdnDomain;
	private $_rootAttrs = array ('imageDomain',);
	private $_imageAttrs = array('imagewidth', 'imageview', 'imageurl', 'imagename', 'imageheight');

	protected function _construct()
	{
		$this->setFeedConfig(Mage::helper('eb2cmedia')->getConfigModel());

		$this->setFeedRemotePath  ($this->getFeedConfig()->imagesFeedRemotePath);
		$this->setFeedFilePattern ($this->getFeedConfig()->imagesFeedFilePattern);
		$this->setFeedLocalPath   ($this->getFeedConfig()->imagesFeedLocalPath);
		$this->setFeedEventType   ($this->getFeedConfig()->imagesFeedEventType);

		parent::_construct();
	}

	/**
	 * The abstract class will handle getting the files from remote, putting them into local directories, and handing 
	 * us the DOM of the XML.
	 *
	 */
	public function processDom(TrueAction_Dom_Document $dom)
	{
		$imagesProcessed = 0;
		// Validate Eb2c Header here?

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
					$imagesProcessed += $this->_processOneImage($itemImageInfo);
					$itemImageInfo = $oneImageInfo = null;
				}
			}
		}
	}

	/** 
	 * Process a single item image set. There could be multiple sets of images for a given item (I guess), but I 
	 * just ready an Item node in; inside that node are images, I apply them.
	 *
	 * @return int number of images applied.
	 */
	private function _processOneImage($imageInfo)
	{
		$imageId = 0;
		$imagesProcessed = 0;

		$mageProduct = Mage::getModel('catalog/product');
		$productId = $mageProduct->getIdBySku($imageInfo['sku']);
		if($productId) {
			$mageProduct->load($productId);
		}
		else {
			Mage::log( '[' . __CLASS__ . '] SKU not found ' . $imageInfo['sku']);
			return 0;
		}

		$eb2cImage = Mage::getModel(self::EB2CMEDIA_IMAGES_MODEL);
		foreach( $imageInfo['images'] as $image ) {
			if( $this->_cdnDomain ) {
				$imageId = $eb2cImage->getIdByName($productId, $image['imagename'], $image['imageview']);
				if( $imageId ) {
					$eb2cImage->load($imageId);
					echo 'Updating ...  ' . print_r($image, true) . "\n";
				}
				else {
					echo 'Adding ...  ' . print_r($image, true) . "\n";
				}
				$eb2cImage->setProductId($productId)
					->setUpdatedAt(Mage::getModel('core/date')->timestamp(time()))
					->setView($image['imageview'])
					->setName($image['imagename'])
					->setUrl($image['imageurl'])
					->setHeight($image['imageheight'])
					->setWidth($image['imagewidth'])
					->save();
			}
			$imagesProcessed++;
		}
		return $imagesProcessed;
	}
}

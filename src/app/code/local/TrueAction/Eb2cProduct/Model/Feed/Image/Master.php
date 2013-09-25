<?php
/**
 * Product Image Feed
 * The abstract class defines some functions we all want to use.
 * The interface defines how feeds get invoked, which to me is separate from how they are configured
 *  and manage their files, and anyway it's where I started.
 * @todo: Finish this Feed
 */
class TrueAction_Eb2cProduct_Model_Feed_Image_Master
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	const CDN_PROTOCOL = 'http://';
	const MEDIA_ATTR_CODE = 'media_gallery';

	private $_eb2cImageFields;
	private $_cdnDomain;
	private $_rootAttrs = array ('imageDomain',);
	private $_imageAttrs = array('imagewidth', 'imageview', 'imageurl', 'imagename', 'imageheight');


	public function catalogProductMediaSaveBeforeObserver($event)
	{
		$product = $event->getProduct();
		$foo = print_r($event->getImages(), true);
		return;
	}

	protected function _construct()
	{
		$this->setFeedConfig(Mage::helper('eb2cproduct')->getConfigModel());

		$this->setFeedRemotePath  ($this->getFeedConfig()->imageFeedRemoteReceivedPath);
		$this->setFeedFilePattern ($this->getFeedConfig()->imageFeedFilePattern);
		$this->setFeedLocalPath   ($this->getFeedConfig()->imageFeedLocalPath);
		$this->setFeedEventType   ($this->getFeedConfig()->imageFeedEventType);

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

		foreach( $imageInfo['images'] as $image ) {
			if( $this->_cdnDomain ) {
				echo 'Adding ...  ' . print_r($image, true) . "\n";
				$this->_addRemoteImage($mageProduct, $image, 'image', false);
			}
			$imagesProcessed++;
		}
		return $imagesProcessed;
	}

	/**
	 * Add image to media gallery and return new filename
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param string                     $file              file name
	 * @param string|array               $mediaAttribute    code of attribute with type 'media_image',
	 *                                                      leave blank if image should be only in gallery
	 * @param boolean                    $exclude           mark image as disabled in product page view
	 * @return string
	 */
	private function _addRemoteImage(Mage_Catalog_Model_Product $product, $eb2cImageInfo, $mediaAttribute=null, $exclude=true)
	{
		$file  = $eb2cImageInfo['imageurl'];
		$label = $eb2cImageInfo['imagename'];
		Mage::dispatchEvent('catalog_product_media_add_image', array('product' => $product, 'image' => $file));

		$pathinfo = pathinfo($file);
		$imgExtensions = array('jpg','jpeg','gif','png');
		if (!isset($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), $imgExtensions)) {
			Mage::throwException(Mage::helper('catalog')->__('Invalid image file type.'));
		}

		$mediaGalleryData = $product->getData(self::MEDIA_ATTR_CODE);
		$position = 0;
		if (!is_array($mediaGalleryData)) {
			$mediaGalleryData = array(
				'images' => array()
			);
		}

		$this->_eb2cImageFields = array();
		foreach( $eb2cImageInfo as $eb2cKey => $eb2cValue ) {
			$this->_eb2cImageFields['eb2c_' . $eb2cKey] = $eb2cValue;	
		}

		foreach ($mediaGalleryData['images'] as &$image) {
			if (isset($image['position']) && $image['position'] > $position) {
				$position = $image['position'];
			}
		}

		$position++;
		$mediaGalleryData['images'][] = array_merge(
			array(
				'file'     => $file,
				'position' => $position,
				'label'    => $label,
				'disabled' => (int) $exclude
			),
			$this->_eb2cImageFields
		);

		$product->setData(self::MEDIA_ATTR_CODE, $mediaGalleryData);
		$pid = $product->getId();
		$product->save();

		$product->load($pid);
		$gallery = $product->getMediaGalleryImages();
		foreach($gallery as $galleryImage) {
			echo print_r($galleryImage,true);
		}

		if (!is_null($mediaAttribute)) {
			$this->setMediaAttribute($product, $mediaAttribute, $file);
		}
		return $file;
	}
}

<?php
/**
 * Product Image Feed
 * The abstract class defines some functions we all want to use.
 * The interface defines how feeds get invoked, which to me is separate from how they are configured
 *  and manage their files, and anyway it's where I started.
 * @todo: Finish this Feed
 */
class TrueAction_Eb2cProduct_Model_Feed_Image_Master
	extends TrueAction_Eb2cProduct_Model_Feed_Image_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	private $_imageAttrs = array('imagewidth', 'imageview', 'imageurl', 'imagename', 'imageheight');

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

		// ItemImages is the root node of each Image Feed file. I don't know if we really care. I just need sku and image info.
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

	/** 
	 * Process a single item image set. There could be multiple sets of images for a given item (I guess), but I 
	 * just ready an Item node in; inside that node are images, I apply them.
	 *
	 * @return int number of images applied.
	 */
	private function _processOneImage($imageInfo)
	{
		$imagesProcessed = 0;

		foreach( $imageInfo['images'] as $image ) {
			$imagesProcessed++;
		}
		return $imagesProcessed;
	}
}

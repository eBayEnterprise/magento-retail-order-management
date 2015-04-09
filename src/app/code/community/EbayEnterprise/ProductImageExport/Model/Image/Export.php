<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Export images "the best we can"
 */
class EbayEnterprise_ProductImageExport_Model_Image_Export extends Varien_Object
{
	const LAST_RUN_DATETIME_KEY = 'image_export_last_run_datetime';
	const XML_TEMPLATE = '<%1$s imageDomain="%2$s" clientId="%3$s" timestamp="%4$s">%5$s</%1$s>';
	const ROOT_NODE = 'ItemImages';
	const ID_PLACE_HOLDER = '{current_store_id}';
	const FRONTEND_INPUT = 'media_image';
	const FILTER_OUT_VALUE = 'no_selection';
	const SKU_MAX_LENGTH = 15;

	/** @var string filter in only product where the updated_at is after the last run date time */
	protected $_lastRunDateTime;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;
	/** @var EbayEnterprise_Catalog_Helper_Data */
	protected $_catalogHelper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	public function __construct(array $initParams=[])
	{
		list($this->_coreHelper, $this->_catalogHelper, $this->_config) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'catalog_helper', Mage::helper('ebayenterprise_catalog')),
			$this->_nullCoalesce($initParams, 'config', Mage::helper('ebayenterprise_catalog')->getConfigModel())
		);
	}
	/**
	 * Type hinting for self::__construct $initParams
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @param  EbayEnterprise_Catalog_Helper_Data
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_Catalog_Helper_Data $catalogHelper,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config
	) {
		return [$coreHelper, $catalogHelper, $config];
	}
	/**
	 * Return the value of field in array if it exists. Otherwise, use the default value.
	 * @param  array
	 * @param  string|int $field Valid array key
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * Builds the Image Export DOM - creates the export file, validates the schema, and then sends it.
	 * @return void
	 */
	public function process()
	{
		$startDateTime = $this->_coreHelper->getNewDateTime()->format('c');
		foreach (array_keys($this->_catalogHelper->getStores()) as $storeId) {
			$this->_buildExport($storeId, $startDateTime);
		}
		$this->_updateExportLastRunDatetime($startDateTime);
	}
	/**
	 * build image feed per store
	 *
	 * @param  int $storeId
	 * @param  string
	 * @return self
	 */
	protected function _buildExport($storeId, $startDateTime)
	{
		$imageData = $this->_getImageData($storeId, $startDateTime);
		if (!empty($imageData)) {
			$this->_catalogHelper->setCurrentStore($storeId);
			$this->_buildItemImages($this->_loadDom($storeId), $storeId, $imageData);
			$this->_catalogHelper->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		}
		return $this;
	}
	/**
	 * load the preliminary data into EbayEnterprise_Dom_Document object and then return
	 * a EbayEnterprise_Dom_Document object
	 * @param int $storeId
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _loadDom($storeId)
	{
		$doc = $this->_coreHelper->getNewDomDocument();
		$doc->loadXml(sprintf(
			self::XML_TEMPLATE,
			self::ROOT_NODE,
			$this->_getCurrentHostName($storeId),
			$this->_coreHelper->getConfigModel()->clientId,
			Mage::getModel('core/date')->date('c'),
			$this->_catalogHelper->generateMessageHeader($this->_config->imageFeedEventType),
			$this->_coreHelper->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
		));

		return $doc;
	}
	/**
	 * get the current host name
	 * @param int $storeId
	 * @return string
	 */
	protected function _getCurrentHostName($storeId)
	{
		$domainParts = parse_url($this->_catalogHelper->getStoreUrl($storeId));
		return $domainParts['host'];
	}
	/**
	 * Create a file from the dom, and return its full path.
	 * 'protected' so we can test around it.
	 * @param EbayEnterprise_Dom_Document $dom
	 * @param int $storeId
	 * @return self
	 */
	protected function _createFileFromDom(EbayEnterprise_Dom_Document $dom, $storeId)
	{
		$dom->save($this->_generateFilePath($storeId));
		return $this;
	}
	/**
	 * @return EbayEnterprise_Catalog_Model_Feed_Core
	 */
	protected function _getFeedCore()
	{
		return Mage::getModel('ebayenterprise_catalog/feed_core', [
			'feed_config' => $this->_config->imageFeed
		]);
	}
	/**
	 * generate image feed file name
	 * @param string $storeId
	 * @return string
	 */
	protected function _generateFilePath($storeId)
	{
		$coreFeed = $this->_getFeedCore();
		return $coreFeed->getLocalDirectory() . DS . str_replace(
			self::ID_PLACE_HOLDER,
			$storeId,
			$this->_catalogHelper->generateFileName(
				$this->_config->imageFeedEventType,
				$this->_config->imageExportFilenameFormat
			)
		);
	}
	/**
	 * Build an item's worth of images
	 *
	 * @param EbayEnterprise_Dom_Document $doc into which itemImages are placed
	 * @param int $storeId
	 * @param array $imageData
	 * @return self
	 */
	protected function _buildItemImages(EbayEnterprise_Dom_Document $doc, $storeId, array $imageData)
	{
		$this->_buildXmlNodes($doc, $imageData)
			->_validateXml($doc)
			->_createFileFromDom($doc, $storeId);

		return $this;
	}
	/**
	 * validate the dom xml
	 * @param EbayEnterprise_Dom_Document $doc
	 * @return self
	 */
	protected function _validateXml(EbayEnterprise_Dom_Document $doc)
	{
		Mage::getModel('eb2ccore/api')->schemaValidate(
			$doc, $this->_config->imageExportXsd
		);

		return $this;
	}
	/**
	 * build the xml node base on the image data given and the DOMDocument object given
	 * @param EbayEnterprise_Dom_Document
	 * @param array $imageData
	 * @return self
	 */
	protected function _buildXmlNodes(EbayEnterprise_Dom_Document $doc, array $imageData)
	{
		$itemImages = $this->_coreHelper->getDomElement($doc);
		foreach ($imageData as $data){
			$this->_buildImagesNodes(
				$itemImages->createChild('Item', null, ['id' => $data['id']]),
				$data['image_data']
			);
		}
		return $this;
	}
	/**
	 * build images/image nodes
	 * @param EbayEnterprise_Dom_Element $item
	 * @param array $imageData
	 * @return self
	 */
	protected function _buildImagesNodes(EbayEnterprise_Dom_Element $item, array $imageData)
	{
		$images = $item->createChild('Images');
		foreach ($imageData as $image) {
			$images->createChild('Image', null, [
				'imageview' => $image['view'],
				'imagename' => $image['name'],
				'imageurl' => $image['url'],
				'imagewidth' => $image['width'],
				'imageheight' => $image['height']
			]);
		}
		return $this;
	}
	/**
	 * get product image data for product that doesn't exceed the self::SKU_MAX_LENGTH
	 * @param  int
	 * @param  string
	 * @return array
	 * Example: [
	 *   [
	 *      'id' => 'Some Product Sku'
	 *      'image_data' => [
	 *         [
	 *           'view' => 'small',
	 *           'name' => 'Some image label',
	 *           'url' => 'http://example.com/media/catalog/small.jpg',
	 *           'width' => 500,
	 *           'height' => 500
	 *         ]
	 *         ...
	 *       ]
	 *   ]
	 *   ...
	 * )
	 */
	protected function _getImageData($storeId, $startDateTime)
	{
		$data = [];
		foreach ($this->_getProductCollection($storeId, $startDateTime) as $product) {
			if (strlen($product->getSku()) <= self::SKU_MAX_LENGTH) {
				$data[] = $this->_extractImageData($product->load($product->getId()));
			}
		}
		return array_filter($data);
	}
	/**
	 * extracting image data from a given Mage_Catalog_Model_Product object
	 * @param Mage_Catalog_Model_Product $product
	 * @return array | null
	 */
	protected function _extractImageData(Mage_Catalog_Model_Product $product)
	{
		$media = $product->getMediaGalleryImages();

		return ($media && $media->count())?
			[
				'id' => $product->getSku(),
				'image_data' => $this->_getMediaData($media, $product)
			] : null;
	}
	/**
	 * extracting image data from a given Mage_Catalog_Model_Product object
	 * @param Varien_Data_Collection $media
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */
	protected function _getMediaData(Varien_Data_Collection $media, Mage_Catalog_Model_Product $product)
	{
		$mData = [];
		$imageViews = $this->_filterImageViews($this->_getMageImageViewMap($product));
		foreach ($media as $mageImage) {
			$dimension = $this->_getImageDimension($mageImage);
			foreach ($imageViews as $view) {
				$mData[] = [
					'view' => $view,
					'name' => $mageImage->getLabel(),
					'url' => $mageImage->getUrl(),
					'width' => $dimension['width'],
					'height' => $dimension['height']
				];
			}
		}
		return $mData;
	}
	/**
	 * get image dimensions
	 *
	 * @param Varien_Object $mageImage
	 * @return array
	 */
	protected function _getImageDimension(Varien_Object $mageImage)
	{
		return array_combine(['width', 'height'], array_slice(getimagesize(
			file_exists($mageImage->getPath()) ? $mageImage->getPath() : $mageImage->getUrl()
		), 0, 2));
	}
	/**
	 * get a collection of product per store
	 * @param  int
	 * @param  string
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getProductCollection($storeId, $startDateTime)
	{
		$lastRunDatetime = $this->_config->imageExportLastRunDatetime;
		$collection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect(['*'])
			->addStoreFilter($storeId);
		if ($startDateTime) {
			$collection->addFieldToFilter('updated_at', ['lt' => $startDateTime]);
		}
		if ($lastRunDatetime) {
			$collection->addFieldToFilter('updated_at', ['gt' => $lastRunDatetime]);
		}
		$collection->load();
		return $collection;
	}
	/**
	 * Searches for all media_image type attributes for this product's attribute set, and creates a hash matching
	 * the attribute code to its value, which is a media path. The attribute code is used as the
	 * image 'view', and we use array_search to match based on media path.
	 * @param Mage_Catalog_Model_Product $mageProduct
	 * @return array of view_names => image_paths
	 */
	protected function _getMageImageViewMap(Mage_Catalog_Model_Product $mageProduct)
	{
		$attributes = $mageProduct->getAttributes();
		return array_reduce(array_keys($attributes), function($result=[], $key) use($attributes, $mageProduct) {
			if (!strcmp($attributes[$key]->getFrontendInput(), EbayEnterprise_ProductImageExport_Model_Image_Export::FRONTEND_INPUT)) {
				$result[$key] = $mageProduct->getData($key);
			}
			return $result;
		});
	}
	/**
	 * given an array of image view filter out any key with value self::FILTER_OUT_VALUE
	 * return any key without self::FILTER_OUT_VALUE value if empty return an array with one empty string element
	 * @param array $imageViews
	 * @return array
	 */
	protected function _filterImageViews(array $imageViews)
	{
		$views = array_filter(array_keys($imageViews), function($key) use ($imageViews) {
			return ($imageViews[$key] !== EbayEnterprise_ProductImageExport_Model_Image_Export::FILTER_OUT_VALUE);
		});

		return !empty($views)? $views : [''];
	}
	/**
	 * @return Mage_Core_Model_Config_Data
	 */
	protected function _getConfigData()
	{
		return Mage::getModel('core/config_data');
	}
	/**
	 * @return EbayEnterprise_Catalog_Model_Config
	 */
	protected function _getCalogConfig()
	{
		return Mage::getSingleton('ebayenterprise_catalog/config');
	}
	/**
	 * @param  string
	 * @return self
	 */
	protected function _updateExportLastRunDatetime($lastRunDatetime)
	{
		$path = $this->_getCalogConfig()->getPathForKey(static::LAST_RUN_DATETIME_KEY);
		$this->_updateConfig($path, $lastRunDatetime);
		return $this;
	}
	/**
	 * @param  string
	 * @param  string
	 * @return self
	 */
	protected function _updateConfig($path, $value)
	{
		$this->_getConfigData()->addData([
			'path' => $path,
			'value' => $value,
			'scope' => 'default',
			'scope_id' => 0,
		])->save();
		return $this;
	}
}

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

class EbayEnterprise_ProductImageExport_Test_Model_Image_ExportTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::process method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::process given a
	 *                a null value as the sourceStoreId parameter and expect the method
	 *                EbayEnterprise_ProductImageExport_Helper_Data::getStores to be called and run an array of stores
	 *                entity_id key values then expect the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildExport
	 *                to be invoked given the initial call and the store id
	 */
	public function testProccess()
	{
		$storeId = 5;
		$startDateTime = '2015-04-07T20:22:13+00:00';
		$stores = array($storeId => Mage::getModel('core/store'));

		$helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('getStores'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue($stores));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_buildExport', '_updateExportLastRunDatetime'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_buildExport')
			->will($this->returnSelf());
		$exportMock->expects($this->once())
			->method('_updateExportLastRunDatetime')
			->will($this->returnSelf());

		$exportMock->process();
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_buildExport method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildExport
	 *                an value of zero for the processed parameter and an integer value for the store id parameter
	 *                expects the following things to happen: the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageData
	 *                is expected to be invoked a given the given store id which will return an array of product image data
	 *                then the method EbayEnterprise_ProductImageExport_Helper_Data::setCurrentStore given the stored id, next
	 *                the method EbayEnterprise_ProductImageExport_Model_Image_Export::_loadDom is invoked given the storeId returned
	 *                an EbayEnterprise_Dom_Document object, then the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildItemImages
	 *                is called given the return value from the method EbayEnterprise_ProductImageExport_Model_Image_Export::_loadDom
	 *                as first parameter, the given store id as second parameter and last the product image data, then
	 *                the method EbayEnterprise_ProductImageExport_Helper_Data::setCurrentStore get invoked a second time given
	 *                the constant value of class Mage_Core_Model_App::ADMIN_STORE_ID
	 */
	public function testBuildExport()
	{
		$storeId = 5;
		$startDateTime = '2015-04-07T20:22:13+00:00';
		$imageData = array(array());
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('setCurrentStore'))
			->getMock();
		$helperMock->expects($this->exactly(2))
			->method('setCurrentStore')
			->will($this->returnValueMap(array(
				array($storeId, $helperMock),
				array(Mage_Core_Model_App::ADMIN_STORE_ID)
			)));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_loadDom', '_getImageData', '_buildItemImages'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_loadDom')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($doc));
		$exportMock->expects($this->once())
			->method('_getImageData')
			->with($this->identicalTo($storeId), $this->identicalTo($startDateTime))
			->will($this->returnValue($imageData));
		$exportMock->expects($this->once())
			->method('_buildItemImages')
			->with($this->identicalTo($doc), $this->identicalTo($storeId), $imageData)
			->will($this->returnValue(count($imageData)));

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_buildExport', array($storeId, $startDateTime)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_loadDom method for the following expectations
	 * Expectation 1: the method EbayEnterprise_ProductImageExport_Model_Image_Export::_loadDom will be invoked by this test
	 *                given a known processed value and a known store id value in which the method
	 *                EbayEnterprise_ProductImageExport_Helper_Data::getConfigModel will be called and return a mocked
	 *                EbayEnterprise_Eb2cCore_Model_Config_Registry object, then the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getNewDomDocument will be invoked and return mocked
	 *                EbayEnterprise_Dom_Document object, then the method EbayEnterprise_Dom_Document::loadXml get invoked
	 *                the value generated by the sprintf method in which take value from class constant, return value from
	 *                the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getCurrentHostName given the store id
	 *                then value from EbayEnterprise_ProductImageExport_Helper_Data::generateMessageHeader given the event type
	 *                value from the EbayEnterprise_Eb2cCore_Model_Config_Registry object, last the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getBaseUrl get called given the Mage_Core_Model_Store::URL_TYPE_MEDIA
	 *                class constant
	 */
	public function testLoadDom()
	{
		$storeId = 3;
		$cfg = array('clientId' => 'WNS', 'imageFeedEventType' => 'ImageMaster');
		$messageHeader = '<MessageHeader>...</MessageHeader>';
		$host = 'example.com';
		$dateTime = '2014-04-07T09:09:14+00:00';
		$mediaUrl = '';
		$initialXml = sprintf(
			EbayEnterprise_ProductImageExport_Model_Image_Export::XML_TEMPLATE,
			EbayEnterprise_ProductImageExport_Model_Image_Export::ROOT_NODE,
			$host,
			$cfg['clientId'],
			$dateTime,
			$messageHeader,
			$mediaUrl
		);

		$helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'generateMessageHeader'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'imageFeedEventType' => $cfg['imageFeedEventType']
			))));
		$helperMock->expects($this->once())
			->method('generateMessageHeader')
			->with($this->identicalTo($cfg['imageFeedEventType']))
			->will($this->returnValue($messageHeader));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('loadXml'))
			->getMock();
		$docMock->expects($this->once())
			->method('loadXml')
			->with($this->identicalTo($initialXml))
			->will($this->returnValue(true)); // successfully loaded

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument', 'getBaseUrl', 'getConfigModel'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($docMock));
		$coreHelperMock->expects($this->once())
			->method('getBaseUrl')
			->with($this->identicalTo(Mage_Core_Model_Store::URL_TYPE_MEDIA))
			->will($this->returnValue($mediaUrl));
		$coreHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'clientId' => $cfg['clientId']
			))));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$dateMock = $this->getModelMockBuilder('core/date')
			->disableOriginalConstructor()
			->setMethods(array('date'))
			->getMock();
		$dateMock->expects($this->once())
			->method('date')
			->with($this->identicalTo('c'))
			->will($this->returnValue($dateTime));
		$this->replaceByMock('model', 'core/date', $dateMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_getCurrentHostName'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_getCurrentHostName')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($host));

		$this->assertSame($docMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_loadDom', array($storeId)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getCurrentHostName method for the following expectations
	 * Expectation 1: the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getCurrentHostName will be invoked by
	 *                this test given a store id and expect the method EbayEnterprise_ProductImageExport_Helper_Data::getStoreUrl
	 *                to be invoked given the store id and will return a known URL which will be passed to the built-in
	 *                parse_url method which will return an array contain the key 'host' which will be the return value
	 *                the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getCurrentHostName
	 */
	public function testGetCurrentHostName()
	{
		$storeId = 8;
		$host = 'example.com';
		$storeUrl = "http://$host";

		$helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreUrl'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getStoreUrl')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeUrl));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(null)
			->getMock();

		$this->assertSame($host, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getCurrentHostName', array($storeId)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_createFileFromDom method for the following expectations
	 * Expectation 1: the method EbayEnterprise_ProductImageExport_Model_Image_Export::_createFileFromDom will be invoked by this
	 *                test given a mock EbayEnterprise_Dom_Document object and a store id in which expect the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_generateFilePath to be called given the store id
	 *                which will return a known string, this know string value will be passed to the method
	 *                EbayEnterprise_Dom_Document::save method and the string then be returned
	 */
	public function testCreateFileFromDom()
	{
		$storeId = 9;
		$filename = 'path/to/out-box/some-file-name.xml';
		$nBytes = 10;

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('save'))
			->getMock();
		$docMock->expects($this->once())
			->method('save')
			->with($this->identicalTo($filename))
			->will($this->returnValue($nBytes)); // the number of bytes written the save xml file

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_generateFilePath'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_generateFilePath')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($filename));

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_createFileFromDom', array($docMock, $storeId)
		));
	}


	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_buildItemImages method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildItemImages
	 *                given an EbayEnterprise_Dom_Document object, a store id and an array of image data, then the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_buildXmlNodes will be called given the
	 *                EbayEnterprise_Dom_Document object and the array of image data it will then return itself, then
	 *                the method EbayEnterprise_ProductImageExport_Model_Image_Export::_validateXml will be invoked given the
	 *                EbayEnterprise_Dom_Document object, then the method EbayEnterprise_ProductImageExport_Model_Image_Export::createFileFromDom
	 *                will be invoked given the EbayEnterprise_Dom_Document object and the store id
	 */
	public function testBuildItemImages()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$storeId = 5;
		$imageData = array(array());

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->disableOriginalConstructor()
			->setMethods(array('_buildXmlNodes', '_validateXml', '_createFileFromDom'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_buildXmlNodes')
			->with($this->identicalTo($doc), $this->identicalTo($imageData))
			->will($this->returnSelf());
		$exportMock->expects($this->once())
			->method('_validateXml')
			->with($this->identicalTo($doc))
			->will($this->returnSelf());
		$exportMock->expects($this->once())
			->method('_createFileFromDom')
			->with($this->identicalTo($doc), $this->identicalTo($storeId))
			->will($this->returnSelf());

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_buildItemImages', array($doc, $storeId, $imageData)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_validateXml method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_validateXml given
	 *                an EbayEnterprise_Dom_Document object in which the method EbayEnterprise_Eb2cCore_Model_Api::schemaValidate
	 *                will be invoked given the EbayEnterprise_Dom_Document object and the magic value from the mocked
	 *                EbayEnterprise_Eb2cCore_Model_Config_Registry object which is the return value from calling the method
	 *                EbayEnterprise_ProductImageExport_Helper_Data::getConfigModel
	 */
	public function testValidateXml()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$cfg = array('imageExportXsd' => 'ImageMasterV11.xsd');

		$apiMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('schemaValidate'))
			->getMock();
		$apiMock->expects($this->once())
			->method('schemaValidate')
			->with($this->identicalTo($doc))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/api', $apiMock);

		$helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry($cfg)));
		$this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(null)
			->getMock();

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_validateXml', array($doc)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_buildXmlNodes method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildXmlNodes given
	 *                a mocked EbayEnterprise_Dom_Document object and an array of image data in which the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getDomElement will be called given the mocked
	 *                EbayEnterprise_Dom_Document object, then we expect the given array of image data to be loop through
	 *                in which the method  EbayEnterprise_Dom_Element::createChild will be called given a string literal 'item',
	 *                null, and an array of key 'id' map to the image data key value 'id', which will return an
	 *                EbayEnterprise_Dom_Element in which will be passed to the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_buildImagesNodes as first parameter and the key
	 *                'image_data' from the given image data array as second parameter
	 */
	public function testBuildXmlNodes()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$imageData = array(array('id' => '54-HTSC0083', 'image_data' => array()));

		$elementMock = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('createChild'))
			->getMock();
		$elementMock->expects($this->once())
			->method('createChild')
			->with(
				$this->identicalTo('Item'),
				$this->identicalTo(null),
				$this->identicalTo(array('id' => $imageData[0]['id']))
			)
			->will($this->returnSelf());

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getDomElement'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getDomElement')
			->with($this->identicalTo($doc))
			->will($this->returnValue($elementMock));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_buildImagesNodes'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_buildImagesNodes')
			->with($this->identicalTo($elementMock), $this->identicalTo($imageData[0]['image_data']))
			->will($this->returnSelf());

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_buildXmlNodes', array($doc, $imageData)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_buildImagesNodes method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_buildImagesNodes
	 *                given a mocked EbayEnterprise_Dom_Element object and an array of image data
	 *                then the method EbayEnterprise_Dom_Element::createChild will be invoked given the literal string
	 *                'Images', then the given image data array will be looped through and the method
	 *                EbayEnterprise_Dom_Element::createChild will be given a second time given the literal string 'Image'
	 *                as first parameter, null as second parameter and then an array with keys imageview, imagename, etc...
	 */
	public function testBuildImagesNodes()
	{
		$imageData = array(
			array(
				'view' => 'small',
				'name' => 'Some label',
				'url' => 'http://example.com/media/catalog/small.jpg',
				'width' => 300,
				'height' => 400
			),
		);

		$nodeAttributes = array(
			'imageview' => $imageData[0]['view'],
			'imagename' => $imageData[0]['name'],
			'imageurl' => $imageData[0]['url'],
			'imagewidth' => $imageData[0]['width'],
			'imageheight' => $imageData[0]['height']
		);

		$elementMock = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('createChild'))
			->getMock();
		$elementMock->expects($this->exactly(2))
			->method('createChild')
			->will($this->returnValueMap(array(
				array('Images', null, null, null, $elementMock),
				array('Image', null, $nodeAttributes, null, $elementMock),
			)));

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_buildImagesNodes', array($elementMock, $imageData)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageData method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageData
	 *                given a known store id the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getProductCollection
	 *                will be invoked given the store id and will return a Mage_ProductImageExport_Model_Resource_Product_Collection object
	 *                which will be loop through each loop cycle will return Mage_ProductImageExport_Model_Product object in which
	 *                the method Mage_ProductImageExport_Model_Product::getSku be call and return a known sku in which the length
	 *                will be compare against the EbayEnterprise_ProductImageExport_Model_Image_Export::SKU_MAX_LENGTH class
	 *                constant if sku value is less or equal class constant allow the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_extractImageData be called given the
	 *                Mage_ProductImageExport_Model_Product object in which return image data
	 */
	public function testGetImageData()
	{
		$storeId = 5;
		$startDateTime = '2015-04-07T20:22:13+00:00';
		$data = array(array('id' => '54-HSTS83223', 'image_data' => array(array(),)),);
		$product = Mage::getModel('catalog/product')->addData(array('sku' => $data[0]['id']));

		$collection = $this->getResourceModelMock('catalog/product_collection', array('load'));
		$collection->addItem($product);

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_getProductCollection', '_extractImageData'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_getProductCollection')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($collection));
		$exportMock->expects($this->once())
			->method('_extractImageData')
			->with($this->identicalTo($product))
			->will($this->returnValue($data[0]));

		$this->assertSame($data, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getImageData', array($storeId, $startDateTime)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_extractImageData method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_extractImageData
	 *                given a mocked Mage_ProductImageExport_Model_Product object in which the method
	 *                Mage_ProductImageExport_Model_Product::getMediaGalleryImages will be invoked a return Varien_Data_Collection object
	 *                then the method Varien_Data_Collection::count get call which allowed the method
	 *                Mage_ProductImageExport_Model_Product::getSku to be called and the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_getMediaData to be invoked and given
	 *                the Varien_Data_Collection object and the Mage_ProductImageExport_Model_Product object
	 */
	public function testExtractImageData()
	{
		$imageData = array(array(),);
		$data = array('id' => '54-HSTS83223', 'image_data' => $imageData);

		$media = new Varien_Data_Collection();
		$media->addItem(new Varien_Object());

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getMediaGalleryImages', 'getSku'))
			->getMock();
		$product->expects($this->once())
			->method('getMediaGalleryImages')
			->will($this->returnValue($media));
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue($data['id']));

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_getMediaData'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_getMediaData')
			->with($this->identicalTo($media), $this->identicalTo($product))
			->will($this->returnValue($imageData));

		$this->assertSame($data, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_extractImageData', array($product)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getMediaData method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getMediaData
	 *                given a Varien_Data_Collection object and a Mage_ProductImageExport_Model_Product object in the method
	 *                EbayEnterprise_ProductImageExport_Model_Image_Export::_getMageImageViewMap will be invoked given a
	 *                Mage_ProductImageExport_Model_Product object which return an array of image media attribute codes
	 *                given the attribute code to the method EbayEnterprise_ProductImageExport_Model_Image_Export::_filterImageViews
	 *                when invoked once will return the attribute code without value 'no_selection', the Varien_Data_Collection
	 *                object get loop through which return a Varien_Object per loops, the Varien_Object get passed to
	 *                the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageDimension which return an array
	 *                keys width/height the filtered image views array get loop through and build an array with array keys
	 *                view, name, url, etc...
	 */
	public function testGetMediaData()
	{
		$views = array('image' => 'no_selection', 'small_image' => 'no_selection', 'thumbnail' => 'selected');
		$filterView = array('thumbnail');
		$dimension = array('width' => 500, 'height' => 600);

		$imageData = array(
			'label' => 'some label',
			'url' => 'http://example.com/to/some/thumbnail.jpg',
		);

		$data = array(array(
			'view' => $filterView[0],
			'name' => $imageData['label'],
			'url' => $imageData['url'],
			'width' => $dimension['width'],
			'height' => $dimension['height']
		));

		$item = new Varien_Object($imageData);
		$media = new Varien_Data_Collection();
		$media->addItem($item);

		$product = Mage::getModel('catalog/product');

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(array('_getMageImageViewMap', '_filterImageViews', '_getImageDimension'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_getMageImageViewMap')
			->with($this->identicalTo($product))
			->will($this->returnValue($views));
		$exportMock->expects($this->once())
			->method('_filterImageViews')
			->with($this->identicalTo($views))
			->will($this->returnValue($filterView));
		$exportMock->expects($this->once())
			->method('_getImageDimension')
			->with($this->identicalTo($item))
			->will($this->returnValue($dimension));

		$this->assertSame($data, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getMediaData', array($media, $product)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getProductCollection method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getProductCollection
	 *                given a store id and expects the following methods to be invoked
	 *                Mage_ProductImageExport_Model_Resource_Product_Collection::addAttributeToSelect, addStoreFilter, and load
	 */
	public function testGetProductCollection()
	{
		$storeId = 5;
		$startDateTime = '2015-04-08T20:22:13+00:00';
		$lastRunDateTime = '2015-04-07T20:22:13+00:00';
		$collection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addStoreFilter', 'addFieldToFilter', 'load'))
			->getMock();
		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo(array('*')))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addStoreFilter')
			->with($this->identicalTo($storeId))
			->will($this->returnSelf());
		$collection->expects($this->exactly(2))
			->method('addFieldToFilter')
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);

		$export = Mage::getModel('ebayenterprise_productimageexport/image_export', [
			'config' => $this->buildCoreConfigRegistry(['imageExportLastRunDatetime' => $lastRunDateTime])
		]);

		$this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$export, '_getProductCollection', array($storeId, $startDateTime)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getMageImageViewMap method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getMageImageViewMap
	 *                given a mocked Mage_ProductImageExport_Model_Product object and expects the following methods to be invoked
	 *                Mage_ProductImageExport_Model_Product::getAttributes, Mage_ProductImageExport_Model_Resource_Eav_Attribute::getFrontendInput,
	 *                and Mage_ProductImageExport_Model_Product::getData
	 */
	public function testGetMageImageViewMap()
	{
		$attribute = $this->getResourceModelMockBuilder('catalog/eav_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getFrontendInput'))
			->getMock();
		$attribute->expects($this->once())
			->method('getFrontendInput')
			->will($this->returnValue(EbayEnterprise_ProductImageExport_Model_Image_Export::FRONTEND_INPUT));

		$key = 'image';
		$value = 'no_selection';
		$attributes = array($key => $attribute);
		$result = array($key => $value);

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getAttributes', 'getData'))
			->getMock();
		$product->expects($this->once())
			->method('getAttributes')
			->will($this->returnValue($attributes));
		$product->expects($this->once())
			->method('getData')
			->with($this->identicalTo($key))
			->will($this->returnValue($value));

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getMageImageViewMap', array($product)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageDimension method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_getImageDimension
	 *                given a Varien_Object object
	 * @dataProvider dataProvider
	 */
	public function testGetImageDimension($data)
	{
		$result = array('width' => 28, 'height' => 18);
		$mageImage = new Varien_Object(array(
			'path' => null, 'url' => "data://application/octet-stream;base64,$data"
		));

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getImageDimension', array($mageImage)
		));
	}

	/**
	 * Test EbayEnterprise_ProductImageExport_Model_Image_Export::_filterImageViews method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_ProductImageExport_Model_Image_Export::_filterImageViews
	 *                given an array of image views and expects to return a known array of data
	 */
	public function testFilterImageViews()
	{
		$imageViews = array(
			'image' => EbayEnterprise_ProductImageExport_Model_Image_Export::FILTER_OUT_VALUE,
			'small_image' => EbayEnterprise_ProductImageExport_Model_Image_Export::FILTER_OUT_VALUE,
			'thumbnail' => 'selected'
		);

		$result = array('2' => 'thumbnail');

		$exportMock = $this->getModelMockBuilder('ebayenterprise_productimageexport/image_export')
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_filterImageViews', array($imageViews)
		));
	}
}

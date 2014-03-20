<?php

class TrueAction_Eb2cProduct_Test_Model_PimTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Should create an internal DOMDocument to be used to build the feed file
	 * unless one is provided in a 'dom' key in the parameters arg passed to the
	 * constructor.
	 * @test
	 */
	public function testConstructor()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->getMock();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_setUpCoreFeed'))
			->getMock();
		$pim->expects($this->once())
			->method('_setUpCoreFeed')
			->will($this->returnValue($coreFeed));
		$pim->__construct();
		$this->assertInstanceOf(
			'TrueAction_Dom_Document',
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_doc')
		);
		$this->assertSame(
			$coreFeed,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed')
		);
	}
	/**
	 * Test constructing the PIM Model passing it dependencies. Should expect the
	 * core feed model to be completely intialized - never call _setUpCoreFeed
	 * @test
	 */
	public function testConstructorWithArgs()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$doc = new TrueAction_Dom_Document();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_setUpCoreFeed'))
			->getMock();
		$pim->expects($this->never())->method('_setUpCoreFeed');

		$pim->__construct(array('doc' => $doc, 'core_feed' => $coreFeed));
		$this->assertInstanceOf(
			'TrueAction_Dom_Document',
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_doc')
		);
		$this->assertSame(
			$coreFeed,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed')
		);
	}
	/**
	 * If the constructor is called with a params argument containing s 'doc' key
	 * value pair, the value must be a TrueAction_Dom_Document, else an error
	 * (converted to an Exception in the test) will be triggered.
	 * @test
	 */
	public function testConstructorBadDomError()
	{
		$this->setExpectedException('Exception', 'User Error: TrueAction_Eb2cProduct_Model_Pim::__construct called with invalid doc. Must be instance of TrueAction_Dom_Document');
		Mage::getModel('eb2cproduct/pim', array('doc' => 'Fish'));
	}
	/**
	 * Create feed data set from product collection.
	 * Create XML from feed data.
	 * Write out file.
	 * Return path to file created.
	 * @test
	 */
	public function testBuildFeed()
	{
		$pathToFile = 'path/to/export/file.xml';
		$feedData = $this->getModelMock('eb2cproduct/pim_product_collection', array('getItems'));
		$productIds = array(1, 2, 3, 4, 54);
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->getMock();

		$feedDoc = $this->getMock('TrueAction_Dom_Document', array('save'));
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->setMethods(array('_createFeedDataSet', '_createDomFromFeedData', '_getFeedFilePath'))
			->setConstructorArgs(array(array('doc' => $feedDoc, 'core_feed' => $coreFeed)))
			->getMock();

		$pim->expects($this->once())
			->method('_createFeedDataSet')
			->with($this->identicalTo($productIds))
			->will($this->returnValue($feedData));
		$pim->expects($this->once())
			->method('_createDomFromFeedData')
			->with($this->identicalTo($feedData))
			->will($this->returnValue($feedDoc));
		$pim->expects($this->once())
			->method('_getFeedFilePath')
			->will($this->returnValue($pathToFile));
		$feedDoc->expects($this->once())
			->method('save')
			->with($this->identicalTo($pathToFile))
			->will($this->returnValue(12));

		$this->assertSame($pathToFile, $pim->buildFeed($productIds));
	}
	/**
	 * Create a new PIM Product Collection to contain all of the PIM Product
	 * instances that will be used to build the feed. For each store view, create
	 * a new product collection within that store view of all products in the
	 * provided collection. Then, for each product in the collection, get the
	 * existing PIM Product from the collection or create a new instance and add
	 * it to the collection. Then, update the PIM Product model with the product
	 * in the specific store scope. Finally, return the collection.
	 * @test
	 */
	public function testCreateFeedDataSet()
	{
		$productIds = array(1);

		$store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->getMock();
		$stores = array($store);

		$pimProductCollection = $this->getModelMock('eb2cproduct/pim_product_collection');
		$langHelper = $this->getHelperMock('eb2ccore/languages', array('getStores'));
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_createProductCollectionForStore', '_processProductCollection'))
			->getMock();

		$productCollection = $this->getResourceModelMock('catalog/product_collection', array('load'));

		$this->replaceByMock('helper', 'eb2ccore/languages', $langHelper);
		$this->replaceByMock('model', 'eb2cproduct/pim_product_collection', $pimProductCollection);

		$langHelper->expects($this->once())
			->method('getStores')
			->will($this->returnValue($stores));
		$pim->expects($this->once())
			->method('_createProductCollectionForStore')
			->with($this->identicalTo($productIds), $this->identicalTo($store))
			->will($this->returnValue($productCollection));
		$pim->expects($this->once())
			->method('_processProductCollection')
			->with(
				$this->identicalTo($productCollection),
				$this->identicalTo($pimProductCollection)
			)
			->will($this->returnValue($pimProductCollection));
		$this->assertSame(
			$pimProductCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_createFeedDataSet', array($productIds))
		);
	}
	/**
	 * Process all of the products within a given store.
	 * @test
	 * @dataProvider provideTrueFalse
	 */
	public function testProcessProductCollection($isInCollection)
	{
		$storeId = 1;
		$doc = new TrueAction_Dom_Document();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $doc);
		$pimProductCollection = $this->getModelMock(
			'eb2cproduct/pim_product_collection',
			array('getItemForProduct', 'addItem')
		);
		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('loadPimAttributesByProduct'))
			->getMock();

		$product = $this->getModelMock('catalog/product');
		$productCollection = $this->getResourceModelMock('catalog/product_collection', array('getItems', 'getStoreId'));

		$this->replaceByMock('model', 'eb2cproduct/pim_product', $pimProduct);

		$productCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue(array($product)));
		$productCollection->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));
		$pimProduct->expects($this->once())
			->method('loadPimAttributesByProduct')
			->with($this->identicalTo($product), $this->identicalTo($doc))
			->will($this->returnSelf());

		$config = $this->buildCoreConfigRegistry(array(
			'client_id' => 'theclientid', 'catalog_id' => 'thecatalogid'
		));

		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$prodHelper->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($config));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		if ($isInCollection) {
			$pimProductCollection->expects($this->once())
				->method('getItemForProduct')
				->with($this->identicalTo($product))
				->will($this->returnValue($pimProduct));
			$pimProductCollection->expects($this->never())
				->method('addItem');
		} else {
			$pimProductCollection->expects($this->once())
				->method('getItemForProduct')
				->with($this->identicalTo($product))
				->will($this->returnValue(null));
			$pimProductCollection->expects($this->once())
				->method('addItem')
				->with($this->identicalTo($pimProduct))
				->will($this->returnSelf());
		}

		$this->assertSame(
			$pimProductCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_processProductCollection',
				array($productCollection, $pimProductCollection)
			)
		);
	}
	/**
	 * Initialize the DOM Document with a root node and message header.
	 * @test
	 */
	public function testStartDocument()
	{
		$doc = new TrueAction_Dom_Document();
		$helper = $this->getHelperMock('eb2cproduct/data', array('generateMessageHeader'));
		$this->replaceByMock('helper', 'eb2cproduct', $helper);
		$mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$helper->expects($this->once())
			->method('generateMessageHeader')
			->with($this->identicalTo('PIMExport'))
			->will($this->returnValue('<MessageHeader/>'));
		$pim = Mage::getModel('eb2cproduct/pim', array('doc' => $doc, 'core_feed' => $coreFeed));
		$this->assertSame($pim, EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_startDocument', array()));
		$this->assertSame(
			'<MageMaster imageDomain="' . $mediaUrl . '"><MessageHeader></MessageHeader></MageMaster>',
			$doc->C14N()
		);
	}
	/**
	 * Create the DOMDocument representing the feed. Should initialize (add root
	 * node and message header) the DOMDocument, build out and append fragments
	 * for each item to be included, and validate the resulting DOM.
	 * @test
	 */
	public function testCreateDomFromFeedData()
	{
		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')->disableOriginalConstructor()->getMock();
		$feedDoc = new TrueAction_Dom_Document();
		$feedDoc->loadXML('<root></root>');
		$itemFragment = $feedDoc->createDocumentFragment();
		$itemFragment->appendChild($feedDoc->createElement('Item'));

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$pimProductCollection = $this->getModelMock('eb2cproduct/pim_product_collection', array('getItems'));
		$pim = $this->getModelMock(
			'eb2cproduct/pim',
			array('_buildItemNode', '_startDocument', '_validateDocument'),
			false,
			array(array('doc' => $feedDoc, 'core_feed' => $coreFeed))
		);

		$pimProductCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue(array($pimProduct)));
		$pim->expects($this->once())
			->method('_startDocument')
			->will($this->returnValue($feedDoc->documentElement));
		$pim->expects($this->once())
			->method('_buildItemNode')
			->with($this->identicalTo($pimProduct))
			->will($this->returnValue($itemFragment));
		$pim->expects($this->once())
			->method('_validateDocument')
			->will($this->returnSelf());

		$this->assertSame(
			$feedDoc,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_createDomFromFeedData', array($pimProductCollection))
		);
		$this->assertSame(
			'<root><Item></Item></root>',
			$feedDoc->C14N()
		);
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * For each pim attribute model given, append DOM to represent the value.
	 * @test
	 */
	public function testBuildItemNode()
	{
		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttributes = array($pimAttribute,);
		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('getCatalogId', 'getClientId', 'getPimAttributes'))
			->getMock();
		$feedDoc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createDocumentFragment', 'createElement'))
			->getMock();
		$itemFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->setMethods(array('appendChild'))
			->getMock();
		$itemNode = $this->getMockBuilder('TrueAction_Dom_Element')
			->setMethods(array('addAttributes'))
			->disableOriginalConstructor()
			->getMock();
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$pim = $this->getModelMock(
			'eb2cproduct/pim',
			array('_appendAttributeValue'),
			false,
			array(array('doc' => $feedDoc, 'core_feed' => $coreFeed))
		);

		$feedDoc->expects($this->once())
			->method('createDocumentFragment')
			->will($this->returnValue($itemFragment));
		$feedDoc->expects($this->once())
			->method('createElement')
			->with($this->identicalTo('Item'))
			->will($this->returnValue($itemNode));
		$itemFragment->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($itemNode))
			->will($this->returnValue($itemNode));
		$itemNode->expects($this->once())
			->method('addAttributes')
			->with($this->identicalTo(array('gsi_client_id' => 'clientId', 'catalog_id' => 'catId')))
			->will($this->returnSelf());
		$pimProduct->expects($this->once())
			->method('getCatalogId')
			->will($this->returnValue('catId'));
		$pimProduct->expects($this->once())
			->method('getClientId')
			->will($this->returnValue('clientId'));
		$pimProduct->expects($this->once())
			->method('getPimAttributes')
			->will($this->returnValue($pimAttributes));
		$pim->expects($this->once())
			->method('_appendAttributeValue')
			->with($this->identicalTo($itemNode), $this->identicalTo($pimAttribute))
			->will($this->returnSelf());

		$this->assertSame(
			$itemFragment,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_buildItemNode', array($pimProduct))
		);
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given TrueAction_Dom_Element.
	 * @test
	 */
	public function testAppendAttributeValue()
	{
		$xmlDest = 'BaseAttrubtes/Something';
		$valueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$importedValueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();

		$languageCode = 'en-us';

		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute->destinationXpath = $xmlDest;
		$pimAttribute->sku = '45-12345';
		$pimAttribute->language = $languageCode;
		$pimAttribute->value = $valueFragment;
		$doc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('importNode'))
			->getMock();

		$attributeNode = $this->getMockBuilder('TrueACtion_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('appendChild', 'addAttributes'))
			->getMock();
		$itemNode = $this->getMockBuilder('TrueAction_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setNode'))
			->getMock();

		$doc->expects($this->once())
			->method('importNode')
			->with($this->identicalTo($valueFragment), $this->isTrue())
			->will($this->returnValue($importedValueFragment));
		$itemNode->expects($this->once())
			->method('setNode')
			->with($this->identicalTo($xmlDest))
			->will($this->returnValue($attributeNode));
		$attributeNode->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($importedValueFragment))
			->will($this->returnValue($importedValueFragment));
		$attributeNode->expects($this->once())
			->method('addAttributes')
			->with($this->identicalTo(array('xml:lang' => $languageCode)));

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')->disableOriginalConstructor()->setMethods(null)->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $doc);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute)
			)
		);
	}
	/**
	 * When the PIM attribute being added does not have a language, property will
	 * be null, the xml:lang attribute should not be added.
	 * @test
	 */
	public function testAppendAttributeValueNoTranslations()
	{
		$xmlDest = 'BaseAttrubtes/Something';
		$valueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$importedValueFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttribute->destinationXpath = $xmlDest;
		$pimAttribute->sku = '45-12345';
		$pimAttribute->language = null;
		$pimAttribute->value = $valueFragment;
		$doc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('importNode'))
			->getMock();

		$attributeNode = $this->getMockBuilder('TrueACtion_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('appendChild', 'addAttributes'))
			->getMock();
		$itemNode = $this->getMockBuilder('TrueAction_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setNode'))
			->getMock();

		$doc->expects($this->once())
			->method('importNode')
			->with($this->identicalTo($valueFragment), $this->isTrue())
			->will($this->returnValue($importedValueFragment));
		$itemNode->expects($this->once())
			->method('setNode')
			->with($this->identicalTo($xmlDest))
			->will($this->returnValue($attributeNode));
		$attributeNode->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($importedValueFragment))
			->will($this->returnValue($importedValueFragment));
		$attributeNode->expects($this->never())
			->method('addAttributes');

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')->disableOriginalConstructor()->setMethods(null)->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_doc', $doc);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute)
			)
		);
	}
	/**
	 * Use the Eb2cCore API model to validate the DOM against the schema. When
	 * valid, the method should just return self.
	 * @test
	 */
	public function testValidateDocument()
	{
		$configRegistry = $this->buildCoreConfigRegistry(array('pimExportXsd' => 'CommonProduct.xsd'));
		$helper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$doc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->getMock();
		$api = $this->getModelMock('eb2ccore/api', array('schemaValidate'));
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$pim = Mage::getModel('eb2cproduct/pim', array('doc' => $doc, 'core_feed' => $coreFeed));

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($configRegistry));
		$api->expects($this->once())
			->method('schemaValidate')
			->with($this->identicalTo($doc), $this->identicalTo('CommonProduct.xsd'))
			->will($this->returnSelf());

		$this->replaceByMock('helper', 'eb2cproduct', $helper);
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_validateDocument')
		);
	}
	/**
	 * Set up a eb2ccore/feed model to handle setting up the dirs for the outbound
	 * file - all of which is done via constructing the model with a 'dir_config'
	 * in the constructor args.
	 * @test
	 */
	public function testSetUpCoreFeed()
	{
		$configRegistry = $this->buildCoreConfigRegistry(array('pimExportFeed' => array('local_directory' => 'local/path')));
		$helper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($configRegistry));

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed', array('setUpDirs'))
			->disableOriginalConstructor()
			->getMock();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->replaceByMock('helper', 'eb2cproduct', $helper);
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$this->assertSame(
			$coreFeed,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_setUpCoreFeed')
		);
	}
	/**
	 * generate the full file path for the outbound feed file.
	 * @test
	 */
	public function testGetFeedFilePath()
	{
		$outboundPath = 'Path/To/Outbound/Dir';
		$tmpFileName = 'MageMaster_File_Name.xml';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getLocalDirectory'))
			->getMock();
		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('generateFileName'));
		$config = $this->buildCoreConfigRegistry(array('pim_export_feed_event_type' => 'PIMExport'));

		$prodHelper->expects($this->once())
			->method('generateFileName')
			->with($this->identicalTo('MageMaster'))
			->will($this->returnValue($tmpFileName));
		$coreFeed->expects($this->once())
			->method('getLocalDirectory')
			->will($this->returnValue($outboundPath));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $config);
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$pim = Mage::getModel(
			'eb2cproduct/pim',
			array('doc' => new TrueAction_Dom_Document(), 'core_feed' => $coreFeed)
		);

		$this->assertSame(
			$outboundPath . DS . $tmpFileName,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_getFeedFilePath')
		);
	}
	/**
	 * setup a collection of products for a specific store.
	 * @test
	 */
	public function testCreateProductCollectionForStore()
	{
		$productIds = array(1, 2, 3, 4, 54);
		$languageCode = 'en-us';
		$store = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getLanguageCode'))
			->getMock();
		$productCollection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter', 'addExpressionAttributeToSelect', 'setStore'))
			->getMock();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();

		$store->expects($this->once())
			->method('getLanguageCode')
			->will($this->returnValue($languageCode));
		$productCollection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('*'))
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('addExpressionAttributeToSelect')
			->with(
				$this->identicalTo('pim_language_code'),
				$this->identicalTo("('" . $languageCode . "')"),
				$this->identicalTo(array())
			)
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('entity_id'), $this->identicalTo(array('in' => $productIds)))
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('setStore')
			->with($this->identicalTo($store))
			->will($this->returnSelf());

		$this->replaceByMock('resource_model', 'catalog/product_collection', $productCollection);

		$this->assertSame(
			$productCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim, '_createProductCollectionForStore', array($productIds, $store)
			)
		);
	}
}

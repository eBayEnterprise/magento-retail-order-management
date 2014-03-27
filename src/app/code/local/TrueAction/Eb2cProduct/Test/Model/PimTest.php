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
		$docs = array();

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->getMock();
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_setUpCoreFeed', '_getFeedDoms'))
			->getMock();
		$pim->expects($this->once())
			->method('_setUpCoreFeed')
			->will($this->returnValue($coreFeed));
		$pim->expects($this->once())
			->method('_getFeedDoms')
			->will($this->returnValue($docs));
		$pim->__construct();
		$this->assertSame($docs, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_docs'));
		$this->assertSame($coreFeed, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed'));
	}
	/**
	 * Test constructing the PIM Model passing it dependencies. Should expect the
	 * core feed model to be completely intialized - never call _setUpCoreFeed
	 * @test
	 */
	public function testConstructorWithArgs()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock();
		$docs = array('item_map' => Mage::helper('eb2ccore')->getNewDomDocument());
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pim->__construct(array('docs' => $docs, 'core_feed' => $coreFeed));
		$this->assertSame($docs, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_docs'));
		$this->assertSame($coreFeed, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pim, '_coreFeed'));
	}
	/**
	 * If the constructor is called with a params argument containing 'docs' key
	 * value pair, the value must be an array of key mapped to TrueAction_Dom_Document, else an error
	 * (converted to an Exception in the test) will be triggered.
	 * @test
	 */
	public function testConstructorBadDomError()
	{
		$expectedException = sprintf(
			TrueAction_Eb2cProduct_Model_Pim::ERROR_INVALID_DOC,
			'TrueAction_Eb2cProduct_Model_Pim::__construct'
		);
		$initParams = array(TrueAction_Eb2cProduct_Model_Pim::KEY_DOCS => array());
		$this->setExpectedException('Exception', $expectedException);

		$helper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('triggerError'))
			->getMock();
		$helper->expects($this->once())
			->method('triggerError')
			->with($this->identicalTo($expectedException))
			->will($this->throwException(new Exception($expectedException)));
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '__construct', array($initParams));
	}
	/**
	 * If the constructor is called with a params argument containing s 'core_feed' key
	 * value pair, the value must be a TrueAction_Eb2cCore_Model_Feed, else an error
	 * (converted to an Exception in the test) will be triggered.
	 * @test
	 */
	public function testConstructorBadCoreFeed()
	{
		$docs = array();
		$expectedException = sprintf(
			TrueAction_Eb2cProduct_Model_Pim::ERROR_INVALID_CORE_FEED,
			'TrueAction_Eb2cProduct_Model_Pim::__construct'
		);
		$initParams = array(TrueAction_Eb2cProduct_Model_Pim::KEY_CORE_FEED => array());
		$this->setExpectedException('Exception', $expectedException);

		$helper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('triggerError'))
			->getMock();
		$helper->expects($this->once())
			->method('triggerError')
			->with($this->identicalTo($expectedException))
			->will($this->throwException(new Exception($expectedException)));
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedDoms'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedDoms')
			->will($this->returnValue($docs));

		EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '__construct', array($initParams));
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
		$key = 'item_map';
		$map = array($key => array());
		$pathToFile = array($key => 'path/to/export/file.xml');
		$productIds = array(1, 2, 3, 4, 54);

		$feedData = $this->getModelMockBuilder('eb2cproduct/pim_product_collection')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$feedDoc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('save'))
			->getMock();
		$feedDoc->expects($this->once())
			->method('save')
			->with($this->identicalTo($pathToFile[$key]))
			->will($this->returnValue(12));

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedsMap', '_createFeedDataSet', '_createDomFromFeedData', '_getFeedFilePath'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));
		$pim->expects($this->once())
			->method('_createFeedDataSet')
			->with($this->identicalTo($productIds), $this->identicalTo($key))
			->will($this->returnValue($feedData));
		$pim->expects($this->once())
			->method('_createDomFromFeedData')
			->with($this->identicalTo($feedData), $this->identicalTo($key))
			->will($this->returnValue($feedDoc));
		$pim->expects($this->once())
			->method('_getFeedFilePath')
			->with($this->identicalTo($key))
			->will($this->returnValue($pathToFile[$key]));

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
		$key = 'item_map';

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
				$this->identicalTo($pimProductCollection),
				$this->identicalTo($key)
			)
			->will($this->returnValue($pimProductCollection));
		$this->assertSame(
			$pimProductCollection,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_createFeedDataSet', array($productIds, $key))
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
		$key = 'item_map';
		$docs = array($key => Mage::helper('eb2ccore')->getNewDomDocument());
		$attributeList = array('_gsi_client_id', 'sku');

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedAttributes'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedAttributes')
			->with($this->identicalTo($key))
			->will($this->returnValue($attributeList));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);

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
			->with(
				$this->identicalTo($product),
				$this->identicalTo($docs[$key]),
				$this->identicalTo($key),
				$this->identicalTo($attributeList)
			)
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
				array($productCollection, $pimProductCollection, $key)
			)
		);
	}
	/**
	 * Initialize the DOM Document with a root node and message header.
	 * @test
	 */
	public function testStartDocument()
	{
		$key = 'item_map';
		$rootNode = TrueAction_Eb2cProduct_Model_Pim::KEY_ROOT_NODE;
		$schemeLocation = TrueAction_Eb2cProduct_Model_Pim::KEY_SCHEMA_LOCATION;
		$eventType = TrueAction_Eb2cProduct_Model_Pim::KEY_EVENT_TYPE;
		$headerTemplate = '<MessageHeader></MessageHeader>';
		$mediaUrl = '';

		$map = array($key => array(
			$rootNode => 'ItemMaster',
			$schemeLocation => 'ItemMasterV11.xml',
			$eventType => 'ItemMaster',
		));

		$feedXml = sprintf(
			TrueAction_Eb2cProduct_Model_Pim::XML_TEMPlATE,
			$map[$key][$rootNode],
			TrueAction_Eb2cProduct_Model_Pim::XMLNS,
			$map[$key][$schemeLocation],
			$headerTemplate,
			$mediaUrl
		);

		$docMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('loadXml'))
			->getMock();
		$docMock->expects($this->once())
			->method('loadXml')
			->with($this->identicalTo($feedXml))
			->will($this->returnSelf());

		$docs = array($key => $docMock);

		$helper = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('generateMessageHeader'))
			->getMock();
		$helper->expects($this->once())
			->method('generateMessageHeader')
			->with($this->identicalTo($map[$key][$eventType]))
			->will($this->returnValue($headerTemplate));
		$this->replaceByMock('helper', 'eb2cproduct', $helper);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getBaseUrl'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getBaseUrl')
			->with($this->identicalTo(Mage_Core_Model_Store::URL_TYPE_MEDIA))
			->will($this->returnValue($mediaUrl));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedsMap'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);

		$this->assertSame($pim, EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_startDocument', array()));
	}
	/**
	 * Create the DOMDocument representing the feed. Should initialize (add root
	 * node and message header) the DOMDocument, build out and append fragments
	 * for each item to be included, and validate the resulting DOM.
	 * @test
	 */
	public function testCreateDomFromFeedData()
	{
		$key = 'item_map';
		$itemNode = 'Item';
		$isValidate = 'true';
		$map = array($key => array(
			TrueAction_Eb2cProduct_Model_Pim::KEY_ITEM_NODE => $itemNode,
			TrueAction_Eb2cProduct_Model_Pim::KEY_IS_VALIDATE => $isValidate,
		));
		$feedDoc = Mage::helper('eb2ccore')->getNewDomDocument();
		$feedDoc->loadXML('<root></root>');
		$itemFragment = $feedDoc->createDocumentFragment();
		$itemFragment->appendChild($feedDoc->createElement('Item'));

		$docs = array($key => $feedDoc);

		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->getMock();

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$helperMock->expects($this->once())
			->method('parseBool')
			->with($this->identicalTo($isValidate))
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$pimProductCollection = $this->getModelMockBuilder('eb2cproduct/pim_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItems'))
			->getMock();
		$pimProductCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue(array($pimProduct)));

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_buildItemNode', '_startDocument', '_validateDocument', '_getFeedsMap'))
			->getMock();
		$pim->expects($this->once())
			->method('_startDocument')
			->will($this->returnSelf());
		$pim->expects($this->once())
			->method('_buildItemNode')
			->with($this->identicalTo($pimProduct), $this->identicalTo($itemNode), $this->identicalTo($key))
			->will($this->returnValue($itemFragment));
		$pim->expects($this->once())
			->method('_validateDocument')
			->with($this->identicalTo($key))
			->will($this->returnSelf());
		$pim->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);

		$this->assertSame($feedDoc, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pim, '_createDomFromFeedData', array($pimProductCollection, $key)
		));
		$this->assertSame('<root><Item></Item></root>', $feedDoc->C14N());
	}
	/**
	 * Create DOMDocumentFragment with the <Item> node.
	 * For each pim attribute model given, append DOM to represent the value.
	 * @test
	 */
	public function testBuildItemNode()
	{
		$key = 'item_map';
		$childNode = 'Item';

		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$pimAttributes = array($pimAttribute,);

		$itemNode = $this->getMockBuilder('TrueAction_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$itemFragment = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->setMethods(array('appendChild'))
			->getMock();
		$itemFragment->expects($this->once())
			->method('appendChild')
			->with($this->identicalTo($itemNode))
			->will($this->returnValue($itemNode));

		$feedDoc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createDocumentFragment', 'createElement'))
			->getMock();

		$feedDoc->expects($this->once())
			->method('createDocumentFragment')
			->will($this->returnValue($itemFragment));
		$feedDoc->expects($this->once())
			->method('createElement')
			->with($this->identicalTo($childNode))
			->will($this->returnValue($itemNode));

		$docs = array($key => $feedDoc);

		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('getPimAttributes'))
			->getMock();
		$pimProduct->expects($this->once())
			->method('getPimAttributes')
			->will($this->returnValue($pimAttributes));

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_appendAttributeValue'))
			->getMock();
		$pim->expects($this->once())
			->method('_appendAttributeValue')
			->with($this->identicalTo($itemNode), $this->identicalTo($pimAttribute), $this->identicalTo($key))
			->will($this->returnSelf());

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);

		$this->assertSame($itemFragment, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pim, '_buildItemNode', array($pimProduct, $childNode, $key)
		));
	}
	/**
	 * Append the nodes necessary DOMNodes to represent the pim attribute to the
	 * given TrueAction_Dom_Element.
	 * @test
	 */
	public function testAppendAttributeValue()
	{
		$key = 'item_map';
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
		$doc->expects($this->once())
			->method('importNode')
			->with($this->identicalTo($valueFragment), $this->isTrue())
			->will($this->returnValue($importedValueFragment));

		$docs = array($key => $doc);

		$attributeNode = $this->getMockBuilder('TrueACtion_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('appendChild', 'addAttributes'))
			->getMock();
		$itemNode = $this->getMockBuilder('TrueAction_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setNode'))
			->getMock();

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
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute, $key)
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
		$key = 'item_map';
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

		$docs = array($key => $doc);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')->disableOriginalConstructor()->setMethods(null)->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);
		$this->assertSame(
			$pim,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$pim,
				'_appendAttributeValue',
				array($itemNode, $pimAttribute, $key)
			)
		);
	}
	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim::_appendAttributeValue method for the following expectations
	 * Expectation 1: this test will invoked the method TrueAction_Eb2cProduct_Model_Pim::_appendAttributeValue
	 *                given a mocked TrueAction_Dom_Element object, a mocked TrueAction_Eb2cProduct_Model_Pim_Attribute
	 *                object and a key
	 */
	public function testAppendAttributeValueWhenPimAttributeValueIsADomAttr()
	{
		$key = 'item_map';
		$data = array('name' => 'catalog_id', 'value' => '83');
		$domAttribute = new DOMAttr($data['name'], $data['value']);

		$elementMock = $this->getMockBuilder('TrueAction_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setAttribute'))
			->getMock();
		$elementMock->expects($this->once())
			->method('setAttribute')
			->with($this->identicalTo($data['name']), $this->identicalTo($data['value']))
			->will($this->returnValue($domAttribute));

		$attributeMock = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$attributeMock->value = $domAttribute;

		$pimModelMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($pimModelMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_appendAttributeValue', array($elementMock, $attributeMock, $key)
		));
	}
	/**
	 * Use the Eb2cCore API model to validate the DOM against the schema. When
	 * valid, the method should just return self.
	 * @test
	 */
	public function testValidateDocument()
	{
		$key = 'item_map';
		$configRegistry = $this->buildCoreConfigRegistry(array('pimExportXsd' => 'CommonProduct.xsd'));
		$helper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$doc = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->getMock();
		$api = $this->getModelMock('eb2ccore/api', array('schemaValidate'));

		$docs = array($key => $doc);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_docs', $docs);

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
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_validateDocument', array($key))
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
		$key = 'item_map';
		$eventType = TrueAction_Eb2cProduct_Model_Pim::KEY_EVENT_TYPE;
		$filePattern = TrueAction_Eb2cProduct_Model_Pim::KEY_FILE_PATTERN;
		$map = array($key => array(
			$eventType => 'ItemMaster',
			$filePattern => '{event_type}_{store_id}.xml'
		));

		$outboundPath = 'Path/To/Outbound/Dir';
		$tmpFileName = 'MageMaster_File_Name.xml';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getLocalDirectory'))
			->getMock();
		$coreFeed->expects($this->once())
			->method('getLocalDirectory')
			->will($this->returnValue($outboundPath));

		$prodHelper = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('generateFileName'))
			->getMock();
		$prodHelper->expects($this->once())
			->method('generateFileName')
			->with($this->identicalTo($map[$key][$eventType]), $this->identicalTo($map[$key][$filePattern]))
			->will($this->returnValue($tmpFileName));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedsMap'))
			->getMock();
		$pim->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pim, '_coreFeed', $coreFeed);

		$this->assertSame(
			$outboundPath . DS . $tmpFileName,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($pim, '_getFeedFilePath', array($key))
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

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim::_getFeedDoms method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Pim::_getFeedDoms will be invoked by this test
	 *                and is expected called the method TrueAction_Eb2cProduct_Model_Pim::_getFeedsMap which will
	 *                return an array of feed configurations, looping through each feed configurations key and building
	 *                an array of DOMDocument by feed configuration keys
	 */
	public function testGetFeedDoms()
	{
		$keyA = 'item_map';
		$keyB = 'content_map';
		$map = array($keyA => array(), $keyB => array());

		$docs = array(
			$keyA => Mage::helper('eb2ccore')->getNewDomDocument(),
			$keyB => Mage::helper('eb2ccore')->getNewDomDocument(),
		);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->at(0))
			->method('getNewDomDocument')
			->will($this->returnValue($docs[$keyA]));
		$coreHelperMock->expects($this->at(1))
			->method('getNewDomDocument')
			->will($this->returnValue($docs[$keyB]));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$pimModelMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedsMap'))
			->getMock();
		$pimModelMock->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));

		$this->assertSame($docs, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_getFeedDoms', array()
		));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim::_getFeedsMap method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Pim::_getFeedsMap will be invoked by this test
	 *                and is expected to cache and return the pim exported configuration in a the class property
	 *                TrueAction_Eb2cProduct_Model_Pim::_feedsMap, the test will proved this by first setting
	 *                the class property TrueAction_Eb2cProduct_Model_Pim::_feedsMap to a know value of an empty
	 *                array and then invoked the method TrueAction_Eb2cProduct_Model_Pim::_getFeedsMap and then
	 *                assert that it is equal to the return value of the mocked TrueAction_Eb2cCore_Model_Feed::getConfigData
	 *                method when invoked once
	 */
	public function testGetFeedsMap()
	{
		$map = array('item_map' => array(), 'content_map' => array());

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cProduct_Model_Pim::PIM_CONFIG_PATH))
			->will($this->returnValue($map));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$pimModelMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($pimModelMock, '_feedsMap', array());

		$this->assertSame($map, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_getFeedsMap', array()
		));

		$this->assertSame($map, EcomDev_Utils_Reflection::getRestrictedPropertyValue($pimModelMock, '_feedsMap'));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim::_getFeedAttributes method for the following expectation
	 * Expectation 1: this test will invoked the method TrueAction_Eb2cProduct_Model_Pim::_getFeedAttributes given
	 *                a known array key to return the attribute map configured for the feed
	 */
	public function testGetFeedAttributes()
	{
		$key = 'item_map';
		$attributes = array('_gsi_client_id', 'sku');
		$map = array($key => array(TrueAction_Eb2cProduct_Model_Pim::KEY_MAPPINGS => array(
			$attributes[0] => array(),
			$attributes[1] => array()
		)));

		$pimModelMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getFeedsMap'))
			->getMock();
		$pimModelMock->expects($this->once())
			->method('_getFeedsMap')
			->will($this->returnValue($map));

		$this->assertSame($attributes, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimModelMock, '_getFeedAttributes', array($key)
		));
	}
}

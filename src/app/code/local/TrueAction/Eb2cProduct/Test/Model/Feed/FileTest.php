<?php

class TrueAction_Eb2cProduct_Test_Model_Feed_FileTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Data provider for testing the constructor. Provides the array
	 * of file details and, if expected for the given set of details, the
	 * message for the error triggered.
	 * @return array
	 */
	public function provideConstructorDetailsAndErrors()
	{
		return array(
			array(array('doc' => new TrueAction_Dom_Document(), 'error_file' => 'error_file.xml'), null),
			array(array('doc' => "this isn't a DOMDocument", 'error_file' => 'error_file.xml'), 'User Error: TrueAction_Eb2cProduct_Model_Feed_File::__construct called with invalid doc. Must be instance of TrueAction_Dom_Document'),
			array(array('wat' => "There's aren't the arguments you're looking for"), 'User Error: TrueAction_Eb2cProduct_Model_Feed_File::__construct called without required feed details: doc, error_file missing.'),
		);
	}
	/**
	 * Test constructing instances - should be invoked with an array of
	 * "feed details". The array of data the model is instantiated with
	 * must contain keys for 'error_file' and 'doc'. When instantiated with a
	 * proper set of file details, the array should be stored on the _feedDetails
	 * property. When given an invalid set of file details, an error should be triggered.
	 * @param array  $fileDetails  Argument to the constructor
	 * @param string $errorMessage Expected error message, if empty, no error is expected
	 * @test
	 * @dataProvider provideConstructorDetailsAndErrors
	 */
	public function testConstruction($fileDetails, $errorMessage)
	{
		if ($errorMessage) {
			// Errors should be getting converted to PHPUnit_Framework_Error but
			// they aren't...instead just getting plain ol' Exceptions...so at least the
			// messages are rather explicit so hopefully no miscaught exceptions by this test.
			$this->setExpectedException('Exception', $errorMessage);
		}

		$feedFile = Mage::getModel('eb2cproduct/feed_file', $fileDetails);

		if (!$errorMessage) {
			$this->assertSame(
				$fileDetails,
				EcomDev_Utils_Reflection::getRestrictedPropertyValue($feedFile, '_feedDetails')
			);
		}
	}
	/**
	 * Test processing of the file, which consists for deleting any products
	 * marked for deletion in the feed, processing adds/updates for the default
	 * store view and then processing any translations in the feed.
	 * @test
	 */
	public function testProcess()
	{
		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('processByOperationType'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('processByOperationType')
			->with($this->isInstanceOf('Varien_Event_Observer'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $errorConfirmationMock);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('deleteProducts', 'processDefaultStore', 'processTranslations'))
			->getMock();
		$file->expects($this->once())
			->method('deleteProducts')
			->will($this->returnSelf());
		$file->expects($this->once())
			->method('processDefaultStore')
			->will($this->returnSelf());
		$file->expects($this->once())
			->method('processTranslations')
			->will($this->returnSelf());

		$this->assertSame($file, $file->process());

		$this->assertEventDispatched('product_feed_process_operation_type_error_confirmation');
	}

	/**
	 * Test processing translations. Should get a list of all languages configured
	 * in the admin and then processing the feed for that language.
	 * @test
	 */
	public function testProcessTranslations()
	{
		$languageCodes = array('en-us', 'fr-fr');
		$languageHelperMock = $this->getHelperMockBuilder('eb2ccore/languages')
			->disableOriginalConstructor()
			->setMethods(array('getLanguageCodesList'))
			->getMock();
		$languageHelperMock->expects($this->once())
			->method('getLanguageCodesList')
			->will($this->returnValue($languageCodes));
		$this->replaceByMock('helper', 'eb2ccore/languages', $languageHelperMock);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('processForLanguage'))
			->getMock();
		$fileModelMock->expects($this->at(0))
			->method('processForLanguage')
			->with($this->equalTo('en-us'))
			->will($this->returnSelf());
		$fileModelMock->expects($this->at(1))
			->method('processForLanguage')
			->with($this->equalTo('fr-fr'))
			->will($this->returnSelf());

		$this->assertSame($fileModelMock, $fileModelMock->processTranslations());

	}

	/**
	 * To process a single languages, the original feed should be split into a
	 * separate DOM including only data for that language and then extracting all
	 * product data from the split DOM. Then, for each store configured for the
	 * given language, the data should be imported within that store view.
	 * @test
	 */
	public function testProcessForLanguage()
	{
		$languageCode = 'en-us';
		$splitDoc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$storeModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$storeModelMock->expects($this->at(0))
			->method('getId')
			->will($this->returnValue(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID));
		$storeModelMock->expects($this->at(1))
			->method('getId')
			->will($this->returnValue(3));
		$storeModelMock->expects($this->at(2))
			->method('getId')
			->will($this->returnValue(4));

		$languagesHelperMock = $this->getHelperMockBuilder('eb2ccore/languages')
			->disableOriginalConstructor()
			->setMethods(array('getStores'))
			->getMock();
		$languagesHelperMock->expects($this->once())
			->method('getStores')
			->with($this->equalTo('en-us'))
			->will($this->returnValue(array($storeModelMock, $storeModelMock, $storeModelMock)));
		$this->replaceByMock('helper', 'eb2ccore/languages', $languagesHelperMock);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_splitByLanguageCode', '_importExtractedData'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('_splitByLanguageCode')
			->with($this->equalTo('en-us'), $this->equalTo(TrueAction_Eb2cProduct_Model_Feed_File::XSLT_SINGLE_TEMPLATE_PATH))
			->will($this->returnValue($splitDoc));
		$fileModelMock->expects($this->exactly(2))
			->method('_importExtractedData')
			->with($this->equalTo($splitDoc), $this->logicalOr($this->equalTo(3), $this->equalTo(4)))
			->will($this->returnSelf());

		$this->assertSame($fileModelMock, $fileModelMock->processForLanguage($languageCode));
	}

	/**
	 * To process the default store, the original feed file should be split into
	 * a spearate file containing any data that does not include a translation or
	 * is for the language of the default store. Product data should then be
	 * extracted from the split file and imported within the default store view.
	 * @test
	 */
	public function testProcessDefaultStore()
	{
		$splitDoc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'languageCode' => 'en-us'
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_splitByLanguageCode', '_importExtractedData'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('_splitByLanguageCode')
			->with($this->equalTo('en-us'), $this->equalTo(TrueAction_Eb2cProduct_Model_Feed_File::XSLT_DEFAULT_TEMPLATE_PATH))
			->will($this->returnValue($splitDoc));
		$fileModelMock->expects($this->once())
			->method('_importExtractedData')
			->with($this->equalTo($splitDoc), $this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
			->will($this->returnSelf());

		$this->assertSame($fileModelMock, $fileModelMock->processDefaultStore());
	}

	/**
	 * When splitting the feed DOMDocument by language code, use eb2cproduct/data
	 * helper's splitDomByXslt method, passing through the original DOMDocument,
	 * the path to the appropriate XSLT and the parameters to be passed through
	 * to the XSLT.
	 * @test
	 */
	public function testSplitByLanguageCode()
	{

		$languageCode = 'en-us';
		$template = TrueAction_Eb2cProduct_Model_Feed_File::XSLT_DEFAULT_TEMPLATE_PATH;
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$splitDoc = new TrueAction_Dom_Document('1.0', 'UTF-8');

		$xsltFilePath = 'mock/path/to/language-splitting-xslt.xsl';

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('splitDomByXslt'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('splitDomByXslt')
			->with($this->equalTo($doc), $this->equalTo($xsltFilePath), $this->equalTo(array('lang_code' => $languageCode)))
			->will($this->returnValue($splitDoc));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('getDoc', '_getXsltPath'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('getDoc')
			->will($this->returnValue($doc));
		$fileModelMock->expects($this->once())
			->method('_getXsltPath')
			->with($this->equalTo($template))
			->will($this->returnValue($xsltFilePath));

		$this->assertSame(
			$splitDoc,
			$this->_reflectMethod($fileModelMock, '_splitByLanguageCode')->invoke($fileModelMock, $languageCode, $template)
		);
	}

	/**
	 * Deleting a product should create a product collection of products marked
	 * for deletion in the feed and then call the delete method on the collection.
	 * @test
	 */
	public function testDeleteProducts()
	{
		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('processByOperationType'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('processByOperationType')
			->with($this->isInstanceOf('Varien_Event_Observer'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $errorConfirmationMock);

		$skus = array('45-4321', '45-9432');

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addAttributeToSelect', 'load', 'delete'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('sku'), $this->equalTo($skus))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo(array('entity_id')))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('delete')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $catalogResourceModelProductMock);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_getSkusToDelete'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('_getSkusToDelete')
			->will($this->returnValue($skus));

		$this->assertSame(
			$fileModelMock,
			$this->_reflectMethod($fileModelMock, 'deleteProducts')->invoke($fileModelMock)
		);

		$this->assertEventDispatched('product_feed_process_operation_type_error_confirmation');
	}

	/**
	 * Test _getSkusToDelete method with the following assumptions when invoked by this test
	 * Expectation 1: this set first set the class property TrueAction_Eb2cProduct_Model_Feed_File::_feedDetails
	 *                to a known state of key value array
	 * Expectation 2: when the method TrueAction_Eb2cProduct_Model_Feed_File::_getSkusToDelete get invoked by this
	 *                test the method TrueAction_Eb2cProduct_Model_Feed_File::getDoc will be called once and it
	 *                will return the DOMDocument object, then the method TrueAction_Eb2cProduct_Helper_Data::splitDomByXslt
	 *                will be called with the DOMDocument object and the xslt full path to the delete template file, this method
	 *                will return new DOMDocument object with skus to be deleted
	 * Expectation 3: the new DOMDocument object will be passed as parameter to the TrueAction_Eb2cCore_Helper_Data::getNewDomXPath
	 *                method which will return the DOMXPath object, with this xpath object the method query the each sku node and
	 *                extract each sku to be deleted into an array of skus
	 * Expectation 4: this array of skus then get return
	 * @mock TrueAction_Eb2cProduct_Model_Feed_File::getDoc
	 * @mock TrueAction_Eb2cProduct_Helper_Data::splitDomByXslt
	 * @mock TrueAction_Eb2cCore_Helper_Data::getNewDomXPath
	 */
	public function testGetSkusToDelete()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-1234</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-4321</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-9432</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);

		$dlDoc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$dlDoc->loadXML(
			'<product_to_be_deleted>
				<sku>45-4321</sku>
				<sku>45-9432</sku>
			</product_to_be_deleted>'
		);

		$xpath = new DOMXPath($dlDoc);

		$xslt = 'path/to/delete/xslt.xsl';

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('splitDomByXslt'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('splitDomByXslt')
			->with($this->equalTo($doc), $this->equalTo($xslt))
			->will($this->returnValue($dlDoc));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomXPath'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomXPath')
			->with($this->equalTo($dlDoc))
			->will($this->returnValue($xpath));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('getDoc', '_getXsltPath'))
			->getMock();
		$file->expects($this->once())
			->method('getDoc')
			->will($this->returnValue($doc));
		$file->expects($this->once())
			->method('_getXsltPath')
			->with($this->identicalTo(TrueAction_Eb2cProduct_Model_Feed_File::XSLT_DELETED_SKU))
			->will($this->returnValue($xslt));

		$this->_reflectProperty($file, '_feedDetails')->setValue($file, array(
			'doc' => $doc,
			'local' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_Subset.xml',
			'remote' => '/ItemMaster/',
			'timestamp' => '2012-07-06 10:09:05',
			'type' => 'ItemMaster',
			'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
		));

		$this->assertSame(
			array('45-4321', '45-9432'),
			$this->_reflectMethod($file, '_getSkusToDelete')->invoke($file)
		);
	}
	/**
	 * Test getting an array of all SKUs contained in a split feed file. Can
	 * assume that any SKUs to delete have already been stripped out by the XSLT.
	 * @test
	 */
	public function testGetSkusToUpdate()
	{
		$doc = '<Items>
	<Item><ItemId><ClientItemId>45-12345</ClientItemId></ItemId></Item>
	<Item><ClientItemId>45-23456</ClientItemId></Item>
	<Item><UniqueID>45-34567</UniqueID></Item>
</Items>';
		$dom = new DOMDocument();
		$dom->loadXML($doc);
		$xpath = new DOMXPath($dom);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame(
			array('45-12345', '45-23456', '45-34567'),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$file,
				'_getSkusToUpdate',
				array($xpath)
			)
		);
	}
	/**
	 * To extract data from the feed, the feed file needs to be loaded into a new
	 * DOMXPath, creating a product collection based on SKUs present in the feed,
	 * split the feed file into individual item nodes and create/update the
	 * product for each item. Finally, the collection should be set within the
	 * store context the product data should be saved in and save the collection.
	 * @test
	 */
	public function testImportExtractedData()
	{
		// DOMDocument containing data to be imported
		$doc = $this->getMockBuilder('TrueAction_Dom_Document')->disableOriginalConstructor()->getMock();
		// store context to save the products in
		$storeId = 1;
		// skus extracted from the DOM to be added/updated - in this case one to add
		$skus = array('skus-to-update');
		// DOMNode containing data for the product
		$contextNode = $this->getMockBuilder('DOMNode')->disableOriginalConstructor()->getMock();
		// DOMNodeList of all items in the feed. Using an array instead of actual
		// DOMNodeList as it isn't possible, so far as I can tell, to mock a
		// DOMNodeList to behave as desired. As all this method really needs is a
		// Traversable object containing DOMNodes, the array substitution works.
		$itemNodeList = array($contextNode);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getSkusToUpdate', '_buildProductCollection', '_updateItem'
			))
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomXPath'));
		$xpath = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(array('query'))
			->getMock();
		$productCollection = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'save', 'count'))
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);

		$coreHelper->expects($this->once())
			->method('getNewDomXPath')
			->with($this->identicalTo($doc))
			->will($this->returnValue($xpath));

		$file->expects($this->once())
			->method('_getSkusToUpdate')
			->with($this->identicalTo($xpath))
			->will($this->returnValue($skus));
		$file->expects($this->once())
			->method('_buildProductCollection')
			->with($this->identicalTo($skus))
			->will($this->returnValue($productCollection));
		$file->expects($this->once())
			->method('_updateItem')
			->with(
				$this->identicalTo($xpath),
				$this->identicalTo($contextNode),
				$this->identicalTo($productCollection)
			)
			->will($this->returnSelf());

		$xpath->expects($this->once())
			->method('query')
			->with($this->identicalTo('/Items/Item'))
			->will($this->returnValue($itemNodeList));

		$productCollection->expects($this->once())
			->method('setStore')
			->with($this->identicalTo($storeId))
			->will($this->returnSelf());
		$productCollection->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$this->assertSame(
			$file,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$file,
				'_importExtractedData',
				array($doc, $storeId)
			)
		);
	}

	/**
	 * Test creating/updating a product with data extracted from the feed.
	 * Should check for the product to exist in the product collection. If the
	 * product exists, the product in the collection should be retrieved and
	 * updated with data extracted from the feed. If it does not exist, a new
	 * product should be created, updated with data from the feed, and added
	 * to the product colleciotn.
	 * @param boolean $isNew
	 * @param string $sku
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testUpdateItem($isNew, $sku)
	{
		$contextNode = $this->getMockBuilder('DOMNode')->disableOriginalConstructor()->getMock();
		$productFeedData = array('sku' => $sku, 'title' => 'Shiny New Title');
		$xpath = $this->getMockBuilder('DOMXPath')->disableOriginalConstructor()->getMock();

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(array('extractItem', 'extractSku'))
			->getMock();
		$productHelper = $this->getHelperMock('eb2cproduct/data', array('createNewProduct'));
		$productCollection = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemById', 'addItem'))
			->getMock();
		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('addData'))
			->getMock();
		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->replaceByMock('singleton', 'eb2cproduct/feed_extractor', $extractor);
		$this->replaceByMock('helper', 'eb2cproduct', $productHelper);

		$extractor->expects($this->once())
			->method('extractSku')
			->with($this->identicalTo($xpath), $this->identicalTo($contextNode))
			->will($this->returnValue($sku));
		$extractor->expects($this->once())
			->method('extractItem')
			->with($this->identicalTo($xpath), $this->identicalTo($contextNode), $this->identicalTo($product))
			->will($this->returnValue($productFeedData));

		$product->expects($this->once())
			->method('addData')
			->with($this->identicalTo($productFeedData))
			->will($this->returnSelf());

		if ($isNew) {
			$productHelper->expects($this->once())
				->method('createNewProduct')
				->with($this->identicalTo($sku))
				->will($this->returnValue($product));
			$productCollection->expects($this->once())
				->method('addItem')
				->with($this->identicalTo($product))
				->will($this->returnSelf());
			$productCollection->expects($this->once())
				->method('getItemById')
				->with($this->identicalTo($sku))
				->will($this->returnValue(null));
		} else {
			$productHelper->expects($this->never())
				->method('createNewProduct');
			$productCollection->expects($this->never())
				->method('addItem');
			$productCollection->expects($this->once())
				->method('getItemById')
				->with($this->identicalTo($sku))
				->will($this->returnValue($product));
		}

		$this->assertSame(
			$file,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$file, '_updateItem', array($xpath, $contextNode, $productCollection)
			)
		);
	}
	/**
	 * Build a product collection from a list of SKUs. The collection should only
	 * be expected to inlcude products that already exist in Magneto. The
	 * collection should also load as little product data as possible while still
	 * allowing all of the necessary updates and saves to be performed.
	 * @test
	 */
	public function testBuildProductCollection()
	{
		$skus = array('12345', '4321');

		$productCollectionMock = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addAttributeToFilter', 'load'))
			->getMock();

		$productCollectionMock->expects($this->any())
			->method('addAttributeToSelect')
			->with($this->equalTo(array('entity_id')))
			->will($this->returnSelf());
		$productCollectionMock->expects($this->any())
			->method('addAttributeToFilter')
			->with($this->equalTo(array(
				array(
					'attribute' => 'sku',
					'in' => $skus,
				),
			)))
			->will($this->returnSelf());
		$productCollectionMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		$this->replaceByMock('resource_model', 'eb2cproduct/feed_product_collection', $productCollectionMock);
		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame($productCollectionMock, $this->_reflectMethod($file, '_buildProductCollection')->invoke($file, $skus));
	}

}

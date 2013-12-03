<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_CleanerTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getProductsToClean method, making sure our assumption are correct and
	 * the right parameters are being pass to the collect to filter where is_clean is false
	 * @test
	 */
	public function testGetProductsToClean()
	{
		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter'))
			->getMock();

		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo('*'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('is_clean'), $this->equalTo(false))
			->will($this->returnSelf());

		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getCollection'))
			->getMock();
		$catalogModelProductMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($catalogResourceModelProductMock));
		$this->replaceByMock('model', 'catalog/product', $catalogModelProductMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Resource_Product_Collection',
			$cleanerObject->getProductsToClean()
		);
	}

	/**
	 * Test cleaning all products. Assertions are intentionally simplistic as the
	 * more rigorous coverage is included in the other test methods.
	 * I just want to ensure that when put together, all the pieces work to some
	 * verifiable degree.
	 * @test
	 * @large
	 */
	public function testCleanAllProducts()
	{
		$newCollection = new Varien_Data_Collection();
		foreach (range(1, 5) as $id) {
			$newCollection->addItem(Mage::getModel('catalog/product')->addData(array('entity_id' => $id)));
		}

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('getProductsToClean', 'cleanProduct'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('getProductsToClean')
			->will($this->returnValue($newCollection));
		$feedCleanerModelProductMock->expects($this->exactly(5))
			->method('cleanProduct')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanAllProducts()
		);
	}

	/**
	 * Test cleanProduct method, when the product is configurable
	 * @test
	 */
	public function testCleanProductWithConfigurableProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getTypeId', 'save'))
			->getMock();
		$product->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue('configurable'));
		$product->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_resolveProductLinks', '_addUsedProducts', 'markProductClean'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('_resolveProductLinks')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('_addUsedProducts')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('markProductClean')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanProduct($product)
		);
	}

	/**
	 * Test cleanProduct method, when the product is a child of a configurable
	 * @test
	 */
	public function testCleanProductWithChildConfigurableProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getTypeId', 'save', 'getSku', 'getStyleId'))
			->getMock();
		$product->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue('simple'));
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234-Simple'));
		$product->expects($this->exactly(2))
			->method('getStyleId')
			->will($this->returnValue('1234-Configurable'));
		$product->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_resolveProductLinks', '_addToCofigurableProduct', 'markProductClean'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('_resolveProductLinks')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('_addToCofigurableProduct')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('markProductClean')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanProduct($product)
		);
	}

	/**
	 * Test _resolveProductLinks method, when the product has unresolved link data
	 * @test
	 */
	public function testResolveProductLinks()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setUnresolvedProductLinks'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(
				'a:1:{i:0;a:3:{s:9:"link_type";s:7:"related";s:14:"operation_type";s:3:"Add";s:17:"link_to_unique_id";s:7:"clean_0";}}'
			));
		$product->expects($this->once())
			->method('setUnresolvedProductLinks')
			->will($this->returnSelf());

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_linkProducts'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->at(0))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array(array(
					'link_type' => 'related',
					'operation_type' => 'Add',
					'link_to_unique_id' => 'clean_0',
				))),
				$this->equalTo('related')
			)
			->will($this->returnValue(array(array(
				'link_type' => 'related',
				'operation_type' => 'Add',
				'link_to_unique_id' => 'clean_0',
			))));
		$feedCleanerModelProductMock->expects($this->at(1))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array()),
				$this->equalTo('upsell')
			)
			->will($this->returnValue(array()));
		$feedCleanerModelProductMock->expects($this->at(2))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array()),
				$this->equalTo('crosssell')
			)
			->will($this->returnValue(array()));
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($feedCleanerModelProductMock, '_resolveProductLinks')->invoke($feedCleanerModelProductMock, $product)
		);
	}

	/**
	 * Test _linkProducts method, when the product has unresolved link data,
	 * @test
	 */
	public function testLinkProducts()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getRelatedProducts'))
			->getMock();
		$product->expects($this->once())
			->method('getRelatedProducts')
			->will($this->returnValue(array(
				Mage::getModel('catalog/product')->addData(array(
					'sku' => '1234-Related'
				)),
			)));

		$linkUpdates = array(array(
			'link_type' => 'related',
			'operation_type' => 'Add',
			'link_to_unique_id' => 'clean_0',
		));

		$linkType = 'related';

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_buildProductLinkForSku'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('_buildProductLinkForSku')
			->will($this->returnValue(array(array(
				'link_type' => 'related',
				'operation_type' => 'Add',
				'link_to_unique_id' => 'clean_0',
			))));
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->will($this->returnValue(Mage::getModel('catalog/product')->addData(array(
				'sku' => '1234-Related',
				'entity_id' => 0,
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$this->assertSame(
			array(array(array(
				'link_type' => 'related',
				'operation_type' => 'Add',
				'link_to_unique_id' => 'clean_0',
			))),
			$this->_reflectMethod($feedCleanerModelProductMock, '_linkProducts')->invoke($feedCleanerModelProductMock, $product, $linkUpdates, $linkType)
		);
	}

	/**
	 * Test _linkProducts method, when the product has unresolved link data
	 * adding sku to be added to a product
	 * @test
	 */
	public function testLinkProductsToBeLink()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getRelatedProducts'))
			->getMock();
		$product->expects($this->once())
			->method('getRelatedProducts')
			->will($this->returnValue(array(
				Mage::getModel('catalog/product')->addData(array(
					'sku' => '1234-Related'
				)),
			)));

		$linkUpdates = array(array(
			'link_type' => 'related',
			'operation_type' => 'Add',
			'link_to_unique_id' => 'clean_0',
		));

		$linkType = 'related';

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_buildProductLinkForSku'))
			->getMock();
		$feedCleanerModelProductMock->expects($this->never())
			->method('_buildProductLinkForSku')
			->will($this->returnValue(array(array(
				'link_type' => 'related',
				'operation_type' => 'Add',
				'link_to_unique_id' => 'clean_0',
			))));
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->will($this->returnValue(Mage::getModel('catalog/product')->addData(array(
				'sku' => '1234-Related',
				'entity_id' => 10,
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$this->assertSame(
			array(),
			$this->_reflectMethod($feedCleanerModelProductMock, '_linkProducts')->invoke($feedCleanerModelProductMock, $product, $linkUpdates, $linkType)
		);
	}

	/**
	 * provider for building product link sku
	 * return array
	 */
	public function providerBuildProductLinkForSku()
	{
		return array(
			array('1234-Related', 'related', 'Add'),
			array('4321-Related', 'related', 'Delete'),
			array('1234-Upsell', 'upsell', 'Add'),
			array('4321-Upsell', 'upsell', 'Delete'),
			array('1234-Crosssell', 'crosssell', 'Add'),
			array('4321-Crosssell', 'crosssell', 'Delete'),
		);
	}

	/**
	 * Test _buildProductLinkForSku method
	 * adding sku to missing link
	 * @param  string $sku       Sku the link should point to
	 * @param  string $type      Type of link to create
	 * @param  string $operation Type of operation, "Add" or "Delete"
	 * @dataProvider providerBuildProductLinkForSku
	 * @test
	 */
	public function testBuildProductLinkForSku($sku, $type, $operation)
	{
		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');
		$this->assertSame(
			array(
				'link_to_unique_id' => $sku,
				'link_type' => $type,
				'operation_type' => $operation,
			),
			$this->_reflectMethod($cleanerObject, '_buildProductLinkForSku')->invoke($cleanerObject, $sku, $type, $operation)
		);
	}

	/**
	 * Test _addUsedProducts method, when the product has used product data
	 * @test
	 */
	public function testAddUsedProducts()
	{
		$configurable = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts', 'getUsedProductIds'))
			->getMock();
		$configurable->expects($this->once())
			->method('saveProducts')
			->will($this->returnSelf());
		$configurable->expects($this->once())
			->method('getUsedProductIds')
			->will($this->returnValue(array(5)));
		$this->replaceByMock('resource_model', 'catalog/product_type_configurable', $configurable);

		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getSku', 'getId', 'getTypeInstance', 'setStatus', 'setVisibility', 'save'))
			->getMock();
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234'));
		$product->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$product->expects($this->once())
			->method('getTypeInstance')
			->will($this->returnValue($configurable));
		$product->expects($this->once())
			->method('setStatus')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('setVisibility')
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter', 'getAllIds'))
			->getMock();

		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo('*'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->at(1))
			->method('addFieldToFilter')
			->with($this->equalTo('style_id'), $this->equalTo(array('eq' => '1234')))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->at(2))
			->method('addFieldToFilter')
			->with($this->equalTo('entity_id'), $this->equalTo(array('neq' => '1')))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('getAllIds')
			->will($this->returnValue(array(2,3,4)));
		$this->replaceByMock('resource_model', 'catalog/product_collection', $catalogResourceModelProductMock);

		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getCollection'))
			->getMock();
		$catalogModelProductMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($catalogResourceModelProductMock));
		$this->replaceByMock('model', 'catalog/product', $catalogModelProductMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($cleanerObject, '_addUsedProducts')->invoke($cleanerObject, $product)
		);
	}

	/**
	 * Test _addUsedProducts method, when the product has no user product data
	 * @test
	 */
	public function testAddUsedProductsWithEmptyUseProudct()
	{
		$configurable = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts', 'getUsedProductIds'))
			->getMock();
		$configurable->expects($this->never())
			->method('saveProducts')
			->will($this->returnSelf());
		$configurable->expects($this->once())
			->method('getUsedProductIds')
			->will($this->returnValue(array()));

		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getSku', 'getId', 'getTypeInstance', 'setStatus', 'setVisibility', 'save'))
			->getMock();
		$product->expects($this->exactly(2))
			->method('getSku')
			->will($this->returnValue('1234'));
		$product->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$product->expects($this->once())
			->method('getTypeInstance')
			->will($this->returnValue($configurable));
		$product->expects($this->never())
			->method('setStatus')
			->will($this->returnSelf());
		$product->expects($this->never())
			->method('setVisibility')
			->will($this->returnSelf());
		$product->expects($this->never())
			->method('save')
			->will($this->returnSelf());

		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter', 'getAllIds'))
			->getMock();

		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo('*'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->at(1))
			->method('addFieldToFilter')
			->with($this->equalTo('style_id'), $this->equalTo(array('eq' => '1234')))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->at(2))
			->method('addFieldToFilter')
			->with($this->equalTo('entity_id'), $this->equalTo(array('neq' => '1')))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->any())
			->method('getAllIds')
			->will($this->returnValue(array()));
		$this->replaceByMock('resource_model', 'catalog/product_collection', $catalogResourceModelProductMock);

		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getCollection'))
			->getMock();
		$catalogModelProductMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($catalogResourceModelProductMock));
		$this->replaceByMock('model', 'catalog/product', $catalogModelProductMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($cleanerObject, '_addUsedProducts')->invoke($cleanerObject, $product)
		);
	}

	/**
	 * Test _addToCofigurableProduct method, when child product has a known Parent configruable product
	 * @test
	 */
	public function testAddToCofigurableProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getId', 'getStyleId'))
			->getMock();
		$product->expects($this->once())
			->method('getStyleId')
			->will($this->returnValue('1234-Configurable'));
		$product->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));

		$configurableModelMock = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts', 'getUsedProductIds'))
			->getMock();
		$configurableModelMock->expects($this->once())
			->method('saveProducts')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'), $this->equalTo(array(2,3,4, 1)))
			->will($this->returnSelf());
		$configurableModelMock->expects($this->once())
			->method('getUsedProductIds')
			->will($this->returnValue(array(2,3,4)));
		$this->replaceByMock('resource_model', 'catalog/product_type_configurable', $configurableModelMock);

		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getId', 'getTypeInstance', 'setStatus', 'setVisibility', 'save'))
			->getMock();
		$catalogModelProductMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(5));
		$catalogModelProductMock->expects($this->once())
			->method('getTypeInstance')
			->will($this->returnValue($configurableModelMock));
		$catalogModelProductMock->expects($this->once())
			->method('setStatus')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->once())
			->method('setVisibility')
			->with($this->equalTo(4))
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('1234-Configurable'))
			->will($this->returnValue($catalogModelProductMock));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($cleanerObject, '_addToCofigurableProduct')->invoke($cleanerObject, $product)
		);
	}

	/**
	 * Test _addToCofigurableProduct method, when child product has a known Parent configruable product
	 * @test
	 */
	public function testAddToCofigurableProductNoParentProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getId', 'getStyleId', 'getSku'))
			->getMock();
		$product->expects($this->exactly(2))
			->method('getStyleId')
			->will($this->returnValue('1234-Configurable'));
		$product->expects($this->once())
			->method('getId')
			->will($this->returnValue(0));
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234'));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('1234-Configurable'))
			->will($this->returnValue($product));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($cleanerObject, '_addToCofigurableProduct')->invoke($cleanerObject, $product)
		);
	}

	/**
	 * Test markProductClean method, marking product clean
	 * @test
	 */
	public function testMarkProductClean()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setIsClean'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(serialize(array())));
		$product->expects($this->once())
			->method('setIsClean')
			->with($this->equalTo(true))
			->will($this->returnSelf());

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$cleanerObject->markProductClean($product)
		);
	}

	/**
	 * Test markProductClean method, marking product clean
	 * @test
	 */
	public function testMarkProductCleanNoUnresolvedLinkData()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setIsClean', 'getSku'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(
				'a:1:{i:0;a:3:{s:9:"link_type";s:7:"related";s:14:"operation_type";s:3:"Add";s:17:"link_to_unique_id";s:7:"clean_0";}}'
			));
		$product->expects($this->once())
			->method('setIsClean')
			->with($this->equalTo(false))
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234'));

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Cleaner',
			$cleanerObject->markProductClean($product)
		);
	}
}

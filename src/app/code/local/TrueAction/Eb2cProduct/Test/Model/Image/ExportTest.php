<?php
/**
 * Testing image export methods
 */
class TrueAction_Eb2cProduct_Test_Model_Image_ExportTest extends TrueAction_Eb2cCore_Test_Base
{
	const MODEL_NAME              = 'eb2cproduct/image_export';
	const FILETRANSFER_MODEL_NAME = 'filetransfer/protocol_types_sftp';
	const VFS_ROOT                = 'testRoot';

	/**
	 * Return an Image Export Model that won't interact with Mage:app() store
	 * @return Eb2cProduct_Model_Image_Export
	 */
	protected function _stubImageExportModel()
	{
		// Don't want to actually create a file:
		$stubImageExport = $this->getModelMockBuilder(self::MODEL_NAME)
			->setMethods(
				array(
					'_createFileFromDom',
					'_getAllStoreIds',
					'_getStoreUrl',
					'_setCurrentStore',
				)
			)
			->getMock();
		$stubImageExport->expects($this->any())
			->method('_createFileFromDom')
			->will($this->returnValue('doesntMatter'));
		$stubImageExport->expects($this->any())
			->method('_getAllStoreIds')
			->will($this->returnValue(array(0,)));
		$stubImageExport->expects($this->any())
			->method('_getStoreUrl')
			->will($this->returnValue('http://cheetah.ebay.com'));
		$stubImageExport->expects($this->any())
			->method('_setCurrentStore')
			->will($this->returnSelf());

		return $stubImageExport;
	}

	/**
	 * Test build image feed
	 * @loadFixture ExportTest.yaml
	 * There is only one public method - buildExport, which takes null or a valid store Id as an argument,
	 * and returns the number of stores examined.
	 *
	 * 'null' means 'process all stores' so it's a number > 0
 	 * 0 is a the admin store id, so it should always be exactly 1 store processed
	 * An invalid store argument throws a Mage_Core_Model_Store_Exception
	 */
	public function testBuilder()
	{
		$this->markTestSkipped('not a unit test');
		$vfs = $this->getFixture()->getVfs();
		$this->replaceCoreConfigRegistry(
			array (
				'xsdFileImageExport' => $vfs->url(self::VFS_ROOT . '/xsd/Image_Feed.xsd'),
			)
		);

		$this->replaceByMock('model', self::MODEL_NAME, $this->_stubImageExportModel());

		// Don't want to actually send a file:
		$stubTransport = $this->getModelMockBuilder(self::FILETRANSFER_MODEL_NAME)
			->disableOriginalConstructor()
			->setMethods(array('sendFile'))
			->getMock();
		$stubTransport->expects($this->any())
			->method('sendFile')
			->will($this->returnValue(true));
		$this->replaceByMock('model', self::FILETRANSFER_MODEL_NAME, $stubTransport);

		// Builds 'all' XML (calls _getAllStoreIds for the list)
		$this->assertEquals(1,
			Mage::getModel(self::MODEL_NAME)->buildExport()
		);

		// 0 is always the admin store - if I provide an argument, I should process one and only 1 store
		$this->assertEquals(1,
			Mage::getModel(self::MODEL_NAME)->buildExport(0)
		);
	}

	/**
	 * @test
	 * Test no products - test that when getCollection finds no products, we don't crash, just return 0
	 */
	public function testNoProducts()
	{
		$stubCatalogProduct = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getCollection')) ->getMock();
		$stubCatalogProduct->expects($this->any())
			->method('getCollection')
			->will($this->returnValue(array()));
		$this->replaceByMock('model', 'catalog/product', $stubCatalogProduct);

		$stubImageExportModel = $this->_stubImageExportModel();
		$this->replaceByMock('model', self::MODEL_NAME, $stubImageExportModel);

        $imageRefl = new ReflectionObject(Mage::getModel(self::MODEL_NAME));
        $buildItemImages = $imageRefl->getMethod('_buildItemImages');
        $buildItemImages->setAccessible(true);

		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dummyImages = $dom->addElement('ItemImages', null, 'ns')->firstChild;
        $this->assertEquals(
			0,
			$buildItemImages->invoke($stubImageExportModel, $dummyImages)
		);
	}
}

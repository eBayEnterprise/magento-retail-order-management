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

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
		$vfs = $this->getFixture()->getVfs();
		$this->replaceCoreConfigRegistry(
			array (
				'xsdFileImageExport' => $vfs->url(self::VFS_ROOT . '/xsd/Image_Feed.xsd'),
			)
		);

		// Don't want to actually create a file:
		$stubImageExport = $this->getModelMockBuilder(self::MODEL_NAME)
			->setMethods(array('_createFileFromDom'))
			->getMock();
		$stubImageExport->expects($this->any())
			->method('_createFileFromDom')
			->will($this->returnValue('doesntMatter'));
		$this->replaceByMock('model', self::MODEL_NAME, $stubImageExport);

		// Don't want to actually send a file:
		$stubTransport = $this->getModelMockBuilder(self::FILETRANSFER_MODEL_NAME)
			->disableOriginalConstructor()
			->setMethods(array('sendFile'))
			->getMock();
		$stubTransport->expects($this->any())
			->method('sendFile')
			->will($this->returnValue(true));
		$this->replaceByMock('model', self::FILETRANSFER_MODEL_NAME, $stubTransport);

		// Builds all XML and Validates it; should return >0
		$this->assertGreaterThan(0,
			Mage::getModel(self::MODEL_NAME)->buildExport(null)
		);

		// 0 is always the admin store - if I provide an argument, I should process one and only 1 store
		$this->assertEquals(1,
			Mage::getModel(self::MODEL_NAME)->buildExport(0)
		);
	}
	/**
	 * Test an invalid store argument throws Mage_Core_Model_Store_Exception
	 * @expectedException Mage_Core_Model_Store_Exception
	 */
	public function testInvalidStoreArgument()
	{
		Mage::getModel(self::MODEL_NAME)->buildExport('Invalid Store Id - I am not a number');
	}

}

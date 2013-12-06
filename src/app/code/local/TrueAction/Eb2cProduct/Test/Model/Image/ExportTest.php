<?php
/**
 * Testing image export methods
 * 
 */
class TrueAction_Eb2cProduct_Test_Model_Image_ExportTest extends TrueAction_Eb2cCore_Test_Base
{
	const CLASS_NAME       = 'TrueAction_Eb2cProduct_Model_Image_Export';
	const MODEL_NAME       = 'eb2cproduct/image_export';
	const TESTBASE_DIRNAME = 'exportTest';

	/**
	 * Test build
	 * @large
	 * @todo fix this from an expectedException. It's being thrown because we can't pass xsd validate - we can't pass
	 * XXXXexpectedException Mage_Core_Exception
	 * @test
	 */
	public function testBuilder()
	{
		Mage::getModel(self::MODEL_NAME)->buildExport();
	}
}

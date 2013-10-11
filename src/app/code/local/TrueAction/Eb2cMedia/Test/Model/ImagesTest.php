<?php
/**
 * Testing the base model methods that aren't covered by Eb2cMedia Product methods
 * 
 */
class TrueAction_Eb2cMedia_Test_Model_ImagesTest extends TrueAction_Eb2cCore_Test_Base
{
	const CLASS_NAME      = 'TrueAction_Eb2cMedia_Model_Images';
	const MODEL_NAME      = 'eb2cmedia/images';

	/**
	 * Only testing getIdBySkuViewName; ProductsTest covers the rest
	 *
	 * @test
	 * @loadFixture imagesModel.yaml
	 */
	public function testProductImageCalls()
	{
		$testImageModel = Mage::getModel(
			self::MODEL_NAME
		);

		$this->assertInstanceOf(
			self::CLASS_NAME,
			$testImageModel
		);

		$this->assertEquals(
			20,
			$testImageModel->getIdBySkuViewNameSize(
				'29-303132',
				'topright',
				'small_image',
				77, // width
				83 // height
			)
		);
	}
}

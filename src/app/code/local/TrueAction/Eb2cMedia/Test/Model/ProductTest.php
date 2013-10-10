<?php
/**
 * Tests the extra methods necessary to override Mage Product to use Eb2cMedia Product
 * 
 */
class TrueAction_Eb2cMedia_Test_Model_ProductTest extends TrueAction_Eb2cCore_Test_Base
{
	const CLASS_NAME      = 'TrueAction_Eb2cMedia_Model_Product';
	const MODEL_NAME      = 'eb2cmedia/product';
	const TEST_IMAGE_HOST = 'http://cjbus.imageg.net';
	const NUM_TEST_IMAGES = 3;

	/**
	 * Eb2cMedia Images Methods access as members of a product
	 *
	 * @test
	 * @loadFixture imagesModel.yaml
	 */
	public function testProductImageCalls()
	{
		$testProduct = Mage::getModel(
			self::MODEL_NAME,
			array(
				'sku' => '29-303132'
			)
		);

		$this->assertInstanceOf(
			self::CLASS_NAME,
			$testProduct
		);

		$this->assertEquals(
			3,
			$testProduct->getMediaGalleryImages()->count()
		);

		$this->assertEquals(
			self::TEST_IMAGE_HOST . '/some/sensible/path.png',
			$testProduct->getImageUrl()
		);

		$this->assertEquals(
			self::TEST_IMAGE_HOST . '/some/other/sensible/path.jpg',
			$testProduct->getSmallImageUrl()
		);

		$this->assertEquals(
			self::TEST_IMAGE_HOST . '/yet/another/sensible/path.png',
			$testProduct->getThumbnailUrl()
		);
	}
}

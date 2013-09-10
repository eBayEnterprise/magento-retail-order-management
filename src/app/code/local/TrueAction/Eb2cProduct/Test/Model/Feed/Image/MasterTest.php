<?php
/**
 * 
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Image_MasterTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Instantiate the Model, test we get the expected model back
	 *
	 * @test
	 */
	public function testIsInstanceOf()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Image_Master',
			Mage::getModel('eb2cproduct/feed_image_master')
		);
	}

	/**
	 * Instantiate the Model, test we get return code of -1 back
	 *
	 * @test
	 */
	public function testReturnsZero()
	{
		$this->assertSame(
			0,
			Mage::getModel('eb2cproduct/feed_image_master')->processFeeds()
		);
	}
}

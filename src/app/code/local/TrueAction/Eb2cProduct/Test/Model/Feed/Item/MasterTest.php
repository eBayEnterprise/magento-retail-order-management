<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_MasterTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_master;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_master = Mage::getModel('eb2cproduct/feed_item_master');
	}

	/**
	 * testing processFeeds method
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeeds()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$this->_master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->_master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$this->assertNull($this->_master->processFeeds());
	}
}

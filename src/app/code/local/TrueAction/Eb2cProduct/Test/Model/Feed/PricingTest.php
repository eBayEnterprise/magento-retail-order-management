<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_PricingTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @test
	 * @loadExpectation
	 */
	public function testFeedPricingConfig()
	{
		$this->replaceCoreConfigRegistry(array(
			'pricingFeedLocalPath' => 'TrueAction/Eb2c/Feed/Product/Pricing/',
			'storeId' => 'storeId',
			'pricingFeedRemoteReceivedPath' => '/Inbox/Pricing/storeId/',
			'pricingFeedFilePattern' => 'Price*.xml'
		));
		$pricingFeed = Mage::getModel('eb2cproduct/feed_pricing');

		$this->assertSame(
			$this->expected('config')->getLocalPath(),
			$this->_reflectProperty($pricingFeed, '_feedLocalPath')->getValue($pricingFeed)
		);

		$this->assertSame(
			$this->expected('config')->getRemotePath(),
			$this->_reflectProperty($pricingFeed, '_feedRemotePath')->getValue($pricingFeed)
		);

		$this->assertSame(
			$this->expected('config')->getFilePattern(),
			$this->_reflectProperty($pricingFeed, '_feedFilePattern')->getValue($pricingFeed)
		);
	}
}

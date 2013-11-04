<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_PricingTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @test
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testFeedPricingConfig()
	{
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

		$this->assertSame(
			$this->expected('config')->getEventType(),
			$this->_reflectProperty($pricingFeed, '_feedEventType')->getValue($pricingFeed)
		);
	}
}

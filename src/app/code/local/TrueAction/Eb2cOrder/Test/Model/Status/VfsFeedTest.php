<?php
/**
 *
 */
class TrueAction_Eb2cOrder_Test_Model_Status_VfsFeedTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	/**
	 * @test
	 */
	public function testWithoutFixture()
	{
		$vfs = $this->getFixture()->getVfs();
		$vfs->apply(
			array(
				'inbound' => array(
					'A' => array(
						'alpha.txt' => 'Alpha Content',
					),
					'B' => array (
						'beta.txt' => 'Beta Content',
					),
					'emptyFolder' => array(),
					'sampleList.csv' => '10,20,30,40,50,ham,apple,bread',
				)
			)
		);
		$contents = file_get_contents($vfs->url('inbound/B/beta.txt'));
		$this->assertStringEqualsFile($vfs->url('inbound/B/beta.txt'), 'Beta Content');
	}

	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testWithFixture()
	{
		$vfs = $this->getFixture()->getVfs();
		// Mock a few eb2ccore/feed methods. The methods I'm mocking are already tested by core, so no harm done.
		$mockCoreFeedMethods = array(
			'cd'					=> true,
			'checkAndCreateFolder'	=> true,
			'getInboundFolder'		=> '/inbound',
			'lsInboundFolder'		=> array (
											$vfs->url('/inbound/invalidXml.xml'),
											$vfs->url('/inbound/badEb2cSample.xml'),
											$vfs->url('/inbound/goodEb2cSample.xml'),
										),
		);
		$this->replaceModel('eb2ccore/feed',$mockCoreFeedMethods);

		// The transport protocol is mocked - we just pretend we got files
		$this->replaceModel('filetransfer/protocol_types_ftp', array('getFile'=>true,));

		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'statusFeedLocalPath' => $vfs->url('/inbound'),
			)
		);

		$rc = Mage::getModel('eb2corder/status_feed')->processFeeds();
		$this->assertSame(true, $rc);
	}
}

<?php
/**
 * Test the Order Stats Feed Module
 */
class TrueAction_Eb2cOrder_Test_Model_Status_VfsFeedTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	/**
	 * Fakes up some files to send to the feed processor
	 *
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testWithFixture()
	{
		$vfs = $this->getFixture()->getVfs();
		// Mock a few eb2ccore/feed methods so we can get our way through receiving/ listing files
		$this->replaceModel(
			'eb2ccore/feed',		// Class alias to replace ... 
			array(					// Array of methods => return values for mocks
				'cd'					=> true,
				'checkAndCreateFolder'	=> true,
				'getInboundFolder'		=> 'inbound',
				'lsInboundFolder'		=> array (
												$vfs->url('inbound/invalidXml.xml'),
												$vfs->url('inbound/badEb2cSample.xml'),
												$vfs->url('inbound/goodEb2cSample.xml'),
											),
			)
		);

		// The transport protocol is mocked - we just pretend we got files
		$this->replaceModel('filetransfer/protocol_types_ftp', array('getFile'=>true,));

		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'statusFeedLocalPath' => $vfs->url('inbound'),
			)
		);

		$rc = Mage::getModel('eb2corder/status_feed')->processFeeds();
		$this->assertSame(true, $rc);
	}
}

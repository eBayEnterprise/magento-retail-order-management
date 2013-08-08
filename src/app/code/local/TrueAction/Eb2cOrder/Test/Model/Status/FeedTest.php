<?php
/**
 *
 */
class TrueAction_Eb2cOrder_Test_Model_Feed_StatusTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * @TODO: Mock up configuration such that it returns an ftp-type config. @TODO this soon, too.
	 */
	public function testProcessFeeds()
	{
		// The transport protocol is mocked - just pretend you got files
		$mockReceiver = $this->getMock( get_class(Mage::getModel('filetransfer/protocol_types_ftp')), array('getFile',));
		$mockReceiver->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));

		// Feed IO Core is mocked to simply return a couple of file names
		$mockFeedIo = $this->getMock( get_class(Mage::getModel('eb2ccore/feed')), array('setBaseFolder','lsInboundFolder'));
		$mockFeedIo->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnValue($mockFeedIo));
		$mockFeedIo->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
					array(
						'someFile.xml',
						'someOtherFile.xml',
					)
				)
			);

		$this->replaceByMock('model', 'eb2ccore/feed', $mockFeedIo);
		$this->replaceByMock('model', 'filetransfer/protocol_types_ftp', $mockReceiver);

		$feedIo = Mage::getModel('eb2corder/status_feed');
		$rc = $feedIo->processFeeds();
		$this->assertSame(true, $rc);
	}
}

<?php
class TrueAction_Eb2cCore_Test_Helper_FeedTest extends EcomDev_PHPUnit_Test_Case
{
	const FEED_PATH = '/FeedTest/fixtures/';
	private function _getDoc($feed)
	{
		$d = new TrueAction_Dom_Document();
		$d->load(__DIR__ . self::FEED_PATH . $feed . '.xml');
		return $d;
	}
	public function providerValidateHeader()
	{
		return array(
			array('bad-dest-feed'),
			array('bad-event-feed'),
			array('good-feed'),
			array('no-dest-feed'),
			array('no-event-feed'),
		);
	}
	/**
	 * validateHeader should be true when the header has the expected DestinationId and Event Type nodes.
	 * @test
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($feed)
	{
		$doc = $this->_getDoc($feed);
		$isValid = $this->expected($feed)->getIsValid();
		$this->assertSame($isValid, Mage::helper('eb2ccore/feed')->validateHeader($doc, 'GoodEvent'));
	}

	/**
	 * @test
	 */
	public function testFileTransferDefaultIsSftp()
	{
		$hlpr = Mage::helper('eb2ccore/feed');
		$configData = Mage::helper('filetransfer')
			->getInitData($hlpr::FILETRANSFER_CONFIG_PATH);
		$this->assertSame('sftp', $configData['protocol_code']);
	}
}

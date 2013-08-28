<?php
class TrueAction_Eb2cCore_Test_Helper_FeedTest extends EcomDev_PHPUnit_Test_Case
{
	public function providerValidateHeader()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	public function providerValidateHeaderWithInvalidNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed-with-invalid-node.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	public function providerValidateHeaderWithFeedInvalidData()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed-with-feed-invalid-data.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	/**
	 * test validateHeader method, when the header file is valid
	 *
	 * @test
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			true,
			Mage::helper('eb2ccore/feed')
				->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
	}

	/**
	 * test validateHeader method, when the header feed doesn't have valid xml node
	 *
	 * @test
	 * @dataProvider providerValidateHeaderWithInvalidNode
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeaderWithInvalidNode($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			false,
			Mage::helper('eb2ccore/feed')
				->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
	}

	/**
	 * test validateHeader method, when the header feed data doesn't match expected
	 *
	 * @test
	 * @dataProvider providerValidateHeaderWithFeedInvalidData
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeaderWithFeedInvalidData($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			false,
			Mage::helper('eb2ccore/feed')
				->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
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

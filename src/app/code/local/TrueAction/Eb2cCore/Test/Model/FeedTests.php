<?php
/**
 * This class tests the 
 */
class TrueAction_Eb2cCore_Test_Model_FeedTest extends EcomDev_PHPUnit_Test_Case
{
	const	TEST_FILE_PFX	=	'TestFeed';
	const	TEST_XML_DATA = 
'<MessageHeader>
	<Standard>GSI</Standard>
	<HeaderVersion>2.3</HeaderVersion>
	<VersionReleaseNumber>2.3.1</VersionReleaseNumber>
	<SourceData>
		<SourceId>TMSEU_DC001</SourceId>
		<SourceType>WMS</SourceType>
	</SourceData>
	<DestinationData>
		<DestinationId>GSI</DestinationId>
		<DestinationType>FH</DestinationType>
	</DestinationData>
	<EventType>InventoryStatus</EventType>
	<MessageData>
		<MessageId>123456</MessageId>
		<CorrelationId>456789</CorrelationId>
	</MessageData>
	<CreateDateAndTime>2012-01-11T12:19:05-06:00</CreateDateAndTime>
</MessageHeader>';
	private $_tmpDirName;

	public function setUp()
	{
		$mktempOutput = array();
		exec('mktemp -d', $mktempOutput);
		$this->_tmpDirName = $mktempOutput[0];
	}

	public function tearDown()
	{
		if( strncmp($this->_tmpDirName,'/tmp/tmp',8) ) {
			throw new Exception( 'Cowardly refusing to recursively remove temp directory with a prefix unknown to me.');
		}
		Mage::getModel('eb2ccore/feed')->rmdirRecursive($this->_tmpDirName);
	}

	/**
	 * @test
	 */
	public function testFeedMethods()
	{
		$feed = Mage::getModel('eb2ccore/feed');
		$feed->setBaseFolder($this->_tmpDirName);

		$fileset = array();
		for( $i = 0; $i < 4; $i++ ) {
			$fileset[$i] = tempnam($feed->getInboundFolder(), self::TEST_FILE_PFX );
			file_put_contents($fileset[$i],self::TEST_XML_DATA);
		}

		// In real-life usage, your would setBaseFolder => run receiver into getInboundFolder() => loop via lsInboundFolder() => mvToXXXFolder.
		foreach( $feed->lsInboundFolder() as $aFilePath ) {
			$this->assertFileExists($aFilePath);
		}

		// The move tests assert the dest exists after the move, and in doing so are testing the getXXXFolder methods:
		// Test moving a file to outbound
		$feed->mvToOutboundFolder($fileset[0]);
		$this->assertFileNotExists($fileset[0]);
		$this->assertFileExists($feed->getOutboundFolder().$feed->dirsep().basename($fileset[0]));

		// Test moving a file to archive
		$feed->mvToArchiveFolder($fileset[1]);
		$this->assertFileNotExists($fileset[1]);
		$this->assertFileExists($feed->getArchiveFolder().$feed->dirsep().basename($fileset[1]));

		// Test moving a file to error
		$feed->mvToErrorFolder($fileset[2]);	
		$this->assertFileNotExists($fileset[2]);
		$this->assertFileExists($feed->getErrorFolder().$feed->dirsep().basename($fileset[2]));

		// Test moving a file to tmp; we save to a var name because we're going to test moving it back.
		$feed->mvToTmpFolder($fileset[3]);	
		$this->assertFileNotExists($fileset[3]);
		$targetTempFile = $feed->getTmpFolder().$feed->dirsep().basename($fileset[3]);
		$this->assertFileExists($targetTempFile);

		// Test moving back into the Inbound Folder:
		$feed->mvToInboundFolder($targetTempFile);
		$this->assertFileNotExists($targetTempFile);
		$this->assertFileExists($feed->getInboundFolder().$feed->dirsep().basename($targetTempFile));
	}
}

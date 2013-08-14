<?php
/**
 *
 */
use org\bovigo\vfs;
class TrueAction_Eb2cOrder_Test_Model_Feed_StatusTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const SAMPLE_GOOD_XML = <<<END_SAMPLE_GOOD_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderStatusUpdate fileType="Magento Order Status Update" fileStartTime="2013-08-09 19:32:39.0" fileEndTime="2013-08-12 13:43:24.0" recordCount="791">
  <MessageHeader>
    <Standard>eBay_Enterprise</Standard>
    <HeaderVersion>2.3</HeaderVersion>
    <VersionReleaseNumber>4</VersionReleaseNumber>
    <SourceData>
      <SourceId>OMS</SourceId>
      <SourceType>OrderManagementSystem</SourceType>
    </SourceData>
    <DestinationData>
      <DestinationId>TAN-OS-CLI</DestinationId>
      <DestinationType>MAILBOX</DestinationType>
    </DestinationData>
    <EventType>OrderStatus</EventType>
    <MessageData>
      <MessageId>20130812010825</MessageId>
      <CorrelationId>0</CorrelationId>
    </MessageData>
    <CreateDateAndTime>2013-08-12T13:43:24.171Z</CreateDateAndTime>
  </MessageHeader>
  <OrderStatusEvents>
    <OrderStatusEvent>
      <OrderStatusEventTimeStamp>2013-08-10T02:37:48000Z</OrderStatusEventTimeStamp>
      <StoreCode>TMS_US</StoreCode>
      <OrderId>200001483664</OrderId>
      <StatusId>1100.60</StatusId>
      <ProcessTypeKey>ORDER_FULFILLMENT</ProcessTypeKey>
      <StatusName>Created</StatusName>
      <OrderEventDetail>
        <OrderLineId>1</OrderLineId>
        <ItemId>21-885641003920</ItemId>
        <Qty>0</Qty>
      </OrderEventDetail>
    </OrderStatusEvent>
    <OrderStatusEvent>
      <OrderStatusEventTimeStamp>2013-08-10T02:37:49000Z</OrderStatusEventTimeStamp>
      <StoreCode>TMS_US</StoreCode>
      <OrderId>200001483663</OrderId>
      <StatusId>1100.60</StatusId>
      <ProcessTypeKey>ORDER_FULFILLMENT</ProcessTypeKey>
      <StatusName>Created</StatusName>
      <OrderEventDetail>
        <OrderLineId>1</OrderLineId>
        <ItemId>21-885641003920</ItemId>
        <Qty>0</Qty>
      </OrderEventDetail>
    </OrderStatusEvent>
    <OrderStatusEvent>
      <OrderStatusEventTimeStamp>2013-08-10T02:39:41000Z</OrderStatusEventTimeStamp>
      <StoreCode>TMS_US</StoreCode>
      <OrderId>200001483665</OrderId>
      <StatusId>1100.60</StatusId>
      <ProcessTypeKey>ORDER_FULFILLMENT</ProcessTypeKey>
      <StatusName>Created</StatusName>
      <OrderEventDetail>
        <OrderLineId>1</OrderLineId>
        <ItemId>21-885641003920</ItemId>
        <Qty>0</Qty>
      </OrderEventDetail>
    </OrderStatusEvent>
  </OrderStatusEvents>
</OrderStatusUpdate>
END_SAMPLE_GOOD_XML;

	const SAMPLE_INVALID_EB2C_XML = <<<END_SAMPLE_INVALID_EB2C_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderStatusUpdate>
  <MessageHeader>
    <Standard>eBay_Enterprise</Standard>
    <HeaderVersion>2.3</HeaderVersion>
    <VersionReleaseNumber>5</VersionReleaseNumber>
  </MessageHeader>
</OrderStatusUpdate>
END_SAMPLE_INVALID_EB2C_XML;

	const SAMPLE_INVALID_XML = <<<END_SAMPLE_INVALID_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderStatusUpdate>
  <MessageHeader>
END_SAMPLE_INVALID_XML;

	const FAKE_ROOT_DIR			= 'root';
	const GOOD_XML_FILE			= 'goodXmlFile.xml';
	const INVALID_EB2C_XML_FILE	= 'notEb2cFile.xml';
	const INVALID_XML_FILE		= 'badXmlFile.xml';

    public function setUp() {
		// Set up a virtual file system and create the necessary mock inbound folder.
		$this->getFixture()->getVfs();
		vfs\vfsStreamWrapper::register();
		vfs\vfsStream::setup(self::FAKE_ROOT_DIR);

		$feedModel = Mage::getModel('eb2ccore/feed');
		$vfsInboundFolder = vfs\vfsStream::newDirectory($feedModel::INBOUND_FOLDER_NAME)
												->at(vfs\vfsStreamWrapper::getRoot());
		$vfsXmlPathname = self::FAKE_ROOT_DIR . DS . $feedModel::INBOUND_FOLDER_NAME . DS;

		// Create virtual XML files: 1 good file, 1 that fails eb2c validation, and 1 bad XML
		vfs\vfsStream::newFile(self::GOOD_XML_FILE)
						->setContent(self::SAMPLE_GOOD_XML)
						->at($vfsInboundFolder);
		$vfsGoodXmlFile = vfs\vfsStream::url($vfsXmlPathname . self::GOOD_XML_FILE); 

		vfs\vfsStream::newFile(self::INVALID_EB2C_XML_FILE)
						->setContent(self::SAMPLE_INVALID_EB2C_XML)
						->at($vfsInboundFolder);
		$vfsInvalidEb2cXmlFile = vfs\vfsStream::url($vfsXmlPathname . self::INVALID_EB2C_XML_FILE);

		vfs\vfsStream::newFile(self::INVALID_XML_FILE)
						->setContent(self::SAMPLE_INVALID_XML)
						->at($vfsInboundFolder);
		$vfsInvalidXmlFile = vfs\vfsStream::url($vfsXmlPathname . self::INVALID_XML_FILE);


		// Mock a few eb2ccore/feed methods. The methods I'm mocking are already tested by core, so no harm done.
		$mockCoreFeedMethods = array(
			'cd'					=> true,
			'checkAndCreateFolder'	=> true,
			'getInboundFolder'		=> $feedModel::INBOUND_FOLDER_NAME,
			'lsInboundFolder'		=> array (
											$vfsGoodXmlFile,
											$vfsInvalidEb2cXmlFile,
											$vfsInvalidXmlFile,
										),
		);
		$this->replaceModel('eb2ccore/feed',$mockCoreFeedMethods);

		// The transport protocol is mocked - we just pretend we got files
		$this->replaceModel('filetransfer/protocol_types_ftp', array('getFile'=>true,));

		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'statusFeedLocalPath' => $vfsInboundFolder->getName(),
			)
		);
    }

	/**
	 * Tests the order status feed processor. Should cover everything. SetUp does all the hard work to make it 'look like' processFeeds
	 * has received some remote files and that they are in the inbound folder, ready for processing.
	 * @test
	 * @large
	 */
	public function testProcessFeeds()
	{
		$rc = Mage::getModel('eb2corder/status_feed')->processFeeds();
		$this->assertSame(true, $rc);
	}
}

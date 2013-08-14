<?php
require_once 'vfsStream/vfsStream.php';
/**
 *
 */
class TrueAction_Eb2cOrder_Test_Model_Feed_StatusTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const SAMPLE_XML = <<<END_SAMPLE_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderStatusUpdate fileType="Magento Order Status Update" fileStartTime="2013-08-09 19:32:39.0" fileEndTime="2013-08-12 13:43:24.0" recordCount="791">
  <MessageHeader>
    <Standard>eBay_Enterprise</Standard>
    <HeaderVersion>EWS_eb2c_1.0</HeaderVersion>
    <VersionReleaseNumber>EWS_eb2c_1.0</VersionReleaseNumber>
    <SourceData>
      <SourceId>OMS</SourceId>
      <SourceType>OrderManagementSystem</SourceType>
    </SourceData>
    <DestinationData>
      <DestinationId>EE_OrderRTStatusXML</DestinationId>
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
END_SAMPLE_XML;

	const FAKE_ROOT_DIR = 'root';

	const FAKE_XML_FILE = 'fakeXmlFile.xml';

    public function setUp() {
		// Set up a virtual file system and create the necessary mock inbound folder.
		$feedModel = Mage::getModel('eb2ccore/feed');
		vfsStreamWrapper::register();
		vfsStream::setup(self::FAKE_ROOT_DIR);
		$vfsInboundFolder = vfsStream::newDirectory($feedModel::INBOUND_FOLDER_NAME)->at(vfsStreamWrapper::getRoot());

		// Now, let's create a virtual XML file with some sample xml data:
		$vfsXmlFile = vfsStream::newFile(self::FAKE_XML_FILE)
						->setContent(self::SAMPLE_XML)
						->at($vfsInboundFolder);
		$vfsXmlFullPath = vfsStream::url(self::FAKE_ROOT_DIR . DS . $feedModel::INBOUND_FOLDER_NAME . DS . self::FAKE_XML_FILE);

		// Mock a few eb2ccore/feed methods. The methods I'm mocking are already tested by core, so no harm done.
		$mockCoreFeedMethods = array(
			'cd'					=> true,
			'checkAndCreateFolder'	=> true,
			'getInboundFolder'		=> $feedModel::INBOUND_FOLDER_NAME,
			'lsInboundFolder'		=> array ($vfsXmlFullPath),
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

		// $headerVersionNode = $xpath->query('//*/MessageHeader/HeaderVersion'); // Leaving this comment to help me fix problem in Core/Helper/Feed
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

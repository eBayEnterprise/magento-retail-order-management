<?php
/**
 * Test the Order Stats Feed Module
 */
class TrueAction_Eb2cOrder_Test_Model_Status_VfsFeedTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const VFS_ROOT = 'testBase';

	protected function _getStubStatusFeedModel()
	{
		$stubStatusFeed = $this->getModelMockBuilder('eb2corder/status_feed')
			->setMethods(
				array(
					'_loadMageOrder',
//					'_confirmation',
//					'_shipment',
				)
			)
			->getMock();

/*
		$stubStatusFeed->expects($this->any())
			->method('_confirmation')
			->will($this->returnValue(true));

		$stubStatusFeed->expects($this->any())
			->method('_shipment')
			->will($this->returnValue(true));
 */

		$mockSalesOrder = $this->getMockSalesOrder();
		$stubStatusFeed->expects($this->any())
			->method('_loadMageOrder')
			->will($this->returnValue($mockSalesOrder));

		return $stubStatusFeed;
	}

	protected function _getHugeDom()
	{
		$vfs = $this->getFixture()->getVfs();
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dom->load($vfs->url(self::VFS_ROOT . '/snippets/hugeStatusFile.xml'));
		return $dom;
	}

	protected function _getTestDom()
	{
		$vfs = $this->getFixture()->getVfs();
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dom->load($vfs->url(self::VFS_ROOT . '/snippets/testExtract.xml'));
		return $dom;
	}

	protected function _getVfsXpathQuery($query)
	{
		$xpath = new DOMXPath($this->_getTestDom());
		$result =  $xpath->query($query);
		return $result->item(0);
	}

	protected function _getTestOrderStatusNode()
	{
		return $this->_getVfsXpathQuery('/OrderStatuses/OrderStatus[1]');
	}

	protected function _getTestOrderHeaderNode()
	{
		return $this->_getVfsXpathQuery('/OrderStatuses/OrderStatus[1]/Status[1]/Order[1]/OrderHeader[1]');
	}

	protected function _getTestOrderNode()
	{
		return $this->_getVfsXpathQuery('/OrderStatuses/OrderStatus[1]/Status[1]/Order[1]');
	}


	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testExtractStatusType()
	{
		$extractor = Mage::getModel('eb2corder/status_feed_extractor');
		$this->assertEquals(
			'Confirmation',
			$extractor->extractStatusType($this->_getTestOrderStatusNode())
		);
	}

	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testExtractStatusTimeStamp()
	{
		$extractor = Mage::getModel('eb2corder/status_feed_extractor');
		$this->assertEquals(
			'2013-12-06 11:26:03',
			$extractor->extractStatusTimeStamp($this->_getTestOrderStatusNode())
		);
	}

	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testExtractExternalOrderNumber()
	{
		$extractor = Mage::getModel('eb2corder/status_feed_extractor');
		$this->assertEquals(
			'13650739',
			$extractor->extractExternalOrderNumber($this->_getTestOrderHeaderNode())
		);
	}

	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testExtractor()
	{
		$extractor = Mage::getModel('eb2corder/status_feed_extractor');
		$orderNode = $extractor->extractOrderNode($this->_getTestOrderStatusNode());

		$shipments = $extractor->extractShipmentsNode($orderNode);

		foreach( $extractor->extractShipmentNode($shipments) as $shipment ) {
			$shipmentId = $extractor->extractShipmentId($shipment);
			$this->assertThat(
				$shipmentId,
				$this->logicalOr('ID20131104000938475073587','Another_Just_To_Be_Sure_I_Can')
			);

			foreach ($extractor->extractOrderItemsNode($shipment) as $orderItemsNode) {
				foreach ($extractor->extractOrderItemNode($orderItemsNode) as $orderItemNode) {
					$orderDetail = $extractor->orderItemNodeToObject($orderItemNode);

					$this->assertThat(
						$orderDetail->getSku(),
						$this->logicalOr('9632907','9632935')
					);

					$this->assertThat(
						$orderDetail->getQuantity(),
						$this->logicalOr('1','7452345')
					);

					foreach ($orderDetail->getTrackingNumberList() as $tracking) {
						$this->assertThat(
							$tracking->getTrackingNumber(),
							$this->logicalOr('TN1l','TN2')
						);

						$this->assertThat(
							$tracking->getTrackingUrl(),
							$this->logicalOr('TURL1','TURL2')
						);
					}
				}
			}
		}
	}

	/**
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testFeed()
	{
	/*
		$feedLive = Mage::getModel('eb2corder/status_feed');
		$feedLive->processDom($this->_getHugeDom());
	 */

		// Mock up the feed methods that do confirm, shipment status etc.
		$feedStub = $this->_getStubStatusFeedModel();
		$this->replaceByMock('model', 'eb2corder/status_feed', $feedStub);
		$feedStub->processDom($this->_getTestDom());
	}
}

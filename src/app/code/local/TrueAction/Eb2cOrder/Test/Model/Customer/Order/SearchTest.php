<?php
class TrueAction_Eb2cOrder_Test_Model_Customer_Order_SearchTest
	extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'testBase';

	/**
	 * Provide data, an order
	 * @return array
	 */
	public function providerRequestOrderSummary()
	{
		return array(array(1));
	}

	/**
	 * Test the processing of data for a order. customer id is provided via magic setters.
	 * @param int $customerId, the magento customer id to query eb2c with
	 * @dataProvider providerRequestOrderSummary
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testRequestOrderSummary($customerId)
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$orderHelperMock = $this->getHelperMockBuilder('eb2corder/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$orderHelperMock->expects($this->exactly(2))
			->method('getConfig')
			->will($this->returnValue((object) array(
				'xsdFileSearch' => 'Order-Service-Search-1.0.xsd',
				'apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0',
				'apiSearchService' => 'customers',
				'apiSearchOperation' => 'orders/get',
			)));
		$this->replaceByMock('helper', 'eb2corder', $orderHelperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument', 'getApiUri'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue(Mage::helper('eb2ccore')->getNewDomDocument()));
		$coreHelperMock->expects($this->once())
			->method('getApiUri')
			->with($this->equalTo('customers'), $this->equalTo('orders/get'))
			->will($this->returnValue('http://example.com/customers/orders/get.xml'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->isInstanceOf('TrueAction_Dom_Document'), 'Order-Service-Search-1.0.xsd', 'http://example.com/customers/orders/get.xml')
			->will($this->returnValue(file_get_contents($vfs->url(self::VFS_ROOT . '/customer_order_search/response/orderSummaryResponse.xml'))));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame($this->expected('response')->getXml(), Mage::getModel('eb2corder/customer_order_search')->requestOrderSummary($customerId));
	}
	/**
	 * Test the parse response of eb2c call data into varien_object
	 * @param string $orderSummaryReply the xml response from eb2c
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testParseResponse($orderSummaryReply)
	{
		$orderHelperMock = $this->getHelperMockBuilder('eb2corder/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$orderHelperMock->expects($this->once())
			->method('getConfig')
			->will($this->returnValue((object) array('apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0')));
		$this->replaceByMock('helper', 'eb2corder', $orderHelperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument', 'extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue(Mage::helper('eb2ccore')->getNewDomDocument()));
		$coreHelperMock->expects($this->exactly(7))
			->method('extractNodeVal')
			->with($this->isInstanceOf('DOMNodeList'))
			->will($this->returnCallback(
				function ($nodeList) {return ($nodeList->length)? $nodeList->item(0)->nodeValue : null;}
			));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$response = Mage::getModel('eb2corder/customer_order_search')->parseResponse($orderSummaryReply);

		$orderId = $this->expected('response')->getCustomerOrderId();

		$this->assertCount(1, $response);
		$this->assertSame($this->expected('response')->getId(), $response[$orderId]->getId());
		$this->assertSame($this->expected('response')->getOrderType(), $response[$orderId]->getOrderType());
		$this->assertSame($this->expected('response')->getTestType(), $response[$orderId]->getTestType());
		$this->assertSame($this->expected('response')->getModifiedTime(), $response[$orderId]->getModifiedTime());
		$this->assertSame($this->expected('response')->getCustomerOrderId(), $response[$orderId]->getCustomerOrderId());
		$this->assertSame($this->expected('response')->getCustomerId(), $response[$orderId]->getCustomerId());
		$this->assertSame($this->expected('response')->getOrderDate(), $response[$orderId]->getOrderDate());
		$this->assertSame($this->expected('response')->getDashboardRepId(), $response[$orderId]->getDashboardRepId());
		$this->assertSame($this->expected('response')->getStatus(), $response[$orderId]->getStatus());
		$this->assertSame((float) $this->expected('response')->getOrderTotal(), $response[$orderId]->getOrderTotal());
		$this->assertSame($this->expected('response')->getSource(), $response[$orderId]->getSource());
	}
}

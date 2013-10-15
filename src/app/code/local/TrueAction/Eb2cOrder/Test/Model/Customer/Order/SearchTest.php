<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
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
			->will($this->returnValue(new TrueAction_Dom_Document('1.0', 'UTF-8')));
		$coreHelperMock->expects($this->once())
			->method('getApiUri')
			->with($this->equalTo('customers'), $this->equalTo('orders/get'))
			->will($this->returnValue('http://fake.apipartner.com/customers/orders/get.xml'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'setXsd', 'request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('setUri')
			->with($this->equalTo('http://fake.apipartner.com/customers/orders/get.xml'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('setXsd')
			->with($this->equalTo('Order-Service-Search-1.0.xsd'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(file_get_contents($vfs->url(self::VFS_ROOT . '/customer_order_search/response/orderSummaryResponse.xml'))));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame($this->expected('reponse')->getXml(), Mage::getModel('eb2corder/customer_order_search')->requestOrderSummary($customerId));
	}

	/**
	 * Testing when requestOrderSummary api request call throw Zend_Http_Client_Exception
	 * @param int $customerId, the magento customer id to query eb2c with
	 * @dataProvider providerRequestOrderSummary
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testRequestOrderSummaryWithZendHttpClientException($customerId)
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
			->will($this->returnValue(new TrueAction_Dom_Document('1.0', 'UTF-8')));
		$coreHelperMock->expects($this->once())
			->method('getApiUri')
			->with($this->equalTo('customers'), $this->equalTo('orders/get'))
			->will($this->returnValue('http://fake.apipartner.com/customers/orders/get.xml'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'setXsd', 'request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('setUri')
			->with($this->equalTo('http://fake.apipartner.com/customers/orders/get.xml'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('setXsd')
			->with($this->equalTo('Order-Service-Search-1.0.xsd'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->throwException(new Zend_Http_Client_Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame('', Mage::getModel('eb2corder/customer_order_search')->requestOrderSummary($customerId));
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
			->will($this->returnValue(new TrueAction_Dom_Document('1.0', 'UTF-8')));
		$coreHelperMock->expects($this->exactly(7))
			->method('extractNodeVal')
			->with($this->isInstanceOf('DOMNodeList'))
			->will($this->returnCallback(
				function ($nodeList) {return ($nodeList->length)? $nodeList->item(0)->nodeValue : null;}
			));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$response = Mage::getModel('eb2corder/customer_order_search')->parseResponse($orderSummaryReply);

		$orderId = $this->expected('reponse')->getCustomerOrderId();

		$this->assertCount(1, $response);
		$this->assertSame($this->expected('reponse')->getId(), $response[$orderId]->getId());
		$this->assertSame($this->expected('reponse')->getOrderType(), $response[$orderId]->getOrderType());
		$this->assertSame($this->expected('reponse')->getTestType(), $response[$orderId]->getTestType());
		$this->assertSame($this->expected('reponse')->getModifiedTime(), $response[$orderId]->getModifiedTime());
		$this->assertSame($this->expected('reponse')->getCustomerOrderId(), $response[$orderId]->getCustomerOrderId());
		$this->assertSame($this->expected('reponse')->getCustomerId(), $response[$orderId]->getCustomerId());
		$this->assertSame($this->expected('reponse')->getOrderDate(), $response[$orderId]->getOrderDate());
		$this->assertSame($this->expected('reponse')->getDashboardRepId(), $response[$orderId]->getDashboardRepId());
		$this->assertSame($this->expected('reponse')->getStatus(), $response[$orderId]->getStatus());
		$this->assertSame((float) $this->expected('reponse')->getOrderTotal(), $response[$orderId]->getOrderTotal());
		$this->assertSame($this->expected('reponse')->getSource(), $response[$orderId]->getSource());
	}
}

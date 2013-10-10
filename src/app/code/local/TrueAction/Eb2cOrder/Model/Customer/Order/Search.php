<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cOrder_Model_Cutomer_Order_Search extends Mage_Core_Model_Abstract
{
	const SERVICE = 'customers';
	const OPERATION = 'orders/get';
	/**
	 * Cutomer Order Search from eb2c.
	 *
	 * @param Mage_Sales_Model_Order $order, the order to do Void paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function requestOrderSummary($order)
	{
		$responseMessage = '';
		// build request
		$requestDoc = $this->buildOrderSummaryRequest($order);
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);

		try{
			// make request to eb2c for Customer OrderSummary
			$responseMessage = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2ccore')->getApiUri(self::SERVICE, self::OPERATION))
				->setXsd(Mage::helper('eb2corder')->getConfigModel()->xsdFileSearch)
				->request($requestDoc);

		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending Cutomer Order Search request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}

		return $responseMessage;
	}

	/**
	 * Build OrderSummary request.
	 *
	 * @param Mage_Sales_Model_Order $order, the order to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildOrderSummaryRequest($order)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderSummaryRequest = $domDocument->addElement('OrderSummaryRequest', null, Mage::helper('eb2corder')->getXmlNs())->firstChild;
		$orderSummaryRequest->addChild('OrderSearch', null)
			->addChild('CustomerId', (string) $order->getCustomerId());

		return $domDocument;
	}

	/**
	 * Parse customer Order Summary reply xml.
	 *
	 * @param string $orderSummaryReply the xml response from eb2c
	 *
	 * @return array, a collection of Varien_Object with response data
	 */
	public function parseResponse($orderSummaryReply)
	{
		$resultData = array();
		if (trim($orderSummaryReply) !== '') {
			$coreHlpr = Mage::helper('eb2ccore');
			$doc = $coreHlpr->getNewDomDocument();
			$doc->loadXML($orderSummaryReply);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('a', Mage::helper('eb2corder')->getXmlNs());
			$searchResults = $xpath->query('//a:OrderSummary');
			foreach($searchResults as $result) {
				$resultData = new Varien_Object(array(
					'id' => $result->getAttribute('id'),
					'order_type' => $result->getAttribute('orderType'),
					'test_type' => $result->getAttribute('testType'),
					'modified_time' => $result->getAttribute('modifiedTime'),
					'customer_order_id' => (string) $coreHlpr->extractNodeVal($xpath->query('CustomerOrderId/text()', $result),
					'customer_id' => (string) $coreHlpr->extractNodeVal($xpath->query('CustomerId/text()', $result),
					'order_date' => (string) $coreHlpr->extractNodeVal($xpath->query('OrderDate/text()', $result),
					'dashboard_rep_id' => (string) $coreHlpr->extractNodeVal($xpath->query('DashboardRepId/text()', $result),
					'status' => (string) $coreHlpr->extractNodeVal($xpath->query('Status/text()', $result),
					'order_total' => (float) $coreHlpr->extractNodeVal($xpath->query('OrderTotal/text()', $result),
					'source' => (string) $coreHlpr->extractNodeVal($xpath->query('Source/text()', $result),
				));
			}
		}

		return $resultData;
	}
}

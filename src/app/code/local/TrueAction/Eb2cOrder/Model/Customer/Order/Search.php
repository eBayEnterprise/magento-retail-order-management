<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cOrder_Model_Customer_Order_Search
{
	/**
	 * Cutomer Order Search from eb2c.
	 *
	 * @param int $customerId, the magento customer id to query eb2c with
	 *
	 * @return string the eb2c response to the request.
	 */
	public function requestOrderSummary($customerId)
	{
		$responseMessage = '';
		// build request
		$requestDoc = $this->buildOrderSummaryRequest($customerId);
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);
		$cfg = Mage::helper('eb2corder')->getConfig();

		try{
			// make request to eb2c for Customer OrderSummary
			$responseMessage = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2ccore')->getApiUri($cfg->apiSearchService, $cfg->apiSearchOperation))
				->setXsd($cfg->xsdFileSearch)
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
	 * @param int $customerId, the customer id to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildOrderSummaryRequest($customerId)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderSummaryRequest = $domDocument->addElement('OrderSummaryRequest', null, Mage::helper('eb2corder')->getConfig()->apiXmlNs)->firstChild;
		$orderSearch = $orderSummaryRequest->createChild('OrderSearch', null, array());
		$orderSearch->createChild('CustomerId', (string) $customerId);
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
			$xpath->registerNamespace('a', Mage::helper('eb2corder')->getConfig()->apiXmlNs);
			$searchResults = $xpath->query('//a:OrderSummary');
			foreach($searchResults as $result) {
				$orderId = $coreHlpr->extractNodeVal($xpath->query('a:CustomerOrderId/text()', $result));
				$resultData[$orderId] = new Varien_Object(array(
					'id' => $result->getAttribute('id'),
					'order_type' => $result->getAttribute('orderType'),
					'test_type' => $result->getAttribute('testType'),
					'modified_time' => $result->getAttribute('modifiedTime'),
					'customer_order_id' => $orderId,
					'customer_id' => (string) $coreHlpr->extractNodeVal($xpath->query('a:CustomerId/text()', $result)),
					'order_date' => (string) $coreHlpr->extractNodeVal($xpath->query('a:OrderDate/text()', $result)),
					'dashboard_rep_id' => (string) $coreHlpr->extractNodeVal($xpath->query('a:DashboardRepId/text()', $result)),
					'status' => (string) $coreHlpr->extractNodeVal($xpath->query('a:Status/text()', $result)),
					'order_total' => (float) $coreHlpr->extractNodeVal($xpath->query('a:OrderTotal/text()', $result)),
					'source' => (string) $coreHlpr->extractNodeVal($xpath->query('a:Source/text()', $result)),
				));
			}
		}

		return $resultData;
	}
}

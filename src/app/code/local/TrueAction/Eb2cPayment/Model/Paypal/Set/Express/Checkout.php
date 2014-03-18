<?php
class TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout extends TrueAction_Eb2cPayment_Model_Paypal_Abstract
{
	// A mapping to something in the helper. Pretty contrived.
	const URI_KEY = 'get_paypal_set_express_checkout';
	const XSD_FILE = 'xsd_file_paypal_set_express';
	const STORED_FIELD = 'token';
	/**
	 * Build PaypalSetExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		Mage::log(array_keys($totals['grand_total']->getData()));
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalSetExpressCheckoutRequest = $domDocument->addElement('PayPalSetExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalSetExpressCheckoutRequest->createChild('OrderId', (string) $quote->getEntityId());
		$payPalSetExpressCheckoutRequest->createChild('ReturnUrl', (string) Mage::getUrl('*/*/return'));
		$payPalSetExpressCheckoutRequest->createChild('CancelUrl', (string) Mage::getUrl('*/*/cancel'));
		$payPalSetExpressCheckoutRequest->createChild('LocaleCode', (string) Mage::app()->getLocale()->getDefaultLocale());
		$payPalSetExpressCheckoutRequest->createChild(
			'Amount',
			sprintf('%.02f', (isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		// creating lineItems element
		$lineItems = $payPalSetExpressCheckoutRequest->createChild('LineItems', null);
		// add LineItemsTotal
		$lineItems->createChild(
			'LineItemsTotal',
			sprintf('%.02f', (isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		// add ShippingTotal
		$lineItems->createChild(
			'ShippingTotal',
			sprintf('%.02f', (isset($totals['shipping']) ? $totals['shipping']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		// add TaxTotal
		$lineItems->createChild(
			'TaxTotal',
			sprintf('%.02f', (isset($totals['tax']) ? $totals['tax']->getValue() : 0)),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		if ($quote) {
			foreach($quote->getAllAddresses() as $addresses){
				if ($addresses){
					foreach ($addresses->getAllItems() as $item) {
						// creating lineItem element
						$lineItem = $lineItems->createChild('LineItem', null);
						// add Name
						$lineItem->createChild('Name', (string) $item->getName());
						// add Quantity
						$lineItem->createChild('Quantity', (string) $item->getQty());
						// add UnitAmount
						$lineItem->createChild('UnitAmount', sprintf('%.02f', $item->getPrice()), array('currencyCode' => $quote->getQuoteCurrencyCode()));
					}
				}
			}
		}
		return $domDocument;
	}
	/**
	 * Parse PayPal SetExpress reply xml.
	 *
	 * @param string $payPalSetExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 */
	public function parseResponse($payPalSetExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalSetExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalSetExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$nodeToken = $checkoutXpath->query('//a:Token');
			$checkoutObject->setData(array(
				'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'token' => ($nodeToken->length)? (string) $nodeToken->item(0)->nodeValue : null,
			));
		}
		return $checkoutObject;
	}
}

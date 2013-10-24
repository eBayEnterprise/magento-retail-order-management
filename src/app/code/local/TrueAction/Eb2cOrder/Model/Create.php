<?php
/**
 * Generates an OrderCreate
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. Not necessarily that I must *listen* for all of these, but that I should
 * 	be aware of any side effects of these.
 *
 * adminhtml_sales_order_create_process_data_before
 * adminhtml_sales_order_create_process_data
 * sales_convert_quote_to_order
 * sales_convert_quote_item_to_order_item
 * sales_order_place_before
 * checkout_type_onepage_save_order_after
 * checkout_type_multishipping_create_orders_single
 *
 */
class TrueAction_Eb2cOrder_Model_Create extends Mage_Core_Model_Abstract
{
	const	GENDER_MALE = 1;

	private $_o = null;					// Magento Order Object
	private $_xmlRequest = null;		// Human readable XML
	private $_xmlResponse = null;		// Human readable XML
	private $_domRequest = null;		// DOM Object
	private $_domResponse = null;		// DOM Object
	private $_orderItemRef = array();	// Saves an array of item_id's for use in shipping node

	private $_helper;
	private $_config;

	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
	}

	/**
	 * When we have failed to create order, dispatch event
	 * @todo Originally we were going to try some number of times to transmit. Is this still the case?
	 */
	private function _finallyFailed()
	{
		Mage::dispatchEvent('eb2c_order_create_fail', array('order' => $this->_o));
		return;
	}

	/**
	 * The event observer version of transmit order
	 */
	public function observerCreate($event)
	{
		$this->buildRequest($event->getEvent()->getOrder());
		try {
			$this->sendRequest();
		}
		catch( Exception $e ) {
			Mage::logException($e);	// Fail quietly!
		}
		return;
	}

	/**
	 * Transmit Order
	 *
	 */
	public function sendRequest()
	{
		$consts = $this->_helper->getConstHelper();
		$uri = $this->_helper->getOperationUri($consts::CREATE_OPERATION);

		try {
			$response = Mage::getModel('eb2ccore/api')
				->addData(
					array(
						'uri'     => $uri,
						'timeout' => $this->_config->serviceOrderTimeout,
						'xsd'     => $this->_config->xsdFileCreate,
					)
				)
				->request($this->_domRequest);

			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
		}
		catch(Exception $e) {
			Mage::throwException('Send Web Service Request Failed: ' . $e->getMessage());
		}

		$rc = strcmp($status, 'Success') ? false : true;
		if( $rc === true ) {
			Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
		}
		return $rc;
	}

	/**
	 * Build DOM for a complete order
	 *
	 * @todo Get tax details for TaxHeader
	 * @todo Get locale from correct fields
	 * @todo Get 'OrderSource' and 'OrderSource type' from correct fields
	 * @param $orderObject a Mage_Sales_Model_Order
	 */
	public function buildRequest($orderObject)
	{
		$this->_o = $orderObject;

		$consts = $this->_helper->getConstHelper();

		$this->_domRequest = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_domRequest->formatOutput = true;
		$orderCreateRequest = $this->_domRequest->addElement($consts::CREATE_DOM_ROOT_NODE_NAME, null, $this->_config->apiXmlNs)->firstChild;
		$orderCreateRequest->setAttribute('orderType', $consts::ORDER_TYPE);
		$orderCreateRequest->setAttribute('requestId', $this->_getRequestId());

		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService', $consts::LEVEL_OF_SERVICE);
		$order->setAttribute('customerOrderId', $this->_o->getIncrementId());

		$this->_buildCustomer( $order->createChild('Customer') );

		$order->createChild('CreateTime', str_replace(' ', 'T', $this->_o->getCreatedAt()));

		$webLineId = 1;
		$orderItems = $order->createChild('OrderItems');
		foreach( $this->_o->getAllItems() as $item ) {
			$this->_buildOrderItem($orderItems->createChild('OrderItem'), $item, $webLineId++);
		}

		// Magento only ever has 1 ship-to per order, so we're building directly into a singular ShipGroup
		$this->_buildShipping($order->createChild('Shipping')->createChild('ShipGroups')->createChild('ShipGroup'));

		$this->_buildPayment($order->createChild('Payment'));

		$order->createChild('Currency', $this->_o->getOrderCurrencyCode());

		$taxHeader = $order->createChild('TaxHeader')->createChild('Error', 'false');

		$order->createChild('Locale', 'en_US');

		$orderSource = $order->CreateChild('OrderSource');
		$orderSource->setAttribute('type', '');

		$order->createChild('OrderHistoryUrl',
		Mage::app()->getStore( $this->_o->getStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $consts::ORDER_HISTORY_PATH . $this->_o->getEntityId());

		$order->createChild('OrderTotal', sprintf('%.02f', $this->_o->getGrandTotal()));

		$this->_buildContext($orderCreateRequest->createChild('Context'));

		$this->_xmlRequest = $this->_domRequest->saveXML();
		return $this;
	}

	/**
	 * Build customer information node
	 *
	 * @param DomElement customer	where to place customer info
	 */
	private function _buildCustomer(DomElement $customer)
	{
		$customer->setAttribute('customerId', $this->_o->getCustomerId());

		$name = $customer->createChild('Name');
		$name->createChild('Honorific', $this->_o->getCustomerPrefix() );
		$name->createChild('LastName', trim($this->_o->getCustomerLastname() . ' ' . $this->_o->getCustomerSuffix()) );
		$name->createChild('MiddleName', $this->_o->getCustomerMiddlename());
		$name->createChild('FirstName', $this->_o->getCustomerFirstname());

		if( $this->_o->getCustomerGender() ) {
			// Previously tried to pull out the gender text, but that's probably worse, since one could change
			// 	'Male' to 'Boys' (or 'Woman', for that matter) and an invalid or flat-out wrong value would be sent to GSI.
			//	Let's just check the gender value/ option id. If it's 1, male, otherwise, female.
			$genderToSend = ($this->_o->getCustomerGender() == self::GENDER_MALE) ?  'M' : 'F';
			$customer->createChild('Gender', $genderToSend);
		}

		if( $this->_o->getCustomerDob() ) {
			$customer->createChild('DateOfBirth', $this->_o->getCustomerDob());
		}
		$customer->createChild('EmailAddress', $this->_o->getCustomerEmail());
		$customer->createChild('CustomerTaxId', $this->_o->getCustomerTaxvat());
		return;
	}

	/**
	 * Builds a single Order Item node inside the Order Items array
	 *
	 * @todo support > 1 tax
	 * @todo get taxType, taxability, Jurisdiction, Situs, EffectiveRate, TaxClass from correct fields
	 * @param DomElement orderItem
	 * @param Mage_Sales_Model_Order_Item item
	 * @param integer webLineId	identifier to indicate the line item's sequence within the order
	 */
	private function _buildOrderItem(DomElement $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
	{
		$itemId = 'item_' . $item->getId();
		$this->_orderItemRef[] = $itemId;
		$orderItem->setAttribute('id', $itemId );
		$orderItem->setAttribute('webLineId', $webLineId++);
		$orderItem->createChild('ItemId', $item->getSku());
		$orderItem->createChild('Quantity', $item->getQtyOrdered());
		$orderItem->createChild('Description')->createChild('Description', $item->getName());

		$pricing = $orderItem->createChild('Pricing');
		$merchandise = $pricing->createChild('Merchandise');
		$merchandise->createChild('Amount', sprintf('%.02f', $item->getQtyOrdered() * $item->getPrice()));

		$discount = $merchandise
			->createChild('PromotionalDiscounts')
			->createChild('Discount');
		$discount->createChild('Id', 'CHANNEL_IDENTIFIER');	// Spec says this *may* be required, schema validation says it *is* required
		$discount->createChild('Amount', sprintf('%.02f', $item->getDiscountAmount())); // Magento has only 1 discount per line item

		$shippingMethod = $orderItem->createChild('ShippingMethod', '???');
		$estDeliveryDate = $orderItem->createChild('EstimatedDeliveryDate');
		$estDeliveryDate->createChild('MessageType', $item->getEb2cMessageType());

		$deliveryWindow = $estDeliveryDate->createChild('DeliveryWindow');
		$deliveryWindow->createChild('From', $item->getEb2cDeliveryWindowFrom());
		$deliveryWindow->createChild('To', $item->getEb2cDeliveryWindowTo());

		$shippingWindow = $estDeliveryDate->createChild('ShippingWindow');
		$shippingWindow->createChild('From', $item->getEb2cShippingWindowFrom());
		$shippingWindow->createChild('To', $item->getEb2cShippingWindowTo());

		$orderItem->createChild('ReservationId', $item->getEb2cReservationId());

		// Tax on the Merchandise:
		$taxData = $merchandise->createChild('TaxData');
		$taxData->createChild('TaxClass', '????');
		$taxes = $taxData->createChild('Taxes');
		$tax = $taxes->createChild('Tax');
		$tax->setAttribute('taxType', 'SELLER_USE');
		$tax->setAttribute('taxability', 'TAXABLE');
		$tax->createChild('Situs', 0);
		$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$jurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$tax->createChild('EffectiveRate', $item->getTaxPercent());
		$tax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$tax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		$merchandise->createChild('UnitPrice', sprintf('%.02f', $item->getPrice()));
		// End Merchandise

		// Shipping on the orderItem:
		$shipping = $orderItem->createChild('Shipping');
		$shipping->createChild('Amount');

		$taxData = $shipping->createChild('TaxData');
		$taxes = $taxData->createChild('Taxes');
		$tax = $taxes->createChild('Tax');
		$tax->createChild('Situs', 0);
		$tax->createChild('EffectiveRate', 0);
		$tax->createChild('CalculatedTax', sprintf('%.02f', 0));
		// End Shipping

		// Duty on the orderItem:
		$duty = $orderItem->createChild('Duty');
		$duty->createChild('Amount');
		$taxData = $duty->createChild('TaxData');
		$taxData->createChild('TaxClass', 'DUTY'); // Is this a hardcoded value?
		$taxes = $taxData->createChild('Taxes');
		$tax = $taxes->createChild('Tax');
		$tax->setAttribute('taxType', 'SELLER_USE');
		$tax->setAttribute('taxability', 'TAXABLE');
		$tax->createChild('Situs', 0);
		$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$jurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$tax->createChild('EffectiveRate', $item->getTaxPercent());
		$tax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$tax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		// End Duty
		return;
	}

	/**
	 * Builds the Shipping Node for order
	 *
	 * @param DomElement shipGroup Node to contain shipping and billing info
	 *
	 */
	private function _buildShipping(DomElement $shipGroup)
	{
		$consts = $this->_helper->getConstHelper();
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType', '');
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', $consts::SHIPGROUP_DESTINATION_ID);
		$orderItems = $shipGroup->createChild('OrderItems');
		foreach( $this->_orderItemRef as $orderItemRef ) {
			$shipItem = $orderItems->createChild('Item');
			$shipItem->setAttribute('ref', $orderItemRef);
		}
		$destinations = $shipGroup->createChild('Destinations');

		// Ship-To
		$sa = $this->_o->getShippingAddress();
		$dest = $destinations->createChild('MailingAddress');
		$dest->setAttribute('id', $consts::SHIPGROUP_DESTINATION_ID);
		$this->_buildPersonName($dest->createChild('PersonName'), $sa);
		$this->_buildAddress($dest->createChild('Address'), $sa);
		$dest->createChild('Phone', $sa->getTelephone());

		// Bill-To
		$ba = $this->_o->getBillingAddress();
		$billing = $destinations->createChild('MailingAddress');
		$billing->setAttribute('id', $consts::SHIPGROUP_BILLING_ID);
		$this->_buildPersonName($billing->createChild('PersonName'), $ba);
		$this->_buildAddress($billing->createChild('Address'), $ba);
		$billing->createChild('Phone', $ba->getTelephone());
		return;
	}

	/**
	 * Creates PersonName element details from an address
	 *
	 * @param DomElement personName
	 * @param Mage_Sales_Model_Order_Address address
 	 */
	private function _buildPersonName(DomElement $person, Mage_Sales_Model_Order_Address $address)
	{
		$person->createChild('Honorific', $address->getPrefix());
		$person->createChild('LastName', trim($address->getLastname() . ' ' . $address->getSuffix()));
		$person->createChild('MiddleName', $address->getMiddlename());
		$person->createChild('FirstName', $address->getFirstname());
		return;
	}

	/**
	 * Creates MailingAddress/Address element details from address
	 *
	 * @param DomElement addressElement
	 * @param Mage_Sales_Order_Address address
	 */
	private function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
	{
		$line = 1;
		foreach($address->getStreet() as $streetLine) {
			$addressElement->createChild('Line' . $line, $streetLine);
			$line++;
		}
		$addressElement->createChild('City', $address->getCity());
		$addressElement->createChild('MainDivision', $address->getRegion());
		$addressElement->createChild('CountryCode', $address->getCountryId());
		$addressElement->createChild('PostalCode', $address->getPostalCode());
		return;
	}

	/**
	 * Populate the Payment Element of the request
	 *
	 * @param DomElement payment
	 */
	private function _buildPayment($payment)
	{
		$consts = $this->_helper->getConstHelper();
		$payment->createChild('BillingAddress')->setAttribute('ref', $consts::SHIPGROUP_BILLING_ID);
		$this->_buildPayments($payment->createChild('Payments'));
		return;
	}

	/**
	 * Creates the Tender entries within the Payments Element
	 *
	 * @param DomElement payments node into which payment info is placed
	 */
	private function _buildPayments(DomElement $payments)
	{
		if( $this->_config->eb2cPaymentsEnabled ) {
			foreach($this->_o->getAllPayments() as $payment) {
				$method = ucfirst($payment->getMethod());
				$thisPayment = $payments->createChild($method);

				$paymentContext = $thisPayment->createChild('PaymentContext');
				$paymentContext->createChild('PaymentSessionId', '???');
				$paymentContext->createChild('TenderType', '???');
				$paymentContext->createChild('PaymentAccountUniqueId', '???')->setAttribute('isToken', 'true');

				$thisPayment->createChild('PaymentRequestId', '???');
				$thisPayment->createChild('CreateTimeStamp', '???');

				$auth = $thisPayment->createChild('Authorization');
				$auth->createChild('ResponseCode', $payment->getCcStatus());
				$auth->createChild('BankAuthorizationCode', $payment->getCcApproval());
				$auth->createChild('CVV2ResponseCode', $payment->getCcCidStatus());
				$auth->createChild('AVSResponseCode', $payment->getCcAvsStatus());
				$auth->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));

				$thisPayment->createChild('ExpirationDate', $payment->getCcExpYear() . '-' . $payment->getCcExpMonth());
				$thisPayment->createChild('StartDate', '???');
				$thisPayment->createChild('IssueNumber', '???');
			}
		}
		else {
			$thisPayment = $payments->createChild('PrepaidCreditCard');
			$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));

		}
		return;
	}

	/**
	 * Populates the Context element
	 *
	 * @param DomElement context
	 */
	private function _buildContext(DomElement $context)
	{
		$this->_buildBrowserData($context->createChild('BrowserData'));
		return;
	}

	/**
	 * Populates the Context/BrowserData element
	 *
	 * @param DomElement context
	 */
	private function _buildBrowserData(DomElement $browserData)
	{
		$children = array(
			'HostName' => $this->_o->getEb2cHostName(),
			'IPAddress' => $this->_o->getEb2cIpAddress(),
			'SessionId' => $this->_o->getEb2cSessionId(),
			'UserAgent' => $this->_o->getEb2cUserAgent(),
			'JavascriptData' => $this->_o->getEb2cJavascriptData(),
			'Referrer' => $this->_o->getEb2cReferer(),
			'HTTPAcceptData' => 'HttpAcceptData',
		);

		foreach( $children as $key => $value ) {
			$browserData->createChild($key, $value);
		}

		return;
	}

	/**
	 * Get globally unique request identifier
	 */
	private function _getRequestId()
	{
		return uniqid('OCR-');
	}
}

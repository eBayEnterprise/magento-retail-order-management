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

	/**
	 * @var Mage_Sales_Model_Order, Magento Order Object
	 */
	private $_o = null;

	/**
	 * @var string, Human readable XML
	 */
	private $_xmlRequest = null;

	/**
	 * @var string, Human readable XML
	 */
	private $_xmlResponse = null;

	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	private $_domRequest = null;

	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	private $_domResponse = null;

	/**
	 * @var array, Saves an array of item_id's for use in shipping node
	 */
	private $_orderItemRef = array();

	/**
	 * @var TrueAction_Eb2cOrder_Helper_Data, helper Object
	 */
	private $_helper;

	/**
	 * @var TrueAction_Eb2cCore_Model_Config_Registry, config Object
	 */
	private $_config;

	/**
	 * @var array, hold magento payment map to eb2c
	 */
	private $_ebcPaymentMethodMap = array();

	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
		$this->_ebcPaymentMethodMap = array(
			'Pbridge_eb2cpayment_cc' => 'CreditCard',
			'Paypal_express' => 'PrepaidCreditCard',
			'PrepaidCashOnDelivery' => 'PrepaidCashOnDelivery',
		);
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
	 * @param Varien_Event_Observer $event, the observer event
	 * @return void
	 */
	public function observerCreate($event)
	{
		$this->buildRequest($event->getEvent()->getOrder())
			->sendRequest();
	}

	/**
	 * Transmit Order
	 * @return self
	 */
	public function sendRequest()
	{
		$consts = $this->_helper->getConstHelper();
		$uri = $this->_helper->getOperationUri($consts::CREATE_OPERATION);

		if( $this->_config->developerMode ) {
			$uri = $this->_config->developerCreateUri;
		}
		$response = '';
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $this->_xmlRequest), Zend_Log::DEBUG);
		try {
			$response = Mage::getModel('eb2ccore/api')
				->addData(
					array(
						'uri' => $uri,
						'timeout' => $this->_config->serviceOrderTimeout,
						'xsd' => $this->_config->xsdFileCreate,
					)
				)
				->request($this->_domRequest);
		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending order create request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}

		if (trim($response) !== '') {
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;

			if( strcmp($status, 'Success') === true ) {
				Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
			}
		}

		return $this;
	}

	/**
	 * Build DOM for a complete order
	 *
	 * @todo Get tax details for TaxHeader
	 * @todo Get locale from correct fields
	 * @todo Get 'OrderSource' and 'OrderSource type' from correct fields
	 * @param $orderObject a Mage_Sales_Model_Order
	 * @return self
	 */
	public function buildRequest(Mage_Sales_Model_Order $orderObject)
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
		$shipping = $order->createChild('Shipping');

		// building shipGroup node
		$this->_buildShipGroup($shipping->createChild('ShipGroups')->createChild('ShipGroup'));

		$this->_buildShipping($shipping);

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
	 * @return void
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
	}

	/**
	 * Builds a single Order Item node inside the Order Items array
	 *
	 * @todo support > 1 tax
	 * @todo get taxType, taxability, Jurisdiction, Situs, EffectiveRate, TaxClass from correct fields
	 * @param DomElement orderItem
	 * @param Mage_Sales_Model_Order_Item item
	 * @param integer webLineId	identifier to indicate the line item's sequence within the order
	 * @return void
	 */
	private function _buildOrderItem(DomElement $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
	{
		$order = $item->getOrder();
		$quoteId = $order->getQuoteId();
		$itemId = 'item_' . $item->getId();
		$reservationId = (trim($item->getEb2cReservationId()) !== '')? $item->getEb2cReservationId() : Mage::helper('eb2cinventory')->getRequestId($quoteId);

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

		$shippingMethod = $orderItem->createChild('ShippingMethod', $order->getShippingMethod());

		Mage::log(sprintf('[ %s ]: Item Data: %s', __METHOD__, $reservationId), Zend_Log::DEBUG);

		if (trim($item->getEb2cDeliveryWindowFrom()) !== '' && trim($item->getEb2cShippingWindowFrom()) !== '') {
			$estDeliveryDate = $orderItem->createChild('EstimatedDeliveryDate');
			$deliveryWindow = $estDeliveryDate->createChild('DeliveryWindow');
			$deliveryWindow->createChild('From', $item->getEb2cDeliveryWindowFrom());
			$deliveryWindow->createChild('To', $item->getEb2cDeliveryWindowTo());

			$shippingWindow = $estDeliveryDate->createChild('ShippingWindow');
			$shippingWindow->createChild('From', $item->getEb2cShippingWindowFrom());
			$shippingWindow->createChild('To', $item->getEb2cShippingWindowTo());
		}

		$orderItem->createChild('ReservationId', $reservationId);

		// Tax on the Merchandise:
		$merchandiseTaxData = $merchandise->createChild('TaxData');
		$merchandiseTaxData->createChild('TaxClass', '????');
		$merchandiseTaxes = $merchandiseTaxData->createChild('Taxes');
		$merchandiseTax = $merchandiseTaxes->createChild('Tax');
		$merchandiseTax->setAttribute('taxType', 'SELLER_USE');
		$merchandiseTax->setAttribute('taxability', 'TAXABLE');
		$merchandiseTax->createChild('Situs', 0);
		$merchandiseJurisdiction = $merchandiseTax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$merchandiseJurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$merchandiseJurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$merchandiseTax->createChild('EffectiveRate', $item->getTaxPercent());
		$merchandiseTax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$merchandiseTax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		$merchandise->createChild('UnitPrice', sprintf('%.02f', $item->getPrice()));
		// End Merchandise

		// Shipping on the orderItem:
		$shipping = $pricing->createChild('Shipping');
		$shipping->createChild('Amount', (float) $order->getBaseShippingAmount());

		$shippingTaxData = $shipping->createChild('TaxData');
		$shippingTaxes = $shippingTaxData->createChild('Taxes');
		$shippingTax = $shippingTaxes->createChild('Tax');
		$shippingTax->setAttribute('taxType', 'SELLER_USE');
		$shippingTax->setAttribute('taxability', 'TAXABLE');
		$shippingTax->createChild('Situs', 0);
		$shippingTax->createChild('EffectiveRate', 0);
		$shippingTax->createChild('CalculatedTax', sprintf('%.02f', 0));
		// End Shipping

		// Duty on the orderItem:
		$duty = $pricing->createChild('Duty');
		$duty->createChild('Amount', (float) $order->getBaseTaxAmount());
		$dutyTaxData = $duty->createChild('TaxData');
		$dutyTaxData->createChild('TaxClass', 'DUTY'); // Is this a hardcoded value?
		$dutyTaxes = $dutyTaxData->createChild('Taxes');
		$dutyTax = $dutyTaxes->createChild('Tax');
		$dutyTax->setAttribute('taxType', 'SELLER_USE');
		$dutyTax->setAttribute('taxability', 'TAXABLE');
		$dutyTax->createChild('Situs', 0);
		$dutyJurisdiction = $dutyTax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$dutyJurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$dutyJurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$dutyTax->createChild('EffectiveRate', $item->getTaxPercent());
		$dutyTax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$dutyTax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		// End Duty
	}

	/**
	 * Builds the ShipGroup Node for order
	 * @param DomElement shipGroup Node
	 * @return void
	 *
	 */
	private function _buildShipGroup(DomElement $shipGroup)
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
	}

	/**
	 * Builds the Shipping Node for order
	 * @param DomElement shipping Node to contain shipping and billing info
	 * @return void
	 */
	private function _buildShipping(DomElement $shipping)
	{
		$consts = $this->_helper->getConstHelper();
		$destinations = $shipping->createChild('Destinations');
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
	}

	/**
	 * Creates PersonName element details from an address
	 *
	 * @param DomElement personName
	 * @param Mage_Sales_Model_Order_Address address
	 * @return void
 	 */
	private function _buildPersonName(DomElement $person, Mage_Sales_Model_Order_Address $address)
	{
		$person->createChild('Honorific', $address->getPrefix());
		$person->createChild('LastName', trim($address->getLastname() . ' ' . $address->getSuffix()));
		$person->createChild('MiddleName', $address->getMiddlename());
		$person->createChild('FirstName', $address->getFirstname());
	}

	/**
	 * Creates MailingAddress/Address element details from address
	 *
	 * @param DomElement addressElement
	 * @param Mage_Sales_Order_Address address
	 * @return void
	 */
	private function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
	{
		$line = 1;
		foreach ($address->getStreet() as $streetLine) {
			$addressElement->createChild('Line' . $line, $streetLine);
			$line++;
		}
		$addressElement->createChild('City', $address->getCity());
		$addressElement->createChild('MainDivision', $address->getRegion());
		$addressElement->createChild('CountryCode', $address->getCountryId());
		$addressElement->createChild('PostalCode', $address->getPostcode());
	}

	/**
	 * Populate the Payment Element of the request
	 *
	 * @param DomElement payment
	 * @return void
	 */
	private function _buildPayment($payment)
	{
		$consts = $this->_helper->getConstHelper();
		$payment->createChild('BillingAddress')->setAttribute('ref', $consts::SHIPGROUP_BILLING_ID);
		$this->_buildPayments($payment->createChild('Payments'));
	}

	/**
	 * Creates the Tender entries within the Payments Element
	 *
	 * @param DomElement payments node into which payment info is placed
	 * @return void
	 */
	private function _buildPayments(DomElement $payments)
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			foreach($this->_o->getAllPayments() as $payment) {
				$thisPayment = $payments->createChild($this->_ebcPaymentMethodMap[ucfirst($payment->getMethod())]);
				$paymentContext = $thisPayment->createChild('PaymentContext');
				$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
				$paymentContext->createChild('TenderType', $payment->getMethod());
				$paymentContext->createChild('PaymentAccountUniqueId', $payment->getId())->setAttribute('isToken', 'true');
				$thisPayment->createChild('PaymentRequestId', '???');
				$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
				$auth = $thisPayment->createChild('Authorization');
				$auth->createChild('ResponseCode', $payment->getCcStatus());
				$auth->createChild('BankAuthorizationCode', $payment->getCcApproval());
				$auth->createChild('CVV2ResponseCode', $payment->getCcCidStatus());
				$auth->createChild('AVSResponseCode', $payment->getCcAvsStatus());
				$auth->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));
			}
		} else {
			$thisPayment = $payments->createChild('PrepaidCreditCard');
			$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));

		}
	}

	/**
	 * Populates the Context element
	 *
	 * @param DomElement context
	 * @return void
	 */
	private function _buildContext(DomElement $context)
	{
		$this->_buildBrowserData($context->createChild('BrowserData'));
	}

	/**
	 * Populates the Context/BrowserData element
	 *
	 * @param DomElement context
	 * @return void
	 */
	private function _buildBrowserData(DomElement $browserData)
	{
		$browserData->addChild('HostName', Mage::helper('core/http')->getHttpHost(true))
			->addChild('IPAddress', Mage::helper('core/http')->getServerAddr())
			->addChild('SessionId', Mage::getSingleton('core/session')->getSessionId())
			->addChild('UserAgent', Mage::helper('core/http')->getHttpUserAgent(true))
			->addChild('JavascriptData', $this->_o->getEb2cJavascriptData())
			->addChild('Referrer', Mage::helper('core/http')->getHttpReferer(true));

		$httpAcceptData = $browserData->createChild('HTTPAcceptData');
		$httpAcceptData->addChild('ContentTypes', $_SERVER['HTTP_ACCEPT'])
			->addChild('Encoding', $_SERVER['HTTP_ACCEPT_ENCODING'])
			->addChild('Language', Mage::helper('core/http')->getHttpAcceptLanguage(true))
			->addChild('CharSet', Mage::helper('core/http')->getHttpAcceptCharset(true));
	}

	/**
	 * Get globally unique request identifier
	 * @return string
	 */
	private function _getRequestId()
	{
		return uniqid('OCR-');
	}
}

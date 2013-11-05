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
	 * @see https://trueaction.atlassian.net/wiki/display/EBC/Magento+Payment+Method+Map+with+Eb2c
	 */
	private $_ebcPaymentMethodMap = array();

	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
		$this->_ebcPaymentMethodMap = array(
			'Pbridge_eb2cpayment_cc' => 'CreditCard',
			'Paypal_express' => 'PayPal',
			'PrepaidCreditCard' => 'PrepaidCreditCard', // Not use
			'StoredValueCard' => 'StoredValueCard', // Not use
			'Points' => 'Points', // Not use
			'PrepaidCashOnDelivery' => 'PrepaidCashOnDelivery', // Not use
			'Free' => 'StoredValueCard',
		);
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

		$response = '';
		if ($this->_domRequest instanceof DOMDocument) {
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
			} catch(Mage_Core_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] xsd validation occurred while sending order create request to eb2c: (%s).',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this->_processResponse($response);
	}

	/**
	 * processing the request response from eb2c
	 * @param string $response, the response string xml from eb2c request
	 * @return self
	 */
	private function _processResponse($response)
	{
		if (trim($response) !== '') {
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
			if(strtoupper(trim($status)) === 'SUCCESS') {
				$this->_o->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
				Mage::log(
					sprintf('[ %s ]: updating order (%s) state to processing after successfully creating order from eb2c', __METHOD__, $this->_o->getIncrementId()),
					Zend_Log::DEBUG
				);
				Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
			} else {
				$this->_o->setState(Mage_Sales_Model_Order::STATE_NEW)->save();
				Mage::log(
					sprintf('[ %s ]: updating order (%s) state to new after receiving fail response from eb2c', __METHOD__, $this->_o->getIncrementId()),
					Zend_Log::DEBUG
				);
			}
		} else {
			$this->_o->setState(Mage_Sales_Model_Order::STATE_NEW)->save();
			Mage::log(
				sprintf('[ %s ]: updating order (%s) state to new after order creation request failure', __METHOD__, $this->_o->getIncrementId()),
				Zend_Log::DEBUG
			);
		}

		return $this;
	}

	/**
	 * to be implented in the future, if we have gms extension that can provide the url source and type
	 * @return array, source data
	 */
	private function _getSourceData()
	{
		// return empty array since we don't know yet
		return array();
	}

	/**
	 * Build DOM for a complete order
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

		$orderSource = $this->_getSourceData();
		if (!empty($orderSource)) {
			$orderSource = $order->CreateChild('OrderSource', $orderSource['source']);
			$orderSource->setAttribute('type', $orderSource['type']);
		}

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
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		$customer->setAttribute('customerId', sprintf('%s%s', $cfg->clientCustomerIdPrefix, $this->_o->getCustomerId()));

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

		if ($item->getDiscountAmount() > 0) {
			$discount = $merchandise
				->createChild('PromotionalDiscounts')
				->createChild('Discount');
			$discount->createChild('Id', 'CHANNEL_IDENTIFIER');	// Spec says this *may* be required, schema validation says it *is* required
			$discount->createChild('Amount', sprintf('%.02f', $item->getDiscountAmount())); // Magento has only 1 discount per line item
		}

		$shippingMethod = $orderItem->createChild('ShippingMethod', $order->getShippingMethod());
		$orderItem->createChild('ReservationId', $reservationId);

		// Tax on the Merchandise:
		$merchandiseTaxData = $merchandise->createChild('TaxData');
		$merchandiseTaxes = $merchandiseTaxData->createChild('Taxes');
		$merchandiseTax = $merchandiseTaxes->createChild('Tax');
		$merchandiseTax->setAttribute('taxType', 'SELLER_USE');
		$merchandiseTax->setAttribute('taxability', 'TAXABLE');
		$merchandiseTax->createChild('Situs', 0);
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
		$this->_buildDuty($pricing, $order, $item, $quoteId);
		// End Duty
	}

	/**
	 * getting the quote item id by sku
	 * @param int $quoteId, the quote  id
	 * @param int $sku, the item sku
	 * @return int, the quote item id
	 */
	private function _getQuoteItemId($quoteId, $sku)
	{
		$quote = Mage::getModel('sales/quote')->load($quoteId);

		$sku = trim(strtoupper($sku));
		foreach ($quote->getAllItems() as $item) {
			if (trim(strtoupper($item->getSku())) === $sku) {
				return $item->getId();
			}
		}
		return 0;
	}

	/**
	 * get the tax reponse quote record filtering by the quote item id
	 * @param int $quoteId, the quote  id
	 * @param int $sku, the item sku
	 * @return TrueAction_Eb2cTax_Model_Response_Quote, the tax duty amount
	 */
	private function _getItemDuty($quoteId, $sku)
	{
		$responseQuote = Mage::getResourceModel('eb2ctax/response_quote_collection');
		$responseQuote->getSelect()
			->where(sprintf("main_table.quote_item_id = '%d'", $this->_getQuoteItemId($quoteId, $sku)));
		$responseQuote->load();
		return $responseQuote->getFirstItem();
	}

	/**
	 * Builds the Duty Node for order
	 * @param DomElement $pricing, the pricing node to attach duty node to
	 * @param Mage_Sales_Model_Order $order, the order object
	 * @param Mage_Sales_Model_Order_Item $item, the order item object
	 * @param int $quoteId, the quote id associated to the order
	 * @return void
	 */
	private function _buildDuty(DomElement $pricing, Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Item $item, $quoteId)
	{
		$dutyObj = $this->_getItemDuty($quoteId, $item->getSku());
		if ($dutyObj instanceof TrueAction_Eb2cTax_Model_Response_Quote && (float) $dutyObj->getCalculatedTax() > 0) {
			$duty = $pricing->createChild('Duty');
			$duty->createChild('Amount', $dutyObj->getCalculatedTax());
			$dutyTaxData = $duty->createChild('TaxData');
			$dutyTaxData->createChild('TaxClass', 'DUTY'); // Is this a hardcoded value?
			$dutyTaxes = $dutyTaxData->createChild('Taxes');
			$dutyTax = $dutyTaxes->createChild('Tax');
			$dutyTax->setAttribute('taxType', 'SELLER_USE');
			$dutyTax->setAttribute('taxability', 'TAXABLE');
			$dutyTax->createChild('Situs', 0);
			$dutyTax->createChild('EffectiveRate', $item->getTaxPercent());
			$dutyTax->createChild('TaxableAmount', sprintf('%.02f', (float) $dutyObj->getTaxableAmount()));
			$dutyTax->createChild('CalculatedTax', sprintf('%.02f', (float) $dutyObj->getCalculatedTax()));
		}
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
				$payMethodNode = $this->_ebcPaymentMethodMap[ucfirst($payment->getMethod())];

				if ($payMethodNode === 'CreditCard') {
					$thisPayment = $payments->createChild($payMethodNode);
					$paymentContext = $thisPayment->createChild('PaymentContext');
					$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
					$paymentContext->createChild('TenderType', $payment->getMethod());
					$paymentContext->createChild('PaymentAccountUniqueId', $payment->getId())->setAttribute('isToken', 'true');
					$thisPayment->createChild('PaymentRequestId', sprintf('payment%s', $payment->getId()));
					$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
					$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));

					$auth = $thisPayment->createChild('Authorization');

					$auth->createChild('AVSResponseCode', $payment->getAdditionalInformation('avs_response_code'));
					$auth->createChild('BankAuthorizationCode', $payment->getAdditionalInformation('bank_authorization_code'));
					$auth->createChild('CVV2ResponseCode', $payment->getAdditionalInformation('cvv2_response_code'));
					$auth->createChild('ResponseCode', $payment->getAdditionalInformation('response_code'));

					$auth->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));

				} elseif ($payMethodNode === 'PayPal') {
					$thisPayment = $payments->createChild($payMethodNode);
					$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
					$thisPayment->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));

					$paymentContext = $thisPayment->createChild('PaymentContext');
					$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
					$paymentContext->createChild('TenderType', $payment->getMethod());
					$paymentContext->createChild('PaymentAccountUniqueId', $payment->getId())->setAttribute('isToken', 'true');

					$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
					$thisPayment->createChild('PaymentRequestId', sprintf('payment%s', $payment->getId()));
					$auth = $thisPayment->createChild('Authorization');
					$auth->createChild('ResponseCode', $payment->getCcStatus());
				} elseif ($payMethodNode === 'StoredValueCard') {
					// the payment method is free and there is gift card for the order
					if ($this->_o->getGiftCardsAmount() > 0) {
						$thisPayment = $payments->createChild($payMethodNode);
						$paymentContext = $thisPayment->createChild('PaymentContext');
						$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
						$paymentContext->createChild('TenderType', $payment->getMethod());
						$paymentContext->createChild('PaymentAccountUniqueId', $payment->getId())->setAttribute('isToken', 'true');

						$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
						$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGiftCardsAmount()));
					} else {
						// there is no gift card for the order and the payment method is free
						$thisPayment = $payments->createChild('PrepaidCreditCard');
						$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
					}
				}
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

	/**
	 * This method will be trigger via cron, in which it will fetch all magento order
	 * with a state status of 'new' and then loop through them and then run code create eb2c orders
	 * same event to resend them to eb2c to create
	 * @return void
	 */
	public function retryOrderCreate()
	{
		// first get all order with state equal to 'new'
		$orders = $this->_getNewOrders();
		$currentDate = date('m/d/Y H:i:s', Mage::getModel('core/date')->timestamp(time()));
		Mage::log(
			sprintf('[ %s ]: Begin order retry now: %s. Found %s new order to be retried',
				__METHOD__, $currentDate, $orders->count()
			),
			Zend_Log::DEBUG
		);

		foreach ($orders as $order) {
			// running same code to send request create eb2c orders
			$this->buildRequest($order)
				->sendRequest();
		}

		$newDate = date('m/d/Y H:i:s', Mage::getModel('core/date')->timestamp(time()));
		Mage::log(sprintf('[ %s ]: Order retried finish at: %s', __METHOD__, $newDate), Zend_Log::DEBUG);
	}

	/**
	 * fetch all order with state new
	 * @return Mage_Sales_Model_Order_Resource_Collection
	 */
	private function _getNewOrders()
	{
		$orders = Mage::getResourceModel('sales/order_collection');
		$orders->addAttributeToSelect('*')
			->getSelect()
			->where("main_table.state = 'new'");
		return $orders->load();
	}
}

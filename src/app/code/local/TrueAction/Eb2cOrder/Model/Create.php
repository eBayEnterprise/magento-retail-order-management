<?php
/**
 * Generates an OrderCreate
 * @package Eb2c\Order
 * @author westm@trueaction.com
 * Some events I *may* need to care about. Not necessarily that I must *listen* for all of these, but that I should be aware of any side effects of these.
 * adminhtml_sales_order_create_process_data_before
 * adminhtml_sales_order_create_process_data
 * sales_convert_quote_to_order
 * sales_convert_quote_item_to_order_item
 * sales_order_place_before
 * checkout_type_onepage_save_order_after
 * checkout_type_multishipping_create_orders_single
 */
class TrueAction_Eb2cOrder_Model_Create
{
	const	GENDER_MALE = 1;
	const ESTIMATED_DELIVERY_DATE_MODE = 'LEGACY';
	const ESTIMATED_DELIVERY_DATE_MESSAGETYPE = 'NONE';
	/**
	 * The Shipping Charge Type recognized by the Exchange Platform for flatrate/order level shipping costs
	 */
	const SHIPPING_CHARGE_TYPE_FLATRATE = 'FLATRATE';
	/**
	 * @var Mage_Sales_Model_Order, Magento Order Object
	 */
	protected $_o;
	/**
	 * @var string, Human readable XML
	 */
	protected $_xmlRequest;
	/**
	 * @var string, Human readable XML
	 */
	protected $_xmlResponse;
	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	protected $_domRequest;
	/**
	 * @var TrueAction_Dom_Document, DOM Object
	 */
	protected $_domResponse;
	/**
	 * @var array, Saves an array of item_id's for use in shipping node
	 */
	protected $_orderItemRef;
	/**
	 * @var TrueAction_Eb2cCore_Model_Config_Registry, config Object
	 */
	protected $_config;
	/**
	 * @var array, hold magento payment map to eb2c
	 * @see https://trueaction.atlassian.net/wiki/display/EBC/Magento+Payment+Method+Map+with+Eb2c
	 */
	protected $_ebcPaymentMethodMap = array();
	public function __construct()
	{
		$this->_config = Mage::helper('eb2corder')->getConfig();
		$this->_ebcPaymentMethodMap = array(
			'Pbridge_eb2cpayment_cc' => 'CreditCard',
			'Paypal_express' => 'PayPal',
			'PrepaidCreditCard' => 'PrepaidCreditCard', // Not use
			'StoredValueCard' => 'StoredValueCard', // Not use
			'Points' => 'Points', // Not use
			'PrepaidCashOnDelivery' => 'PrepaidCashOnDelivery', // Not use
			'Free' => 'StoredValueCard',
		);
		// initiaze these class properties in the constructor.
		$this->_o = null;
		$this->_xmlRequest = null;
		$this->_xmlResponse = null;
		$this->_domRequest = null;
		$this->_domResponse = null;
		$this->_orderItemRef = array();
	}
	/**
	 * getter method to retrieve _xmlRequest property
	 * @see _xmlRequest
	 */
	public function getXmlRequest()
	{
		return $this->_xmlRequest;
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
		$uri = Mage::helper('eb2corder')->getOperationUri($this->_config->apiCreateOperation);
		$response = '';
		if ($this->_domRequest instanceof DOMDocument) {
			Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $this->getXmlRequest()), Zend_Log::DEBUG);
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
	 * extract the response status from the reponse xml string
	 * @param string $response, the response string xml from eb2c request
	 * @return string, Mage_Sales_Model_Order::STATE_PROCESSING | Mage_Sales_Model_Order::STATE_NEW
	 */
	protected function _extractResponseState($response)
	{
		if (trim($response) !== '') {
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
			return (strtoupper(trim($this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue)) === 'SUCCESS')?
				Mage_Sales_Model_Order::STATE_PROCESSING : Mage_Sales_Model_Order::STATE_NEW;
		}
		return Mage_Sales_Model_Order::STATE_NEW;
	}
	/**
	 * processing the request response from eb2c
	 * @param string $response, the response string xml from eb2c request
	 * @return self
	 */
	protected function _processResponse($response)
	{
		$state = $this->_extractResponseState($response);
		if ($state !== $this->_o->getState()) {
			$this->_o->setState($state)->save();
			Mage::log(
				sprintf('[ %s ]: updating order (%s) state to processing after successfully creating order from eb2c', __METHOD__, $this->_o->getIncrementId()),
				Zend_Log::DEBUG
			);
			Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
		} else {
			// If the response status is not success, but the order is already new, we should log it
			Mage::log(
				sprintf('[ %s ]: the following order (%s) received fail response from eb2c but the order state was already new.', __METHOD__, $this->_o->getIncrementId()),
				Zend_Log::WARN
			);
		}
		return $this;
	}
	/**
	 * to be implented in the future, if we have gms extension that can provide the url source and type
	 * As this is just a placeholder method for future implementation, no need to cover it
	 * @codeCoverageIgnore
	 * @return array, source data
	 */
	protected function _getSourceData()
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
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->_domRequest->formatOutput = true;
		$orderCreateRequest = $this
			->_domRequest
			->addElement($this->_config->apiCreateDomRootNodeName, null, $this->_config->apiXmlNs)
			->firstChild;
		$orderCreateRequest->setAttribute('orderType', $this->_config->apiOrderType);
		$orderCreateRequest->setAttribute('requestId', $this->_getRequestId());
		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService', $this->_config->apiLevelOfService);
		$order->setAttribute('customerOrderId', $this->_o->getIncrementId());
		$this->_buildCustomer($order->createChild('Customer'));
		$order->createChild('CreateTime', str_replace(' ', 'T', $this->_o->getCreatedAt()));
		$webLineId = 1;
		$orderItems = $order->createChild('OrderItems');
		foreach ($this->_o->getAllItems() as $item) {
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
			$orderSourceNode = $order->createChild('OrderSource', $orderSource['source']);
			$orderSourceNode->setAttribute('type', $orderSource['type']);
		}
		$order->createChild(
			'OrderHistoryUrl',
			Mage::helper('eb2corder')->getOrderHistoryUrl($this->_o)
		);
		$order->createChild('OrderTotal', sprintf('%.02f', $this->_o->getGrandTotal()));
		$this->_buildContext($orderCreateRequest->createChild('Context'));
		$this->_xmlRequest = $this->_domRequest->saveXML();
		return $this;
	}
	/**
	 * Build customer information node
	 * @param DomElement customer	where to place customer info
	 * @return void
	 */
	protected function _buildCustomer(DomElement $customer)
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$customer->setAttribute('customerId', sprintf('%s%s', $cfg->clientCustomerIdPrefix, $this->_o->getCustomerId()));
		$name = $customer->createChild('Name');
		$name->createChild('Honorific', $this->_o->getCustomerPrefix());
		$name->createChild('LastName', trim($this->_o->getCustomerLastname() . ' ' . $this->_o->getCustomerSuffix()));
		$name->createChild('MiddleName', $this->_o->getCustomerMiddlename());
		$name->createChild('FirstName', $this->_o->getCustomerFirstname());
		if ($this->_o->getCustomerGender()) {
			// Previously tried to pull out the gender text, but that's probably worse, since one could change
			// 	'Male' to 'Boys' (or 'Woman', for that matter) and an invalid or flat-out wrong value would be sent to GSI.
			//	Let's just check the gender value/ option id. If it's 1, male, otherwise, female.
			$genderToSend = ($this->_o->getCustomerGender() == self::GENDER_MALE) ?  'M' : 'F';
			$customer->createChild('Gender', $genderToSend);
		}
		if ($this->_o->getCustomerDob()) {
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
	protected function _buildOrderItem(DomElement $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
	{
		$order = $item->getOrder();
		$quoteId = $order->getQuoteId();
		$itemId = 'item_' . $item->getId();
		$reservationId = (trim($item->getEb2cReservationId()) !== '')? $item->getEb2cReservationId() : Mage::helper('eb2cinventory')->getRequestId($quoteId);
		$this->_orderItemRef[] = $itemId;
		$orderItem->setAttribute('id', $itemId);
		$orderItem->setAttribute('webLineId', $webLineId);
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
		// Tax on the Merchandise:
		$merchTaxFragment = $this->_buildTaxDataNodes(
			$this->getItemTaxQuotes($item, TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE)
		);
		if ($merchTaxFragment->hasChildNodes()) {
			$merchandise->appendChild($merchTaxFragment);
		}
		$merchandise->createChild('UnitPrice', sprintf('%.02f', $item->getPrice()));
		// End Merchandise
		// Shipping on the orderItem: when flatrate shipping, only the first item should have shipping prices
		// otherwise all items should have it for the shipping price for that item
		if ($this->_getShippingChargeType($this->_o) !== self::SHIPPING_CHARGE_TYPE_FLATRATE || $webLineId === 1) {
			$shipping = $pricing->createChild('Shipping');
			$shipping->createChild('Amount', $this->_getItemShippingAmount($item));
		}
		$shippingTaxFragment = $this->_buildTaxDataNodes(
			$this->getItemTaxQuotes($item, TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING)
		);
		if ($shippingTaxFragment->hasChildNodes()) {
			$shipping->appendChild($shippingTaxFragment);
		}
		// End Shipping
		// Duty on the orderItem:
		$dutyFragment = $this->_buildDuty($item);
		if ($dutyFragment->hasChildNodes()) {
			$pricing->appendChild($dutyFragment);
		}
		// End Duty
		$shippingMethod = $orderItem->createChild('ShippingMethod', Mage::helper('eb2ccore')->lookupShipMethod($order->getShippingMethod()));
		$this->_buildEstimatedDeliveryDate($orderItem, $item);
		$orderItem->createChild('ReservationId', $reservationId);
	}
	/**
	 * build an EstimatedDeliveryDate node.
	 * @param  TrueAction_Dom_Element      $orderItem
	 * @param  Mage_Sales_Model_Order_Item $item
	 * @return self
	 */
	protected function _buildEstimatedDeliveryDate(TrueAction_Dom_Element $orderItem, Mage_Sales_Model_Order_Item $item)
	{
		$edd = $orderItem->createChild('EstimatedDeliveryDate');
		$edd->createChild('DeliveryWindow')
			->addChild('From', $item->getEb2cDeliveryWindowFrom())
			->addChild('To', $item->getEb2cDeliveryWindowTo());
		$edd->createChild('ShippingWindow')
			->addChild('From', $item->getEb2cShippingWindowFrom())
			->addChild('To', $item->getEb2cShippingWindowTo());
		$edd->addChild('Mode', self::ESTIMATED_DELIVERY_DATE_MODE)
			->addChild('MessageType', self::ESTIMATED_DELIVERY_DATE_MESSAGETYPE);
		return $this;
	}
	/**
	 * Build TaxData nodes for the item
	 * @see  TrueAction_Eb2cTax_Model_Response_Quote for tax types.
	 * @param  TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes Collection of tax quotes to build tax nodes for
	 * @return DOMDocumentFragment                  A DOM fragment of the nodes
	 */
	protected function _buildTaxDataNodes(TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes)
	{
		$taxFragment = $this->_domRequest->createDocumentFragment();
		if ($taxQuotes->count()) {
			$taxData = $taxFragment->appendChild(
				$this->_domRequest->createElement('TaxData', null, $this->_config->apiXmlNs)
			);
			$taxes = $taxData->createChild('Taxes');
			$calc = Mage::getModel('tax/calculation');
			foreach ($taxQuotes as $taxQuote) {
				$taxNode = $taxes->createChild('Tax');
				// need to actually get these value from somewhere
				$taxNode->setAttribute('taxType', $taxQuote->getTaxType());
				$taxNode->setAttribute('taxability', $taxQuote->getTaxability());
				$taxNode->createChild('Situs', $taxQuote->getSitus());
				$jurisdiction = $taxNode->createChild('Jurisdiction', $taxQuote->getJurisdiction());
				$jurisdiction->setAttribute('jurisdictionLevel', $taxQuote->getJurisdictionLevel());
				$jurisdiction->setAttribute('jurisdictionId', $taxQuote->getJurisdictionId());
				$imposition = $taxNode->createChild('Imposition', $taxQuote->getImposition());
				$imposition->setAttribute('impositionType', $taxQuote->getImpositionType());
				$taxNode->createChild('EffectiveRate', $calc->round($taxQuote->getEffectiveRate()));
				$taxNode->createChild('TaxableAmount', $calc->round($taxQuote->getTaxableAmount()));
				$taxNode->createChild('CalculatedTax', $calc->round($taxQuote->getCalculatedTax()));
			}
		}
		return $taxFragment;
	}
	/**
	 * Get tax quotes for an item.
	 * @see  TrueAction_Eb2cTax_Model_Response_Quote for available tax types.
	 * @param  Mage_Sales_Model_Order_Item $orderItem The order item to get tax quotes for
	 * @param  int                         $taxType   The type of tax quotes to load
	 * @return TrueAction_Eb2cTax_Model_Resource_Response_Quote_Collection
	 */
	public function getItemTaxQuotes(Mage_Sales_Model_Order_Item $orderItem, $taxType)
	{
		$taxQuotes = Mage::getModel('eb2ctax/response_quote')->getCollection();
		$taxQuotes->addFieldToFilter('quote_item_id', $orderItem->getQuoteItemId())
			->addFieldToFilter('type', $taxType);
		return $taxQuotes;
	}
	/**
	 * Builds the Duty Node for order
	 * @param Mage_Sales_Model_Order_Item $item, the order item object
	 * @return DOMFragment
	 */
	protected function _buildDuty(Mage_Sales_Model_Order_Item $item)
	{
		$dutyFragment = $this->_domRequest->createDocumentFragment();
		$dutyQuotes = $this->getItemTaxQuotes($item, TrueAction_Eb2cTax_Model_Response_Quote::DUTY);
		if ($dutyQuotes->count()) {
			$duty = $dutyFragment->appendChild(
				$this->_domRequest->createElement('Duty', null, $tihs->_config->apiXmlNs)
			);
			$dutyTotal = 0;
			foreach ($dutyQuotes as $dutyQuote) {
				$dutyTotal += $dutyQuote->getCalculatedTax();
			}
			if ($dutyTotal > 0) {
				$duty->createChild('Amount', $dutyTotal);
				$dutyTax = $this->_buildTaxDataNodes($dutyQuotes);
				if ($dutyTax->hasChildNodes()) {
					$duty->addChild($dutyTax);
				}
			}
		}
		return $dutyFragment;
	}
	/**
	 * Builds the ShipGroup Node for order
	 * @param DomElement shipGroup Node
	 * @return void
	 */
	protected function _buildShipGroup(DomElement $shipGroup)
	{
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType', $this->_getShippingChargeType($this->_o));
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', $this->_config->apiShipGroupDestinationId);
		$orderItems = $shipGroup->createChild('OrderItems');
		foreach ($this->_orderItemRef as $orderItemRef) {
			$shipItem = $orderItems->createChild('Item');
			$shipItem->setAttribute('ref', $orderItemRef);
		}
	}
	/**
	 * Builds the Shipping Node for order
	 * @param DomElement shipping Node to contain shipping and billing info
	 * @return void
	 */
	protected function _buildShipping(DomElement $shipping)
	{
		$destinations = $shipping->createChild('Destinations');
		// Ship-To
		$sa = $this->_o->getShippingAddress();
		$dest = $destinations->createChild('MailingAddress');
		$dest->setAttribute('id', $this->_config->apiShipGroupDestinationId);
		$this->_buildPersonName($dest->createChild('PersonName'), $sa);
		$this->_buildAddress($dest->createChild('Address'), $sa);
		$dest->createChild('Phone', $sa->getTelephone());
		// Bill-To
		$ba = $this->_o->getBillingAddress();
		$billing = $destinations->createChild('MailingAddress');
		$billing->setAttribute('id', $this->_config->apiShipGroupBillingId);
		$this->_buildPersonName($billing->createChild('PersonName'), $ba);
		$this->_buildAddress($billing->createChild('Address'), $ba);
		$billing->createChild('Phone', $ba->getTelephone());
	}
	/**
	 * Creates PersonName element details from an address
	 * @param DomElement personName
	 * @param Mage_Sales_Model_Order_Address address
	 * @return void
 	 */
	protected function _buildPersonName(DomElement $person, Mage_Sales_Model_Order_Address $address)
	{
		$person->createChild('Honorific', $address->getPrefix());
		$person->createChild('LastName', trim($address->getLastname() . ' ' . $address->getSuffix()));
		$person->createChild('MiddleName', $address->getMiddlename());
		$person->createChild('FirstName', $address->getFirstname());
	}
	/**
	 * Creates MailingAddress/Address element details from address
	 * @param DomElement addressElement
	 * @param Mage_Sales_Order_Address address
	 * @return void
	 */
	protected function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
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
	 * @param DomElement payment
	 * @return void
	 */
	protected function _buildPayment($payment)
	{
		$payment->createChild('BillingAddress')->setAttribute('ref', $this->_config->apiShipGroupBillingId);
		$this->_buildPayments($payment->createChild('Payments'));
	}
	/**
	 * Creates the Tender entries within the Payments Element
	 * @param DomElement payments node into which payment info is placed
	 * @return void
	 */
	protected function _buildPayments(DomElement $payments)
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			foreach ($this->_o->getAllPayments() as $payment) {
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
					$auth->createChild('ResponseCode', $payment->getAdditionalInformation('response_code'));
					$auth->createChild('BankAuthorizationCode', $payment->getAdditionalInformation('bank_authorization_code'));
					$auth->createChild('CVV2ResponseCode', $payment->getAdditionalInformation('cvv2_response_code'));
					$auth->createChild('AVSResponseCode', $payment->getAdditionalInformation('avs_response_code'));
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
						$pan = $this->_getOrderGiftCardPan($this->_o);
						$thisPayment = $payments->createChild($payMethodNode);
						$paymentContext = $thisPayment->createChild('PaymentContext');
						$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
						$paymentContext->createChild('TenderType', Mage::helper('eb2cpayment')->getTenderType($pan));
						$paymentContext->createChild('PaymentAccountUniqueId', $pan)->setAttribute('isToken', 'false');
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
	 * get order stored value pan
	 * @param Mage_Sales_Model_Order $order, the order object
	 * @return string, the pan
	 */
	protected function _getOrderGiftCardPan(Mage_Sales_Model_Order $order)
	{
		$giftCardData = unserialize($order->getGiftCards());
		if (!empty($giftCardData)) {
			foreach ($giftCardData as $gcData) {
				if (isset($gcData['pan']) && trim($gcData['pan']) !== '') {
					return $gcData['pan'];
				}
			}
		}
		return '';
	}
	/**
	 * Populates the Context element
	 * @param DomElement context
	 * @return void
	 */
	protected function _buildContext(DomElement $context)
	{
		$this->_buildBrowserData($context->createChild('BrowserData'));
	}
	/**
	 * Populates the Context/BrowserData element
	 * @param DomElement context
	 * @return void
	 */
	protected function _buildBrowserData(DomElement $browserData)
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
	protected function _getRequestId()
	{
		return uniqid('OCR-');
	}
	/**
	 * Get the Exchange shipping charge type for the given shipping method.
	 * Currently, all shipping charges being sent as 'FLATRATE' for order level shipping charges.
	 * Full implementation supporting order level and item level shippin gamounts
	 * will likely need to look up the shipping method for the order and
	 * make a better determination as to the charge type for the shipping.
	 * @param  Mage_Sales_Model_Order $order Order the shipping charge applies to
	 * @return string Shipping charge type used by Exchange Platform
	 */
	protected function _getShippingChargeType(Mage_Sales_Model_Order $order)
	{
		return self::SHIPPING_CHARGE_TYPE_FLATRATE;
	}
	protected function _getItemShippingAmount(Mage_Sales_Model_Order_Item $item)
	{
		if ($this->_getShippingChargeType($item->getOrder()) === self::SHIPPING_CHARGE_TYPE_FLATRATE) {
			return (float) $item->getOrder()->getBaseShippingAmount();
		} else {
			throw new Exception('Non-flatrate shipping calculations are not yet supported.');
		}
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
	protected function _getNewOrders()
	{
		return Mage::getResourceModel('sales/order_collection')
			->addAttributeToSelect('*')
			->addFieldToFilter('state', array('eq' => 'new'))
			->load();
	}
}

<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use \eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

/**
 * A collection of entrypoint methods for handling order-related events.
 */
class EbayEnterprise_Order_Model_Observer
{
	/** @var EbayEnterprise_Order_Helper_Event_Shipment */
	protected $_shipmentEventHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;
	/** @var EbayEnterprise_Order_Helper_Event */
	protected $_orderEventHelper;
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_orderCfg;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	/**
	 * Initialize properties
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_logger,
			$this->_orderHelper,
			$this->_orderCfg,
			$this->_orderEventHelper,
			$this->_shipmentEventHelper,
			$this->_coreHelper,
			$this->_logContext
		) = $this->_checkTypes(
			$this->_nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce('helper', $args, Mage::helper('ebayenterprise_order')),
			$this->_nullCoalesce('config', $args, Mage::helper('ebayenterprise_order')->getConfigModel()),
			$this->_nullCoalesce('event_helper', $args, Mage::helper('ebayenterprise_order/event')),
			$this->_nullCoalesce('shipment_event_helper', $args, Mage::helper('ebayenterprise_order/event_shipment')),
			$this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore')),
			$this->_nullCoalesce('log_context', $args, Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	/**
	 * ensure correct types are being injected
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param EbayEnterprise_Order_Helper_Data
	 * @param EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @param EbayEnterprise_Order_Helper_Event
	 * @param EbayEnterprise_Order_Helper_Event_Shipment
	 * @param EbayEnterprise_Eb2cCore_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Context
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Order_Helper_Data $orderHelper,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
		EbayEnterprise_Order_Helper_Event $orderEventHelper,
		EbayEnterprise_Order_Helper_Event_Shipment $shipmentEventHelper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Context $logContext
	) {
		return [$logger, $orderHelper, $orderCfg, $orderEventHelper, $shipmentEventHelper, $coreHelper, $logContext];
	}

	/**
	 * return $ar[$key] if it exists otherwise return $default
	 * @param  string
	 * @param  array
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * Fetch an instance of the order create request model
	 *
	 * @param array $args Key value pair of constructor arguments.
	 *                    You must at least provide the order object.
	 * @return EbayEnterprise_Order_Model_Create
	 */
	protected function _getOrderCreateModel(array $args)
	{
		return Mage::getModel('ebayenterprise_order/create', $args);
	}

	/**
	 * Account for shipping discounts not attached to an item.
	 * Combine all shipping discounts into one.
	 *
	 * @see self::handleSalesConvertQuoteAddressToOrderAddress
	 * @see Mage_SalesRule_Model_Validator::processShippingAmount
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleSalesQuoteCollectTotalsAfter(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		/** @var Mage_Sales_Model_Quote $quote */
		$quote = $event->getQuote();
		/** @var Mage_Sales_Model_Resource_Quote_Address_Collection */
		$addresses = $quote->getAddressesCollection();
		foreach ($addresses as $address) {
			$appliedRuleIds = $address->getAppliedRuleIds();
			if (is_array($appliedRuleIds)) {
				$appliedRuleIds = implode(',', $appliedRuleIds);
			}
			$data = (array) $address->getEbayEnterpriseOrderDiscountData();
			$data[$appliedRuleIds] = [
				'amount_value' => $address->getBaseShippingDiscountAmount(),
				'description' => $this->_orderHelper->__('Shipping Discount'),
			];
			$address->setEbayEnterpriseOrderDiscountData($data);
		}
	}

	/**
	 * Account for discounts in order create request.
	 *
	 * @see self::handleSalesConvertQuoteItemToOrderItem
	 * @see Mage_SalesRule_Model_Validator::process
	 * @see Order-Datatypes-Common-1.0.xsd:PromoDiscountSet
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleSalesRuleValidatorProcess(Varien_Event_Observer $observer)
	{
		/** @var string $romStoreId */
		$romStoreId = $this->_coreHelper->getConfigModel()->storeId;
		/** @var Varien_Event $event */
		$event = $observer->getEvent();
		/** @var Mage_SalesRule_Model_Rule $rule */
		$rule = $event->getRule();
		/** @var Mage_Sales_Model_Quote $quote */
		$quote = $event->getQuote();
		/** @var Mage_Core_Model_Store $store */
		$store = $quote->getStore();
		/** @var Mage_Sales_Model_Quote_Item $item */
		$item = $event->getItem();
		$data = (array) $item->getEbayEnterpriseOrderDiscountData();
		$ruleId = $rule->getId();
		// Use the rule id to prevent duplicates.
		$data[$ruleId] = [
			'amount' => $event->getResult()->getBaseDiscountAmount(),
			'applied_count' => $event->getQty(),
			'code' => $rule->getCouponCode(),
			'description' => $rule->getStoreLabel($store) ?: $rule->getName(),
			'effect_type' => $rule->getSimpleAction(),
			'id' => sprintf('%s-%s', $romStoreId, $ruleId),
		];
		$item->setEbayEnterpriseOrderDiscountData($data);
	}

	/**
	 * Submit an order create request
	 *
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleSalesOrderPlaceAfter(Varien_Event_Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		if ($order instanceof Mage_Sales_Model_Order) {
			$api = $this->_coreHelper->getSdkApi(
				$this->_orderCfg->apiService,
				$this->_orderCfg->apiCreateOperation
			);
			$constructorArgs = [
				'api' => $api,
				'config' => $this->_orderCfg,
				'order' => $order,
				'payload' => $api->getRequestBody(),
			];
			$this->_getOrderCreateModel($constructorArgs)->send();
		} else {
			$this->_logger->logWarn('[%s] Attempted to submit order create request, but parameter (%s) is not an order.', [__CLASS__, gettype($order)]);
		}
	}

	/**
	 * Fetch all orders with state 'new' and status 'unsubmitted'.
	 *
	 * @return Mage_Sales_Model_Order_Resource_Collection
	 */
	protected function _getUnsubmittedOrders()
	{
		$status = EbayEnterprise_Order_Model_Create::STATUS_NEW;
		return Mage::getResourceModel('sales/order_collection')
			->addAttributeToSelect('*')
			->addFieldToFilter('status', ['eq' => $status]);
	}

	/**
	 * Retry order create requests on unsubmitted orders.
	 * Run this on cron.
	 *
	 * @return void
	 */
	public function handleEbayEnterpriseOrderCreateRetryJob()
	{
		$orders = $this->_getUnsubmittedOrders();
		$this->_logger->debug(
			'Found {order_retry_count} order(s) to be resubmitted.',
			$this->_logContext->getMetaData(__CLASS__, ['order_retry_count' => $orders->getSize()])
		);
		$api = $this->_coreHelper->getSdkApi(
			$this->_orderCfg->apiService,
			$this->_orderCfg->apiCreateOperation
		);
		$createArgs = [
			'api' => $api,
			'config' => $this->_orderCfg,
			'payload' => $api->getRequestBody(),
			'is_payload_prebuilt' => true
		];
		foreach ($orders as $order) {
			$this->_resubmit($order, $createArgs);
		}
		$this->_logger->debug('Order retry complete.', $this->_logContext->getMetaData(__CLASS__));
	}

	/**
	 * resubmit the order
	 * @param  Mage_Sales_Model_Order
	 * @param  array
	 */
	protected function _resubmit(Mage_Sales_Model_Order $order, array $createArgs)
	{
		$raw = $order->getEb2cOrderCreateRequest();
		if ($raw) {
			$createArgs['order'] = $order;
			$this->_getOrderCreateModel($createArgs)->send();
		} else {
			$this->_logger->warning(
				'Unable to resubmit "{order_id}". Please see documentation for possible solutions.',
				$this->_logContext->getMetaData(['order_id' => $order->getIncrementId()])
			);
		}
	}

	/**
	 * Consume the 'ebayenterprise_amqp_message_credit_issued' event.
	 * Pass the payload from the event to the 'ebayenterprise_order/creditissued' model instance.
	 * Invoke the `process` method on the model to process the payload and issue the credit memo
	 *
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleEbayEnterpriseAmqpMessageOrderCreditIssued(Varien_Event_Observer $observer)
	{
		Mage::getModel('ebayenterprise_order/creditissued', ['payload' => $observer->getEvent()->getPayload()])->process();
	}

	/**
	 * Consume the event 'ebayenterprise_amqp_message_order_rejected'. Pass the payload
	 * from the event down to the 'ebayenterprise_order/orderrejected' instance. Invoke the process
	 * method on the 'ebayenterprise_order/orderrejected' instance.
	 * @param  Varien_Event_Observer
	 * @return void
	 */
	public function handleEbayEnterpriseAmqpMessageOrderRejected(Varien_Event_Observer $observer)
	{
		Mage::getModel('ebayenterprise_order/orderrejected', [
			'payload' => $observer->getEvent()->getPayload(),
			'order_event_helper' => $this->_orderEventHelper,
			'logger' => $this->_logger,
		])->process();
	}
	/**
	 * Listen for an order cancel event.
	 * Load a collection using the extracted order increment ids.
	 * Update each order's state and status to 'canceled' and the associated status respectively.
	 *
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleEbayEnterpriseOrderEventCancel(Varien_Event_Observer $observer)
	{
		$message = trim($observer->getEvent()->getMessage());
		$orderCollection = $this->_loadOrdersFromXml($message);
		$eventName = $observer->getEvent()->getName();
		foreach ($orderCollection as $order) {
			$this->_orderEventHelper->attemptCancelOrder($order, $eventName);
		}
	}

	/**
	 * Consume the event 'ebayenterprise_amqp_message_order_shipped'. Pass the payload
	 * from the event down to the 'ebayenterprise_order/ordershipped' instance. Invoke the process
	 * method on the 'ebayenterprise_order/ordershipped' instance.
	 *
	 * @param  Varien_Event_Observer
	 * @return void
	 */
	public function handleEbayEnterpriseAmqpMessageOrderShipped(Varien_Event_Observer $observer)
	{
		Mage::getModel('ebayenterprise_order/ordershipped', [
			'payload' => $observer->getEvent()->getPayload(),
			'shipment_event_helper' => $this->_shipmentEventHelper,
			'logger' => $this->_logger,
		])->process();
	}

	/**
	 * Responsible for extracting order increment ids from a passed in DOM document
	 * and then load a collection of sales/order instances for any increment ids in
	 * the document.
	 *
	 * @param  string
	 * @return Varien_Data_Collection
	 */
	protected function _loadOrdersFromXml($xml)
	{
		return $this->_orderHelper->getOrderCollectionByIncrementIds(
			$this->_orderHelper->extractOrderEventIncrementIds($xml)
		);
	}

	/**
	 * Copy custom data to order items.
	 *
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleSalesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
	{
		/** @var Varien_Event $event */
		$event = $observer->getEvent();
		/** @var Mage_Sales_Model_Quote_Item_Abstract $item */
		$item = $event->getItem();
		/** @var Mage_Sales_Model_Order_Item $orderItem */
		$orderItem = $event->getOrderItem();
		$data = $item->getEbayEnterpriseOrderDiscountData();
		$orderItem->setEbayEnterpriseOrderDiscountData($data);
	}

	/**
	 * Copy custom data to order addresses.
	 *
	 * @param Varien_Event_Observer
	 * @return void
	 */
	public function handleSalesConvertQuoteAddressToOrderAddress(Varien_Event_Observer $observer)
	{
		/** @var Varien_Event $event */
		$event = $observer->getEvent();
		/** @var Mage_Sales_Model_Quote_Address $address */
		$address = $event->getAddress();
		/** @var Mage_Sales_Model_Order_Address $orderAddress */
		$orderAddress = $event->getOrderAddress();
		$data = $address->getEbayEnterpriseOrderDiscountData();
		$orderAddress->setEbayEnterpriseOrderDiscountData($data);
	}
}

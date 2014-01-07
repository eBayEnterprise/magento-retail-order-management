<?php
/**
 * Order Status processing Class, gets Order Status feeds from remote
 */
class TrueAction_Eb2cOrder_Model_Status_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	protected function _construct()
	{
		$this->setFeedConfig(Mage::helper('eb2corder')->getConfig());
		$this->setFeedRemotePath($this->getFeedConfig()->statusFeedRemotePath);
		$this->setFeedFilePattern($this->getFeedConfig()->statusFeedFilePattern);
		$this->setFeedLocalPath($this->getFeedConfig()->statusFeedLocalPath);
		$this->setFeedRootNodeName($this->getFeedConfig()->statusFeedRootNodeName);
		$this->setFeedEventType($this->getFeedConfig()->statusFeedEventType);
		parent::_construct();
	}

	/**
	 * Load a Mage Order
	 * @param Order Increment Id
	 * @return Mage_Sales_Model_Order
	 */
	protected function _loadMageOrder($orderId)
	{
		$mageOrder = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		return $mageOrder;
	}

	/**
	 * Is this status older than a status that's already been applied?
	 * @param Mage_Sales_Model_Order
	 * @param StatusDateTime (as from Eb2c)
	 */
	protected function _statusIsExpired($mageOrder, $statusDateTimeStamp)
	{
		if ($statusDateTimeStamp <= $mageOrder->getEb2cOrderStatusTimestamp()) {
			return true;
		}
		return false;
	}

	/**
	 * This is the only abstracted method - process the acutal DOM
	 * @param TrueAction_Dom_Document the feed as already in a DOM
	 * @return self;
	 */
	public function processDom(TrueAction_Dom_Document $dom)
	{
		$extractor = Mage::getModel('eb2corder/status_feed_extractor');

		foreach( $dom->getElementsByTagName($this->getFeedRootNodeName()) as $orderStatusUpdate) {

			foreach( $dom->getElementsByTagName('OrderStatus') as $orderStatusNode ) {

				$statusType      = $extractor->extractStatusType($orderStatusNode);
				$statusTimeStamp = $extractor->extractStatusTimeStamp($orderStatusNode);
				$orderNode       = $extractor->extractOrderNode($orderStatusNode);
				$orderNumber     = $extractor->extractExternalOrderNumber($extractor->extractOrderHeaderNode($orderNode));
				$mageOrder       = $this->_loadMageOrder($orderNumber);

				if( !$mageOrder->getId() ) {
					Mage::log("$orderNumber not found, status $statusType discarded", Zend_log::WARN);
				} else if ($this->_statusIsExpired($mageOrder, $statusTimeStamp)) {
					Mage::log("Ignored expired status $statusType for $orderNumber", Zend_log::WARN);
				} else {
					$orderDetails  = $this->_rollUpOrderDetails($extractor, $orderNode);
					Mage::log(
						$this->_detlDumper($statusType, $mageOrder->getIncrementId(), $orderDetails),
						Zend_log::DEBUG
					);
					switch(strtolower($statusType))
					{
					case 'confirmation':
						$this->_confirmation($mageOrder, $orderDetails, $statusTimeStamp);
						break;
					case 'cancellation':
						$this->_cancellation($mageOrder, $orderDetails, $statusTimeStamp);
						break;
					case 'returncreditissued':
						$this->_returnCreditIssued($mageOrder, $orderDetails, $statusTimeStamp);
						break;
					case 'shipment':
						$this->_shipment($mageOrder, $orderDetails, $statusTimeStamp);
						break;
					default:
						// Log as unknown status type, keep movin' on
						Mage::log("Unknown status $statusType for $orderNumber", Zend_log::WARN);
						// Updating the order with the status type they sent, but not with the
						// Status Type TimeStamp
						$mageOrder
							->setEb2cOrderStatusApplied(Mage::getSingleton('core/date')->gmtDate())
							->setEb2cOrderStatusType($statusType)
							->save();
						break;
					}
				}
			}
		}
		return $this;
	}

	/** 
	 * Set the eb2cOrdereStatusFields
	 */
	protected function _setEb2cOrderStatus($mageOrder, $status, $statusTimeStamp)
	{
		return $mageOrder
			->setEb2cOrderStatusApplied(Mage::getSingleton('core/date')->gmtDate())
			->setEb2cOrderStatusType($status)
			->setEb2cOrderStatusTimestamp($statusTimeStamp);
	}

	/**
	 * Nothing to do, except log that we received confirmation, and update the order eb2cStatusType
	 * @param $mageOrder Mage_Sales_Model_Order
	 * @param array $orderDetails line item details
	 * @return self
	 */
	protected function _confirmation($mageOrder, $orderDetails, $statusTimeStamp)
	{
		$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)->save();
		return $this;
	}

	/**
	 * Cancel items on an order. If all items are canceled, we cancel the whole order.
	 * @param $mageOrder Mage_Sales_Model_Order
	 * @param array $orderDetails line item details
	 * @return self
	 */
	protected function _cancellation($mageOrder, $orderDetails)
	{
		foreach ($orderDetails as $shipmentId => $lineItems) {
			foreach ($lineItems as $lineItem) {
				$mageOrderLine = Mage::getModel('sales/order_item')->load($lineItem->getLineItemNumber());
				$mageOrderline
					->setQtyCanceled($lineItem->getQuantity())
					->save();
			}
		}

		$cancelComplete = true;
		foreach ($mageOrder->getAllVisibleItems() as $mageLineItem) {
			if (($mageLineItem->getQtyOrdered() - $mageLineItem->getQtyCanceled()) !== 0) {
				$cancelComplete = false;
				break;
			}
		}

		if ($cancelComplete === false) {
			// This means there are items still left to ship - it's a partial cancelation
			$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)
				->save();
		} else if ($mageOrder->canCancel()) {
			// Order of operations here are lore handed down from GFMW
			try {
				$mageOrder
					->getPayment()
					->cancel()
					->registerCancellation();
				$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)
					->save();
				Mage::dispatchEvent('order_cancel_after', array('order' => $mageOrder));
			} catch(Exception $e) {
				$mageOrder
					->registerCancellation();
				$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp);
				Mage::dispatchEvent('order_cancel_after', array('order' => $mageOrder));
				$mageOrder->save();

				Mage::log(
					'Exception canceling ' . $mage->getIncrementId() . $e->getMessage(),
					Zend_log::ERR
				);
			}
		} else {
			Mage::log('Cannot cancel ' . $mageOrder->getIncrementId(), Zend_log::WARN);
			$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)
				->save();
		}
		return $this;
	}

	/**
	 * Apply a credit amount.
	 * @param $mageOrder Mage_Sales_Model_Order
	 * @param array $orderDetails line item details
	 * @return self
	 */
	protected function _returnCreditIssued($mageOrder, $orderDetails)
	{
		foreach ($mageOrder->getInvoiceCollection() as $invoice) {
			if ($invoice->canRefund()) {
				$service = Mage::getModel('sales/service_order', $mageOrder);
				$creditMemo = $service->prepareInvoiceCreditmemo($invoice);
				$creditMemo->setAdjustmentPositive(/*TODO Need the amount!*/)
					->refund()
					->save();
			}
		}
		$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)
			->save();
		return $this;
	}

	/**
	 * Note shipped items. If all items shipped, order is complete, and we can issue invoice.
	 * @param $mageOrder Mage_Sales_Model_Order
	 * @param array $orderDetails line item details
	 * @return self
	 */
	protected function _shipment($mageOrder, $orderDetails)
	{
		$invoiceItems = array(); // Because we've shipped something, we have to invoice something
		$trackingInfo = array(); // Because we've shipped something, we have some tracking info

		foreach ($orderDetails as $shipmentId => $lineItems) {
			foreach ($lineItems as $lineItem) {
				$mageOrderLine = Mage::getModel('sales/order_item')->load($lineItem->getLineItemNumber());
				$mageOrderline
					->setQtyShipped($lineItem->getQuantity())
					->save();
				$invoiceItems[$mageOrderLine->getId()] += $lineItem->getQuantity();
				$tracking = $this->getTrackingNumberList();
				if( count($tracking) ) {
					$trackingInfo[$tracking->getTrackingNumber()] = $tracking->getTrackingUrl();
				}
			}
		}

		// Do we have tracking info? Let's make some shipment records
		if (count($trackingInfo)) {
			$mageShipment = $mageOrder->prepareShipment($invoiceItems);
			foreach ($trackingInfo as $number => $url) {
				$trackingData = array('carrier_code' => $url /*TODO Do we know carrier? For now, it's the URL? */, 'title' => /* TODO How do we know title? */'', 'number' => $number);
				$mageTracking = Mage::getModel('sales/order_shipment_track')->addData($trackingData);
				$mageShipment->addTrack($mageTracking);

				// TODO: There is a ShipmentId coming from OrderStatus - maybe that's the array of Packages to be sent to ->setPackages()? */
				$mageShipment->register();
				try {
					$transaction = Mage::getModel('core/resource_transaction')
						->addObject($mageShipment)
						->addObject($mageShipment->getOrder())
						->save();
					$mageShipment->sendEmail();
					$mageShipment->setEmailSent(true);
					$mageShipment->save();
					$this->_otfShipCreated[] = $this->_qtys->orderId;
				} catch (Mage_Core_Exception $e) {
					Mage::log(
						'Shipment create failed ' . $mageOrder->getIncrementId() . ' ' . $e->getMessage(),
						Zend_log::ERR
					);
				}
			}
		}
	
		// Invoice what's just been shipped
		$mageInvoice = Mage::getModel('sales/service_order', $mageOrder)->prepareInvoice($invoiceItems);
		try {
			// Create invoice, capture payment
			$mageInvoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
			$mageInvoice->register();
			$transaction = Mage::getModel('core/resource_transaction')
				->addObject($mageInvoice)
				->addObject($mageInvoice->getOrder());
			$transaction->save();

			// TODO: Don't know if I can do eMail here?
			$mageInvoice->sendEmail();
			$mageInvoice->setEmailSent(true);
			$mageInvoice->save();
		} catch(Exception $e) {
			// Log if we can't create an invoice, but we keep processing the shipment.
				Mage::log('Cant invoice: ' . $mageOrder->getIncrementId() . '::' . $e->getMessage(), Zend_log::ERR);
		}
		$this->_setEb2cOrderStatus($mageOrder, __FUNCTION__, $statusTimeStamp)
			->save();

		return $this;
	}

	/**
	 * Rolls up the XML into array indexed by ShipmentId. Each element is in turn an array
	 * of Varien_Objects representing  Line Item Information.
	 * @param $extractor Feed extractor model
	 * @param TrueAction_Dom_Element OrderNode Element
	 * @return array
	 */
	function _rollUpOrderDetails($extractor, TrueAction_Dom_Element $orderNode)
	{
		$rolledUpDetails = array();
		$shipments = $extractor->extractShipmentsNode($orderNode);
		foreach( $extractor->extractShipmentNode($shipments) as $shipment ) {
			$shipmentId = $extractor->extractShipmentId($shipment);
			foreach ($extractor->extractOrderItemsNode($shipment) as $orderItemsNode) {
				$orderDetails = array();
				foreach ($extractor->extractOrderItemNode($orderItemsNode) as $orderItemNode) {
					$orderDetails[] = $extractor->orderItemNodeToObject($orderItemNode);
				}
			}
			$rolledUpDetails[$shipmentId] = $orderDetails;
		}
		return $rolledUpDetails;
	}

	/**
	 * Dumps rolled-up order line item detail into human readable form, for logging
	 * @param $method calling method
	 * @param $orderNumber Mage Order Number
	 * @param $rollUp rolled-up details as from $this->_rollUpOrderDetails()
	 * @return string human-readable output
	 */
	private function _detlDumper($method, $orderNumber, $rollUp)
	{
		$msg = "$method($orderNumber)\n";
		foreach($rollUp as $shipmentId => $lineItems) {
			$msg .= "ShipmentId: " . $shipmentId . "\n";
			foreach($lineItems as $lineItem) {
				$msg .= sprintf('  %04d ', $lineItem->getLineItemNumber() ). $lineItem->getQuantity() . ' x ' . $lineItem->getSku() . "\n";
			}
		}
		return $msg;
	}
}

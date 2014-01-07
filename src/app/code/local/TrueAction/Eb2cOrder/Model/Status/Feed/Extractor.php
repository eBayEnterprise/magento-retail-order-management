<?php
class TrueAction_Eb2cOrder_Model_Status_Feed_Extractor
{
	/**
	 * Given an OrderStatus node, extract the Status Type from the DOM in orderStatus
	 * @param TrueAction_Dom_Element orderStatus 
	 * @return string StatusType
	 */
	public function extractStatusType(TrueAction_Dom_Element $anOrderStatusNode)
	{
		$statusType = $anOrderStatusNode->getElementsByTagName('StatusType')->item(0)->nodeValue;
		return $statusType;
	}

	/**
	 * Given an OrderStatus node, extract the Status Time Stamp from the DOM in orderStatus
	 * @param TrueAction_Dom_Element orderStatus 
	 * return string StatusTimeStamp
	 */
	public function extractStatusTimeStamp(TrueAction_Dom_Element $anOrderStatusNode)
	{
		$statusTimeStamp = $anOrderStatusNode->getElementsByTagName('StatusTimeStamp')->item(0)->nodeValue;
		return $statusTimeStamp;
	}

	/**
	 * Given an OrderStatus node, Extract the OrderNode from the DOM in order Status
	 * @param TrueAction_Dom_Element orderStatus 
	 * @return TrueAction_Dom_Element <Order> node 
	 */
	public function extractOrderNode(TrueAction_Dom_Element $anOrderStatusNode)
	{
		$status    = $anOrderStatusNode->getElementsByTagName('Status')->item(0);
		$orderNode = $status->getElementsByTagName('Order')->item(0);
		return $orderNode;
	}

	/**
	 * Given an <Order> node, we return the <OrderHeader> node
	 * @param TrueAction_Dom_Element <Order> node 
	 * @return TrueAction_Dom_Element <OrderHeader> node 
	 */
	public function extractOrderHeaderNode(TrueAction_Dom_Element $anOrderNode)
	{
		$orderHeaderNode = $anOrderNode->getElementsByTagName('OrderHeader')->item(0);
		return $orderHeaderNode;
	}

	/**
	 * Given an <Order> node, we return the <OrderHeader> node
	 * @param TrueAction_Dom_Element <Order> node 
	 * @return string value of the External Order Number (which maps to a Magento Order #)
	 */
	public function extractExternalOrderNumber(TrueAction_Dom_Element $anOrderHeaderNode)
	{
		$orderNumber = $anOrderHeaderNode->getElementsByTagName('ExternalOrderNumber')->item(0)->nodeValue;
		return $orderNumber;
	}

	/**
	 * Given an <Order> return the <Shipments> node.
	 * @param TrueAction_Dom_Element <Order> node.
	 * @return TrueAction_Dom_Element <Shipments> node
	 */
	public function extractShipmentsNode(TrueAction_Dom_Element $anOrderNode)
	{
		$shipments = $anOrderNode->getElementsByTagName('Shipments')->item(0);
		return $shipments;
	}

	/**
	 * Given a <Shipments> return a single <Shipment> node. Intended to be called iteratively.
	 * @param TrueAction_Dom_Element <Shipments> node.
	 * @return TrueAction_Dom_Element <Shipment> node
	 */
	public function extractShipmentNode(TrueAction_Dom_Element $aShipmentsNode)
	{
		$aShipmentNode = $aShipmentsNode->getElementsByTagName('Shipment');
		return $aShipmentNode;
	}

	/**
	 * Given a <Shipment> node, return a ShipmentId string.
	 * @param TrueAction_Dom_Element <Shipment> node.
	 * @return string ShipmentId node value
	 */
	public function extractShipmentId(TrueAction_Dom_Element $aShipmentNode)
	{
		$shipmentId = $aShipmentNode->getElementsByTagName('ShipmentId')->item(0)->nodeValue;
		return $shipmentId;
	}

	/**
	 * Given a <Shipment> node, return an <OrderItems> node. Intended to be called iteratively.
	 * @param TrueAction_Dom_Element <Shipment> node.
	 * @return TrueAction_Dom_Element <OrderItems> node
	 */
	public function extractOrderItemsNode(TrueAction_Dom_Element $aShipmentNode)
	{
		$anOrderItemsNode = $aShipmentNode->getElementsByTagName('OrderItems');
		return $anOrderItemsNode;
	}

	/**
	 * Given an <OrderItems> node, return a single <OrderItem> node. Intended to be called iteratively.
	 * @param TrueAction_Dom_Element <OrderItems> node.
	 * @return TrueAction_Dom_Element <OrderItem> node
	 */
	public function extractOrderItemNode(TrueAction_Dom_Element $anOrderItemsNode)
	{
		$anOrderItemNode = $anOrderItemsNode->getElementsByTagName('OrderItem');
		return $anOrderItemNode;
	}

	/**
	 * Given an <OrderItem> node, return an Varien_Object represenation of it.
	 * @param TrueAction_Dom_Element <OrderItem> node
	 * @return Varien_Object
	 */
	public function orderItemNodeToObject(TrueAction_Dom_Element $anOrderItemNode)
	{
		$productNode            = $anOrderItemNode->getElementsByTagName('Product')->item(0);	
		$orderItemArray = array(
			'line_item_number'     => $anOrderItemNode->getElementsByTagName('LineItemNumber')->item(0)->nodeValue,
			'sub_line_item_number' => $anOrderItemNode->getElementsByTagName('SubLineItemNumber')->item(0)->nodeValue,
			'sku'                  => $productNode->getElementsByTagName('SKU')->item(0)->nodeValue,
			'quantity'             => $productNode->getElementsByTagName('Quantity')->item(0)->nodeValue,
		);

		$trackingNumberListNode = $anOrderItemNode->getElementsByTagName('TrackingNumberList')->item(0);
		$trackingInfo = array();
		foreach ($trackingNumberListNode->getElementsByTagName('TrackingInfo') as $trackingInfo) {
			$tracking[] = new Varien_Object (
				array(
					'tracking_number' => $trackingInfo->getElementsByTagName('TrackingNumber')->item(0)->nodeValue,
					'tracking_url'    => $trackingInfo->getElementsByTagName('TrackingURL')->item(0)->nodeValue,
				)
			);
		}
		$orderItemArray['tracking_number_list'] = new Varien_Object( $tracking );
		return new Varien_Object( $orderItemArray );
	}
}

<?php
Mage::log('[ ' . __CLASS__ . ' ] Upgrading Eb2cOrder 1.0.0.18 -> 1.0.0.19', Zend_Log::INFO);
$installer = $this;
$installer->startSetup();

// Status and Status State tables
$statusTable      = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');
 
// The states are assigned to variables so that our big array is more legible.
$canceled   = Mage_Sales_Model_Order::STATE_CANCELED;
$complete   = Mage_Sales_Model_Order::STATE_COMPLETE;
$holded     = Mage_Sales_Model_Order::STATE_HOLDED;
$new        = Mage_Sales_Model_Order::STATE_NEW;
$processing = Mage_Sales_Model_Order::STATE_PROCESSING;

$eb2cStatusToMageStateMap = array(
	'Accepted'                                        => $processing,
	'Accessory BackOrder'                             => $processing,
	'Awaiting Chained Order Creation'                 => $processing,
	'Await Scheduling'                                => $processing,
	'Backordered'                                     => $holded,
	'Being Negotiated'                                => $processing,
	'Cancelled'                                       => $canceled,
	'Chained Order Created'                           => $processing,
	'Companion BackOrder'                             => $processing,
	'Created'                                         => $processing,
	'Draft Order Created'                             => $processing,
	'Dummy Return Created'                            => $new,
	'Fulfilled and Invoiced'                          => $complete,
	'GC Activated'                                    => $complete,
	'GC Activated higher than fulfilled and invoiced' => $complete,
	'GC Shipped'                                      => $complete,
	'GC Shipped higher than fulfilled and invoiced'   => $complete,
	'Included In Shipment'                            => $processing,
	'In-store Return Created'                         => $complete,
	'ISPU Order Cancelled'                            => $canceled,
	'OGC Activated'                                   => $complete,
	'OGC Line Created'                                => $processing,
	'OGC Shipped higher than fulfilled and invoiced'  => $complete,
	'OrderLine Invoiced'                              => $complete,
	'OrderLine Invoiced'                              => $processing,
	'Pending Pickup Cancel'                           => $processing,
	'Pickup Complete'                                 => $complete,
	'PO Shipped'                                      => $complete,
	'PO Shipped higher than fulfilled and invoiced'   => $complete,
	'Pre-Fulfilled Line Created'                      => $processing,
	'Pre-Sell Line Created'                           => $processing,
	'Ready For Pickup'                                => $holded,
	'Receipt Closed'                                  => $processing,
	'Received'                                        => $complete,
	'Released'                                        => $processing,
	'Reserved Awaiting Acceptance'                    => $processing,
	'Reserved'                                        => $processing,
	'Return Created'                                  => $complete,
	'Return Received'                                 => $complete,
	'Scheduled'                                       => $processing,
	'Shipped'                                         => $complete,
	'STS Order Cancelled'                             => $canceled,
	'Unexpected BackOrder'                            => $holded,
	'Unreceived'                                      => $processing,
	'Unscheduled'                                     => $processing,
	'Warranty Line Created'                           => $processing,
	'Warranty Line Processed'                         => $complete,
);

try{
	// New values for Order Status Translation:
	$newStatusSet	  = array();
	$newStatusStateSet = array();

	foreach ($eb2cStatusToMageStateMap as $eb2cStatusLabel => $mageState) {
		/*
		 * This conversion of a Label to a Code works here and here only - it was written for
		 * this specific array. It is not a genric Magento-code creator.
	 	 */
		$eb2cStatusCode = str_replace(' ', '_',
			strtolower(
				preg_replace( '/[^0-9a-zA-Z ]/', '', $eb2cStatusLabel)
			)
		);

		$newStatusSet[] = array(
			'status' => $eb2cStatusCode,
			'label'  => $eb2cStatusLabel,
		);

		$newStatusStateSet[] = array(
			'status'     => $eb2cStatusCode,
			'state'      => $mageState,
			'is_default' => 0,
		);
	}

	// Insert Status entries
	$installer->getConnection()->insertArray(
		$statusTable,
		array(
			'status',
			'label'
		),
		$newStatusSet
	);

	// Insert Status State entries:
	$installer->getConnection()->insertArray(
		$statusStateTable,
		array(
			'status',
			'state',
			'is_default'
		),
		$newStatusStateSet
	);
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();

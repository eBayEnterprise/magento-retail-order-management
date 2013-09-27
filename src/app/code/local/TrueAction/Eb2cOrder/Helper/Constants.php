<?php
/**
 *
 */
class TrueAction_Eb2cOrder_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const RETURN_FORMAT = 'xml';
	const SERVICE = 'orders';

	const CREATE_OPERATION = 'create';
	const CANCEL_OPERATION = 'cancel';

	const CREATE_DOM_ROOT_NODE_NAME = 'OrderCreateRequest';
	const CANCEL_DOM_ROOT_NODE_NAME = 'OrderCancelRequest';

	const ORDER_TYPE = 'SALES';
	const LEVEL_OF_SERVICE = 'REGULAR';

	const SHIPGROUP_DESTINATION_ID = 'dest_1';
	const SHIPGROUP_BILLING_ID = 'billing_1';
	const ORDER_HISTORY_PATH = 'sales/order/view/order_id/';
}

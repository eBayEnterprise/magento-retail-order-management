<?php
class TrueAction_Eb2cOrder_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_cancel_dom_root_node_name'  => 'eb2corder/api/cancel_dom_root_node_name',
		'api_cancel_operation'           => 'eb2corder/api/cancel_operation',
		'api_create_dom_root_node_name'  => 'eb2corder/api/create_dom_root_node_name',
		'api_create_operation'           => 'eb2corder/api/create_operation',
		'api_level_of_service'           => 'eb2corder/api/level_of_service',
		'api_order_history_path'         => 'eb2corder/api/order_history_path',
		'api_order_type'                 => 'eb2corder/api/order_type',
		'api_return_format'              => 'eb2corder/api/return_format',
		'api_search_operation'           => 'eb2corder/api_search_operation',
		'api_search_service'             => 'eb2corder/api_search_service',
		'api_service'                    => 'eb2corder/api/service',
		'api_ship_group_billing_id'      => 'eb2corder/api/ship_group_billing_id',
		'api_ship_group_destination_id'  => 'eb2corder/api/ship_group_destination_id',
		'api_xml_ns'                     => 'eb2ccore/api/xml_namespace',
		'eb2c_payments_enabled'          => 'eb2cpayment/enabled',
		'shipping_tax_class'             => 'eb2corder/shipping/tax_class',
		'status_feed_event_type'         => 'eb2ccore/feed/filetransfer_imports/order_status_feed/event_type',
		'status_feed_header_version'     => 'eb2ccore/feed/filetransfer_imports/order_status_feed/header_version',
		'status_feed_root_node_name'     => 'eb2ccore/feed/filetransfer_imports/order_status_feed/root_node_name',
		'status_feed_xsd'                => 'eb2ccore/feed/filetransfer_imports/order_status_feed/xsd',
		'status_feed_directory_config'   => 'eb2ccore/feed/filetransfer_imports/order_status_feed',
		'transactional_emailer'          => 'eb2ccore/email/transactional_emailer',
		'xsd_file_cancel'                => 'eb2corder/xsd/cancel_file',
		'xsd_file_create'                => 'eb2corder/xsd/create_file',
		'xsd_file_search'                => 'eb2corder/xsd/search_file',
	);
}

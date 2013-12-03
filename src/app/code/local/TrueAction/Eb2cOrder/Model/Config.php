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
		'status_feed_event_type'         => 'eb2corder/status_feed/event_type',
		'status_feed_file_pattern'       => 'eb2corder/status_feed/file_pattern',
		'status_feed_header_version'     => 'eb2corder/status_feed/header_version',
		'status_feed_local_path'         => 'eb2corder/status_feed/local_path',
		'status_feed_remote_path'        => 'eb2corder/status_feed/remote_path',
		'transactional_emailer'          => 'eb2ccore/email/transactional_emailer',
		'xsd_file_cancel'                => 'eb2corder/xsd/cancel_file',
		'xsd_file_create'                => 'eb2corder/xsd/create_file',
		'xsd_file_search'                => 'eb2corder/xsd/search_file',
	);
}

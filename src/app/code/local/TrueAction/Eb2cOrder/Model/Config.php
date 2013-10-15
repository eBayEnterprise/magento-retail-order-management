<?php
/**
 * @package    TrueAction_Eb2c
 */
class TrueAction_Eb2cOrder_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'developer_mode'             => 'eb2c/order/developer_mode',
		'developer_create_uri'       => 'eb2c/order/developer_create_uri',
		'developer_cancel_uri'       => 'eb2c/order/developer_cancel_uri',
		'eb2c_payments_enabled'      => 'eb2cpayment/general/enabled',
		'config_path'                => 'eb2c/order/config_path',
		'status_feed_local_path'     => 'eb2corder/status_feed/local_path',
		'status_feed_remote_path'    => 'eb2corder/status_feed/remote_path',
		'status_feed_file_pattern'   => 'eb2corder/status_feed/file_pattern',
		'status_feed_event_type'     => 'eb2corder/status_feed/event_type',
		'status_feed_header_version' => 'eb2corder/status_feed/header_version',
		'api_xml_ns'                 => 'eb2ccore/api/xml_namespace',
		'xsd_file_create'            => 'eb2c/order/xsd/create_file',
		'xsd_file_cancel'            => 'eb2c/order/xsd/cancel_file',
		'xsd_file_search'            => 'eb2c/order/xsd/search_file',
		'api_search_service'         => 'eb2c/order/api_search_service',
		'api_search_operation'       => 'eb2c/order/api_search_operation',
	);
}

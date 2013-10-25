<?php
class TrueAction_Eb2cOrder_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'eb2c_payments_enabled'      => 'eb2cpayment/enabled',
		'status_feed_local_path'     => 'eb2corder/status_feed/local_path',
		'status_feed_remote_path'    => 'eb2corder/status_feed/remote_path',
		'status_feed_file_pattern'   => 'eb2corder/status_feed/file_pattern',
		'status_feed_event_type'     => 'eb2corder/status_feed/event_type',
		'status_feed_header_version' => 'eb2corder/status_feed/header_version',
		'api_xml_ns'                 => 'eb2ccore/api/xml_namespace',
		'xsd_file_create'            => 'eb2corder/xsd/create_file',
		'xsd_file_cancel'            => 'eb2corder/xsd/cancel_file',
		'xsd_file_search'            => 'eb2corder/xsd/search_file',
		'api_search_service'         => 'eb2corder/api_search_service',
		'api_search_operation'       => 'eb2corder/api_search_operation',
	);
}

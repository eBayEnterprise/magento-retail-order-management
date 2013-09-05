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
		'eb2c_payments_enabled'      => 'eb2c/order/payments_enabled',
		'status_feed_local_path'     => 'eb2c/order/status_feed/local_path',
		'status_feed_remote_path'    => 'eb2c/order/status_feed/remote_path',
		'file_transfer_config_path'  => 'eb2c/order/status_feed/file_transfer_config_path',
		'status_feed_event_type'     => 'eb2c/order/status_feed/event_type',
		'status_feed_header_version' => 'eb2c/order/status_feed/header_version',
	);
}

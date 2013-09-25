<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_xml_ns'        => 'eb2c/product/api_xml_ns',
		'api_env'           => 'eb2c/product/api_env',
		'api_region'        => 'eb2c/product/api_region',
		'api_version'       => 'eb2c/product/api_version',
		'api_service'       => 'eb2c/product/api_service',
		'api_uri_format'    => 'eb2c/product/api_uri_format',
		'api_return_format' => 'eb2c/product/api_return_format',
		'config_path'       => 'eb2c/product/config_path',
		'developer_mode'    => 'eb2c/product/developer_mode',

		'item_feed_local_path'           => 'eb2cproduct/item_master_feed/local_path',
		'item_feed_remote_received_path' => 'eb2cproduct/item_master_feed/remote_path',
		'item_feed_file_pattern'         => 'eb2cproduct/item_master_feed/file_pattern',
		'item_feed_event_type'           => 'eb2cproduct/item_master_feed/event_type',
		'item_feed_header_version'       => 'eb2cproduct/item_master_feed/header_version',

		'content_feed_local_path'           => 'eb2cproduct/content_master_feed/local_path',
		'content_feed_remote_received_path' => 'eb2cproduct/content_master_feed/remote_path',
		'content_feed_file_pattern'         => 'eb2cproduct/content_master_feed/file_pattern',
		'content_feed_event_type'           => 'eb2cproduct/content_master_feed/event_type',
		'content_feed_header_version'       => 'eb2cproduct/content_master_feed/header_version',

		'i_ship_feed_local_path'           => 'eb2cproduct/i_ship_feed/local_path',
		'i_ship_feed_remote_received_path' => 'eb2cproduct/i_ship_feed/remote_path',
		'i_ship_feed_file_pattern'         => 'eb2cproduct/i_ship_feed/file_pattern',
		'i_ship_feed_event_type'           => 'eb2cproduct/i_ship_feed/event_type',
		'i_ship_feed_header_version'       => 'eb2cproduct/i_ship_feed/header_version',

		'pricing_feed_local_path'           => 'eb2cproduct/item_pricing_feed/local_path',
		'pricing_feed_remote_received_path' => 'eb2cproduct/item_pricing_feed/remote_path',
		'pricing_feed_file_pattern'         => 'eb2cproduct/item_pricing_feed/file_pattern',
		'pricing_feed_event_type'           => 'eb2cproduct/item_pricing_feed/event_type',
		'pricing_feed_header_version'       => 'eb2cproduct/item_pricing_feed/header_version',

		'image_feed_local_path'           => 'eb2cproduct/image_master_feed/local_path',
		'image_feed_remote_received_path' => 'eb2cproduct/image_master_feed/remote_path',
		'image_feed_file_pattern'         => 'eb2cproduct/image_master_feed/file_pattern',
		'image_feed_event_type'           => 'eb2cproduct/image_master_feed/event_type',
		'image_feed_header_version'       => 'eb2cproduct/image_master_feed/header_version',
	);
}

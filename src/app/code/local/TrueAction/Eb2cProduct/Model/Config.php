<?php
class TrueAction_Eb2cProduct_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_return_format'                  => 'eb2cproduct/api_return_format',
		'api_service'                        => 'eb2cproduct/api_service',
		'api_xml_ns'                         => 'eb2ccore/api/xml_namespace',

		'content_feed_event_type'            => 'eb2cproduct/content_master_feed/event_type',
		'content_feed_file_pattern'          => 'eb2cproduct/content_master_feed/file_pattern',
		'content_feed_header_version'        => 'eb2cproduct/content_master_feed/header_version',
		'content_feed_local_path'            => 'eb2cproduct/content_master_feed/local_path',
		'content_feed_remote_received_path'  => 'eb2cproduct/content_master_feed/remote_path',

		'dummy_description'                   => 'eb2cproduct/dummy/description',
		'dummy_in_stock'                      => 'eb2cproduct/dummy/in_stock',
		'dummy_manage_stock'                  => 'eb2cproduct/dummy/manage_stock',
		'dummy_price'                         => 'catalog/price/default_product_price',
		'dummy_short_description'             => 'eb2cproduct/dummy/short_description',
		'dummy_stock_quantity'                => 'eb2cproduct/dummy/stock_quantity',
		'dummy_type_id'                       => 'eb2cproduct/dummy/type_id',
		'dummy_weight'                        => 'eb2cproduct/dummy/weight',

		'i_ship_feed_event_type'              => 'eb2cproduct/i_ship_feed/event_type',
		'i_ship_feed_file_pattern'            => 'eb2cproduct/i_ship_feed/file_pattern',
		'i_ship_feed_header_version'          => 'eb2cproduct/i_ship_feed/header_version',
		'i_ship_feed_local_path'              => 'eb2cproduct/i_ship_feed/local_path',
		'i_ship_feed_remote_received_path'    => 'eb2cproduct/i_ship_feed/remote_path',

		'image_feed_event_type'               => 'eb2cproduct/image_master_feed/event_type',
		'image_feed_file_pattern'             => 'eb2cproduct/image_master_feed/file_pattern',
		'image_feed_header_version'           => 'eb2cproduct/image_master_feed/header_version',
		'image_feed_local_path'               => 'eb2cproduct/image_master_feed/local_path',
		'image_feed_remote_received_path'     => 'eb2cproduct/image_master_feed/remote_path',

		'item_feed_event_type'                => 'eb2cproduct/item_master_feed/event_type',
		'item_feed_file_pattern'              => 'eb2cproduct/item_master_feed/file_pattern',
		'item_feed_header_version'            => 'eb2cproduct/item_master_feed/header_version',
		'item_feed_local_path'                => 'eb2cproduct/item_master_feed/local_path',
		'item_feed_remote_received_path'      => 'eb2cproduct/item_master_feed/remote_path',

		'pricing_feed_event_type'             => 'eb2cproduct/item_pricing_feed/event_type',
		'pricing_feed_file_pattern'           => 'eb2cproduct/item_pricing_feed/file_pattern',
		'pricing_feed_header_version'         => 'eb2cproduct/item_pricing_feed/header_version',
		'pricing_feed_local_path'             => 'eb2cproduct/item_pricing_feed/local_path',
		'pricing_feed_remote_received_path'   => 'eb2cproduct/item_pricing_feed/remote_path',

		'processor_delete_batch_size'         => 'eb2cproduct/processor_delete_batch_size',
		'processor_max_total_entries'         => 'eb2cproduct/processor_max_total_entries',
		'processor_update_batch_size'         => 'eb2cproduct/processor_update_batch_size',

		'attributes_code_list'                => 'eb2cproduct/attributes_code_list',

		'ext_keys'                            => 'eb2cproduct/feed/map/ext_keys',
		'ext_keys_bool'                       => 'eb2cproduct/feed/map/ext_keys_bool',
	);
}

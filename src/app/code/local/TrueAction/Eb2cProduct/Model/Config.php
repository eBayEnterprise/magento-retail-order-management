<?php
class TrueAction_Eb2cProduct_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_return_format'                  => 'eb2cproduct/api_return_format',
		'api_service'                        => 'eb2cproduct/api_service',
		'api_xml_ns'                         => 'eb2ccore/api/xml_namespace',

		'content_feed'                       => 'eb2ccore/feed/filetransfer_imports/content_master',
		'content_feed_event_type'            => 'eb2ccore/feed/filetransfer_imports/content_master/event_type',

		'dummy_description'                   => 'eb2cproduct/dummy/description',
		'dummy_in_stock'                      => 'eb2cproduct/dummy/in_stock',
		'dummy_manage_stock'                  => 'eb2cproduct/dummy/manage_stock',
		'dummy_price'                         => 'catalog/price/default_product_price',
		'dummy_short_description'             => 'eb2cproduct/dummy/short_description',
		'dummy_stock_quantity'                => 'eb2cproduct/dummy/stock_quantity',
		'dummy_type_id'                       => 'eb2cproduct/dummy/type_id',
		'dummy_weight'                        => 'eb2cproduct/dummy/weight',

		'i_ship_feed'                         => 'eb2ccore/feed/filetransfer_imports/i_ship',
		'i_ship_feed_event_type'              => 'eb2ccore/feed/filetransfer_imports/i_ship/event_type',

		'image_feed'                          => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox',
		'image_feed_event_type'               => 'eb2cproduct/image_master_feed/event_type',
		'image_feed_file_pattern'             => 'eb2cproduct/image_master_feed/file_pattern',
		'image_feed_header_version'           => 'eb2cproduct/image_master_feed/header_version',

		'item_feed'                           => 'eb2ccore/feed/filetransfer_imports/item_master',
		'item_feed_event_type'                => 'eb2ccore/feed/filetransfer_imports/item_master/event_type',

		'pim_export_feed'                     => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox',
		'pim_export_feed_event_type'          => 'eb2cproduct/pim_export_feed/outbound/message_header/event_type',
		'pim_export_feed_cutoff_date'          => 'eb2cproduct/pim_export_feed/cutoff_date',
		'pim_export_xsd'                      => 'eb2cproduct/pim_export_feed/xsd',

		'pricing_feed'                        => 'eb2ccore/feed/filetransfer_imports/item_pricing',
		'pricing_feed_event_type'             => 'eb2ccore/feed/filetransfer_imports/item_pricing/event_type',

		'processor_delete_batch_size'         => 'eb2cproduct/processor_delete_batch_size',
		'processor_max_total_entries'         => 'eb2cproduct/processor_max_total_entries',
		'processor_update_batch_size'         => 'eb2cproduct/processor_update_batch_size',

		'attributes_code_list'                => 'eb2cproduct/attributes_code_list',

		'ext_keys'                            => 'eb2cproduct/feed/map/ext_keys',
		'ext_keys_bool'                       => 'eb2cproduct/feed/map/ext_keys_bool',

		'link_types_es_accessory'             => 'eb2cproduct/feed/related_link_types/es_accessory',
		'link_types_es_crossselling'          => 'eb2cproduct/feed/related_link_types/es_crossselling',
		'link_types_es_upselling'             => 'eb2cproduct/feed/related_link_types/es_upselling',
	);
}

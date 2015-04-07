<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Catalog_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_return_format'                  => 'ebayenterprise_catalog/api_return_format',
		'api_service'                        => 'ebayenterprise_catalog/api_service',
		'api_xml_ns'                         => 'eb2ccore/api/xml_namespace',

		'content_feed'                       => 'eb2ccore/feed/filetransfer_imports/content_master',
		'content_feed_event_type'            => 'eb2ccore/feed/filetransfer_imports/content_master/event_type',

		'dummy_description'                   => 'ebayenterprise_catalog/dummy/description',
		'dummy_in_stock'                      => 'ebayenterprise_catalog/dummy/in_stock',
		'dummy_manage_stock'                  => 'ebayenterprise_catalog/dummy/manage_stock',
		'dummy_price'                         => 'catalog/price/default_product_price',
		'dummy_short_description'             => 'ebayenterprise_catalog/dummy/short_description',
		'dummy_stock_quantity'                => 'ebayenterprise_catalog/dummy/stock_quantity',
		'dummy_type_id'                       => 'ebayenterprise_catalog/dummy/type_id',
		'dummy_weight'                        => 'ebayenterprise_catalog/dummy/weight',

		'i_ship_feed'                         => 'eb2ccore/feed/filetransfer_imports/i_ship',
		'i_ship_feed_event_type'              => 'eb2ccore/feed/filetransfer_imports/i_ship/event_type',

		'image_feed'                          => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox',
		'image_feed_event_type'               => 'eb2ccore/feed/filetransfer_exports/image_master/outbound/message_header/event_type',
		'image_export_filename_format'        => 'eb2ccore/feed/filetransfer_exports/image_master/filename_format',
		'image_export_xsd'                    => 'eb2ccore/feed/filetransfer_exports/image_master/xsd',
		'image_export_last_run_datetime'      => 'eb2ccore/feed/filetransfer_exports/image_master/last_run_datetime',

		'item_feed'                           => 'eb2ccore/feed/filetransfer_imports/item_master',
		'item_feed_event_type'                => 'eb2ccore/feed/filetransfer_imports/item_master/event_type',

		'pim_export_feed'                     => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox',
		'export_feed_config'                  => 'ebayenterprise_catalog/feed_pim_mapping',
		'pim_export_feed_event_type'          => 'ebayenterprise_catalog/pim_export_feed/outbound/message_header/event_type',
		'pim_export_feed_cutoff_date'         => 'ebayenterprise_catalog/pim_export_feed/cutoff_date',
		'pim_export_filename_format'          => 'ebayenterprise_catalog/pim_export_feed/filename_format',
		'pim_export_xsd'                      => 'ebayenterprise_catalog/pim_export_feed/xsd',

		'pricing_feed'                        => 'eb2ccore/feed/filetransfer_imports/item_pricing',
		'pricing_feed_event_type'             => 'eb2ccore/feed/filetransfer_imports/item_pricing/event_type',

		'attributes_code_list'                => 'ebayenterprise_catalog/attributes_code_list',
		'read_only_attributes'                => 'ebayenterprise_catalog/readonly_attributes',

		'ext_keys'                            => 'ebayenterprise_catalog/feed/map/ext_keys',
		'ext_keys_bool'                       => 'ebayenterprise_catalog/feed/map/ext_keys_bool',

		'link_types_es_accessory'             => 'ebayenterprise_catalog/feed/related_link_types/es_accessory',
		'link_types_es_crossselling'          => 'ebayenterprise_catalog/feed/related_link_types/es_crossselling',
		'link_types_es_upselling'             => 'ebayenterprise_catalog/feed/related_link_types/es_upselling',
	);
}

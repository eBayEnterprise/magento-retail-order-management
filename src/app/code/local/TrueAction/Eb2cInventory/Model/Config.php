<?php
class TrueAction_Eb2cInventory_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{

	protected $_configPaths = array(
		'allocation_expired'                    => 'eb2c/inventory/allocation_expired',
		'quantity_api_uri'                      => 'eb2c/inventory/quantity_api_uri',
		'inventory_detail_uri'                  => 'eb2c/inventory/inventory_detail_uri',
		'allocation_uri'                        => 'eb2c/inventory/allocation_uri',
		'rollback_allocation_uri'               => 'eb2c/inventory/rollback_allocation_uri',
		'config_path'                           => 'eb2c/inventory/config_path',
		'feed_local_path'                       => 'eb2cinventory/inventory_feed/local_path',
		'feed_remote_received_path'             => 'eb2cinventory/inventory_feed/remote_path',
		'feed_file_pattern'                     => 'eb2cinventory/inventory_feed/file_pattern',
		'feed_event_type'                       => 'eb2cinventory/inventory_feed/event_type',
		'feed_header_version'                   => 'eb2cinventory/inventory_feed/header_version',
		'api_xml_ns'                            => 'eb2ccore/api/xml_namespace',
		'api_service'                           => 'eb2c/inventory/api_service',
		'api_opt_inventory_qty'                 => 'eb2c/inventory/api_opt_inventory_qty',
		'api_opt_inventory_details'             => 'eb2c/inventory/api_opt_inventory_details',
		'api_opt_inventory_allocation'          => 'eb2c/inventory/api_opt_inventory_allocation',
		'api_opt_inventory_rollback_allocation' => 'eb2c/inventory/api_opt_inventory_rollback_allocation',
		'api_return_format'                     => 'eb2c/inventory/api_return_format',
		'xsd_file_allocation'                   => 'eb2c/inventory/xsd/allocation_file',
		'xsd_file_details'                      => 'eb2c/inventory/xsd/details_file',
		'xsd_file_quantity'                     => 'eb2c/inventory/xsd/quantity_file',
		'xsd_file_rollback'                     => 'eb2c/inventory/xsd/rollback_file',
	);
}

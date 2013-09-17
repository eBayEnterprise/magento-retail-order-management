<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{

	protected $_configPaths = array(
		'allocation_expired' => 'eb2c/inventory/allocation_expired',
		'developer_mode' => 'eb2c/inventory/developer_mode',
		'quantity_api_uri' => 'eb2c/inventory/quantity_api_uri',
		'inventory_detail_uri' => 'eb2c/inventory/inventory_detail_uri',
		'allocation_uri' => 'eb2c/inventory/allocation_uri',
		'rollback_allocation_uri' => 'eb2c/inventory/rollback_allocation_uri',
		'config_path' => 'eb2c/inventory/config_path',
		'feed_local_path' => 'eb2cinventory/inventory_feed/local_path',
		'feed_remote_received_path' => 'eb2cinventory/inventory_feed/remote_path',
		'feed_file_pattern' => 'eb2cinventory/inventory_feed/file_pattern',
		'feed_event_type' => 'eb2cinventory/inventory_feed/event_type',
		'feed_header_version' => 'eb2cinventory/inventory_feed/header_version',
		'api_xml_ns' => 'eb2c/inventory/api_xml_ns',
		'api_env' => 'eb2c/inventory/api_env',
		'api_region' => 'eb2c/inventory/api_region',
		'api_version' => 'eb2c/inventory/api_version',
		'api_service' => 'eb2c/inventory/api_service',
		'api_opt_inventory_qty' => 'eb2c/inventory/api_opt_inventory_qty',
		'api_opt_inventory_details' => 'eb2c/inventory/api_opt_inventory_details',
		'api_opt_inventory_allocation' => 'eb2c/inventory/api_opt_inventory_allocation',
		'api_opt_inventory_rollback_allocation' => 'eb2c/inventory/api_opt_inventory_rollback_allocation',
		'api_uri_format' => 'eb2c/inventory/api_uri_format',
		'api_return_format' => 'eb2c/inventory/api_return_format',
	);
}

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
		'feed_local_path' => 'eb2c/inventory/feed_local_path',
		'feed_remote_received_path' => 'eb2c/inventory/feed_remote_received_path',
		'config_path' => 'eb2c/inventory/config_path',
		'feed_event_type' => 'eb2c/inventory/feed_event_type',
		'feed_header_version' => 'eb2c/inventory/feed_header_version',
	);
}

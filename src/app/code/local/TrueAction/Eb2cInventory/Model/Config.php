<?php
class TrueAction_Eb2cInventory_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'allocation_expired'                    => 'eb2cinventory/allocation_expired',
		'api_opt_inventory_allocation'          => 'eb2cinventory/api_opt_inventory_allocation',
		'api_opt_inventory_details'             => 'eb2cinventory/api_opt_inventory_details',
		'api_opt_inventory_qty'                 => 'eb2cinventory/api_opt_inventory_qty',
		'api_opt_inventory_rollback_allocation' => 'eb2cinventory/api_opt_inventory_rollback_allocation',
		'api_return_format'                     => 'eb2cinventory/api_return_format',
		'api_service'                           => 'eb2cinventory/api_service',
		'api_xml_ns'                            => 'eb2ccore/api/xml_namespace',
		'feed_event_type'                       => 'eb2cinventory/feed/event_type',
		'feed_file_pattern'                     => 'eb2cinventory/feed/file_pattern',
		'feed_header_version'                   => 'eb2cinventory/feed/header_version',
		'feed_local_path'                       => 'eb2cinventory/feed/local_path',
		'feed_remote_received_path'             => 'eb2cinventory/feed/remote_path',
		'xsd_file_allocation'                   => 'eb2cinventory/xsd/allocation_file',
		'xsd_file_details'                      => 'eb2cinventory/xsd/details_file',
		'xsd_file_quantity'                     => 'eb2cinventory/xsd/quantity_file',
		'xsd_file_rollback'                     => 'eb2cinventory/xsd/rollback_file',
	);
}

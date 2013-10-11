<?php
/**
 * To the best of my knowledge this is the only outbound file.
 *
 */
class TrueAction_Eb2cProduct_Model_Image_Export_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'is_enabled'                       => 'eb2c/product/image_export/enabled',
		'api_xml_ns'                       => 'eb2ccore/api/xml_namespace',
		'xsd_file_image_export_validation' => 'eb2c/product/image_export/xsd/image_feed',

		/* Fields for the MessageHeader element: */
		'standard'         => 'eb2c/product/image_export/message_header/standard',
		'header_version'   => 'eb2c/product/image_export/message_header/header_version',
		'source_id'        => 'eb2c/product/image_export/message_header/source_data/id',
		'source_type'      => 'eb2c/product/image_export/message_header/source_data/type',
		'destination_id'   => 'eb2c/product/image_export/message_header/destination_data/id',
		'destination_type' => 'eb2c/product/image_export/message_header/destination_data/type',
		'event_type'       => 'eb2c/product/image_export/message_header/event_type',
		'message_id'       => 'eb2c/product/image_export/message_header/message_data/message_id',
		'correlation_id'   => 'eb2c/product/image_export/message_header/message_data/correlation_id',
	);
}

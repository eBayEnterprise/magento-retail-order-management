<?php
/**
 * To the best of my knowledge this is the only outbound file.
 *
 */
class TrueAction_Eb2cProduct_Model_Image_Export_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'is_enabled'              => 'eb2cproduct/image_export/enabled',
		'include_empty_galleries' => 'eb2cproduct/image_export/include_empty_galleries',
		'remote_path'             => 'eb2cproduct/image_export/remote_path',
		'filename_format'         => 'eb2cproduct/image_export/filename_format',
		'api_xml_ns'              => 'eb2cproduct/image_export/api/xml_namespace',
		'xsd_file_image_export'   => 'eb2cproduct/image_export/xsd',
		'local_path'              => 'eb2cproduct/image_export/local_path',

		/* Fields for the MessageHeader element: */
		'standard'         => 'eb2cproduct/image_export/message_header/standard',
		'header_version'   => 'eb2cproduct/image_export/message_header/version',
		'source_id'        => 'eb2cproduct/image_export/message_header/source_data_id',
		'source_type'      => 'eb2cproduct/image_export/message_header/source_data_type',
		'destination_id'   => 'eb2cproduct/image_export/message_header/destination_data_id',
		'destination_type' => 'eb2cproduct/image_export/message_header/destination_data_type',
		'event_type'       => 'eb2cproduct/image_export/message_header/event_type',
		'message_id'       => 'eb2cproduct/image_export/message_header/message_data_id',
		'correlation_id'   => 'eb2cproduct/image_export/message_header/message_data_correlation_id',
	);
}

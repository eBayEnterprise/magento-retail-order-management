<?php
/**
 *
 */
class TrueAction_Eb2cMedia_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'config_path'                => 'eb2cmedia/config_path',
		'images_feed_local_path'     => 'eb2cmedia/images_feed/local_path',
		'images_feed_remote_path'    => 'eb2cmedia/images_feed/remote_path',
		'images_feed_file_pattern'   => 'eb2cmedia/images_feed/file_pattern',
		'images_feed_event_type'     => 'eb2cmedia/images_feed/event_type',
		'images_feed_header_version' => 'eb2cmedia/images_feed/header_version',
	);
}

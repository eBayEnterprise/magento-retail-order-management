<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'developer_mode' => 'eb2c/product/developer_mode',
		'feed_local_path' => 'eb2c/product/feed_local_path',
		'feed_remote_received_path' => 'eb2c/product/feed_remote_received_path',
		'config_path' => 'eb2c/product/config_path',
		'feed_event_type' => 'eb2c/product/feed_event_type',
		'feed_header_version' => 'eb2c/product/feed_header_version',
	);
}

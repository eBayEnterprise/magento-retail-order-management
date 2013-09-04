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
		'config_path' => 'eb2c/product/config_path',
		'item_feed_local_path' => 'eb2c/product/item_feed_local_path',
		'item_feed_remote_received_path' => 'eb2c/product/item_feed_remote_received_path',
		'item_feed_event_type' => 'eb2c/product/item_feed_event_type',
		'item_feed_header_version' => 'eb2c/product/item_feed_header_version',
		'content_feed_local_path' => 'eb2c/product/content_feed_local_path',
		'content_feed_remote_received_path' => 'eb2c/product/content_feed_remote_received_path',
		'content_feed_event_type' => 'eb2c/product/content_feed_event_type',
		'content_feed_header_version' => 'eb2c/product/content_feed_header_version',
	);
}

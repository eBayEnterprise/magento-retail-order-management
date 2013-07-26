<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'stored_value_balance_api_uri' => 'eb2c/payment/stored_value_balance_api_uri',
		'stored_value_redeem_api_uri' => 'eb2c/payment/stored_value_redeem_api_uri',
		'stored_value_redeem_void_api_uri' => 'eb2c/payment/stored_value_redeem_void_api_uri',
		'developer_mode' => 'eb2c/payment/developer_mode',
	);
}

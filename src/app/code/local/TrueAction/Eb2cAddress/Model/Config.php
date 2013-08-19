<?php

/**
 * Configuration model to be registered with the eb2c core config helper.
 */
class TrueAction_Eb2cAddress_Model_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'is_validation_enabled' => 'eb2caddress/general/enabled',
		'max_address_suggestions' => 'eb2caddress/general/max_suggestions',
		'api_namespace' => 'eb2caddress/api/namespace_uri',
		'address_format_full' => 'eb2caddress/address_suggestion_templates/full_html',
		'address_format_address_only' => 'eb2caddress/address_suggestion_templates/address_only_html',
	);
}

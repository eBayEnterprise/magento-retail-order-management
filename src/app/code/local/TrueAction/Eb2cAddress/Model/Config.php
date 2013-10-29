<?php
/**
 * Configuration model to be registered with the eb2c core config helper.
 */
class TrueAction_Eb2cAddress_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'address_format_address_only' => 'eb2ccore/address/suggestion_templates/address_only_html',
		'address_format_full'         => 'eb2ccore/address/suggestion_templates/full_html',
		'api_namespace'               => 'eb2ccore/api/xml_namespace',
		'is_validation_enabled'       => 'eb2ccore/address/enabled',
		'max_address_suggestions'     => 'eb2ccore/address/max_suggestions',
		'xsd_file_address_validation' => 'eb2ccore/address/xsd/file',
	);
}

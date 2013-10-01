<?php

/**
 * Configuration model to be registered with the eb2c core config helper.
 */
class TrueAction_Eb2cTax_Model_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_namespace' => 'eb2ccore/api/xml_namespace',
		'tax_apply_after_discount' => 'eb2ctax/calculation/apply_after_discount',
		'tax_vat_inclusive_pricing' => 'eb2ctax/calculation/vat_inclusive_pricing',
		'tax_duty_rate_code' => 'eb2ctax/defaults/duty_amount_code',
	);
}

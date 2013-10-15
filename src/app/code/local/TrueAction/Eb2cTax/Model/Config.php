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
		'xsd_file_tax_duty_fee_quote_request' => 'eb2ctax/default/xsd/quote_request_file',
		'admin_origin_line1' => 'eb2ctax/admin_origin/line1',
		'admin_origin_line2' => 'eb2ctax/admin_origin/line2',
		'admin_origin_line3' => 'eb2ctax/admin_origin/line3',
		'admin_origin_line4' => 'eb2ctax/admin_origin/line4',
		'admin_origin_city' => 'eb2ctax/admin_origin/city',
		'admin_origin_main_division' => 'eb2ctax/admin_origin/main_division',
		'admin_origin_country_code' => 'eb2ctax/admin_origin/country_code',
		'admin_origin_postal_code' => 'eb2ctax/admin_origin/postal_code',
	);
}

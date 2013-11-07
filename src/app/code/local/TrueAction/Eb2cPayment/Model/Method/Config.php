<?php
class TrueAction_Eb2cPayment_Model_Method_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'pbridge_active'              => 'payment/pbridge/active',
		'pbridge_merchant_code'       => 'payment/pbridge/merchantcode',
		'pbridge_merchant_key'        => 'payment/pbridge/merchantkey',
		'pbridge_gateway_url'         => 'payment/pbridge/gatewayurl',
		'pbridge_transfer_key'        => 'payment/pbridge/transferkey',
		'ebc_pbridge_active'          => 'payment/pbridge_eb2cpayment_cc/active',
		'ebc_pbridge_title'           => 'payment/pbridge_eb2cpayment_cc/title',
	);
}

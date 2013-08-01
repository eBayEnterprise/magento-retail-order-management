<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const PAYMENT_XMLNS = 'http://api.gsicommerce.com/schema/payment/1.0';
	const SERVICE = 'payments';
	const OPT_STORED_VALUE_BALANCE = 'storevalue/balance/GS';
	const OPT_STORED_VALUE_REDEEM = 'storevalue/redeem/GS';
	const OPT_STORED_VALUE_REDEEM_VOID = 'storevalue/redeemvoid/GS';
	const OPT_PAYPAL_SET_EXPRESS_CHECKOUT = 'paypal/setExpress';
	const OPT_PAYPAL_GET_EXPRESS_CHECKOUT = 'paypal/getExpress';
	const OPT_PAYPAL_DO_EXPRESS_CHECKOUT = 'paypal/doExpress';
	const OPT_PAYPAL_DO_AUTHORIZATION = 'paypal/doAuth';
	const OPT_PAYPAL_DO_VOID = 'paypal/void';
	const RETURN_FORMAT = 'xml';
}

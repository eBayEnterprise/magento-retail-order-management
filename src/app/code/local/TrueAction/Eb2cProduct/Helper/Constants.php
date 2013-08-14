<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const ENV = 'developer';
	const REGION = 'na';
	const VERSION = 'v1.10';
	const SERVICE = 'product';
	const URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const RETURN_FORMAT = 'xml';
}

/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

(function (Validation) {

	/**
	 * Validate that the value is a potentially valid language code.
	 * Intentionally less strict that possible. Ok to pass some invalid values.
	 * Not ok to fail any valid values. Validates an approximation of BCP47 only
	 * much less strict.
	 *
	 * @param {string}
	 * @return {boolean}
	 */
	function validateLanguageCode(value) {
		return /^(?:x|i|[a-z]{2,8})(?:-[a-z0-9]{1,8})*$/i.test(value);
	}

	Validation.add('validate-language-code', 'Please enter a valid language code.', validateLanguageCode);

})(Validation);

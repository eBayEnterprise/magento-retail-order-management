<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Helper_Data extends Mage_Payment_Helper_Data
{
	/**
	 * sanitizeData
	 *
	 * @param  string $data Data
	 * @return string The sanitized string
	 */
	public function sanitizeData($data)
	{
		$bad = array(' ', '-', '_', '.', ';', '/', '|');
		$sanitized = str_replace($bad, '', $data);

		return $sanitized;
	}
}

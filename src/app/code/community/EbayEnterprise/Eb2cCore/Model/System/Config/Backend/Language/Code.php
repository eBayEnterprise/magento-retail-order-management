<?php
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

/**
 * Clean the provided language code value so it can be consistent with BCP47
 */
class EbayEnterprise_Eb2cCore_Model_System_Config_Backend_Language_Code extends Mage_Core_Model_Config_Data
{
	const INVALID_LANGUAGE_CODE_MESSAGE = 'EbayEnterprise_Eb2cCore_System_Config_Language_Code_Invalid';

	/** @var EbayEnteprise_Eb2cCore_Helper_Languages */
	protected $_langHelper;
	/** @var EbayEnterprise_Eb2cCore_Helper */
	protected $_coreHelper;

	protected function _construct()
	{
		parent::_construct();
		list($this->_langHelper, $this->_coreHelper) = $this->_checkTypes(
			$this->getData('language_helper') ?: Mage::helper('eb2ccore/languages'),
			$this->getData('core_helper') ?: Mage::helper('eb2ccore')
		);
	}

	/**
	 * @param EbayEnterprise_Eb2cCore_Helper_Languages
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Helper_Languages $langHelper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	) {
		return [$langHelper, $coreHelper];
	}

	/**
	 * Attempt to canonicalize and validate the provided language code. The
	 * "canonical" version of the langauge code will be all lowercase. This
	 * value is compared to xml:lang attributes in feeds being processed, which
	 * are also translated to all lowercase during processing. Validation
	 * checks that the canonical language code is a valid value for an xml:lang
	 * attribute.
	 *
	 * @return self
	 */
	public function _beforeSave()
	{
		parent::_beforeSave();
		if ($this->isValueChanged()) {
			$value = strtolower(trim($this->getValue()));
			$this->_validateLanguageCode($value)
				->setValue($value);
		}
		return $this;
	}

	/**
	 * Check that the language code value is a valid language code. If it
	 * is not, throw an exception.
	 *
	 * @param string
	 * @return self
	 * @throws EbayeEnterprise_Eb2cCore_Exception If value is not a valid language code.
	 */
	protected function _validateLanguageCode($langCode)
	{
		if (!$this->_langHelper->validateLanguageCode($langCode)) {
			throw Mage::exception('EbayEnterprise_Eb2cCore', $this->_coreHelper->__(self::INVALID_LANGUAGE_CODE_MESSAGE));
		}
		return $this;
	}
}

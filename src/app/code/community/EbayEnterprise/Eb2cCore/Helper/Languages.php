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

class EbayEnterprise_Eb2cCore_Helper_Languages extends Mage_Core_Helper_Abstract
{
	// Schema file defining a "root" node with an xml:lang attribute. Used
	// to validate language codes.
	const LANGUAGE_CODE_SCHEMA = 'LanguageCodeValidation.xsd';

	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	public function __construct($args=[])
	{
		list($this->_coreHelper) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'core_helper', Mage::helper('eb2ccore'))
		);
	}

	/**
	 * Type checks for constructor args array.
	 *
	 * @param EbayEnterprise_Eb2cCore_Helper_Data
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	) {
		return [$coreHelper];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param array
	 * @param string|int
	 * @param mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Return an array of stores and attach a language code to them, Varien_Object style
	 * @param string $langCode (optional) if passed, only stores using that langCode are returned.
	 * @return array of Mage_Core_Model_Store, keyed by StoreId. Each store has a new magic getter 'getLanguageCode()'
	 */
	public function getStores($langCode=null)
	{
		$stores = array();
		foreach (Mage::app()->getWebsites() as $website) {
			$stores = array_replace($stores, $this->getWebsiteStores($website, $langCode));
		}
		return $stores;
	}
	/**
	 * Return an array of stores for the given website and attach a language code to them, Varien_Object style
	 * @param mixed  $website  the website to get stores from.
	 * @param string $langCode (optional) if passed, only stores using that langCode are returned.
	 * @return array of Mage_Core_Model_Store, keyed by StoreId. Each store has a new magic getter 'getLanguageCode()'
	 */
	public function getWebsiteStores($website, $langCode=null)
	{
		$stores = array();
		$config = Mage::helper('eb2ccore')->getConfigModel();
		$website = Mage::app()->getWebsite($website);
		foreach ($website->getGroups() as $group) {
			foreach ($group->getStores() as $store) {
				$storeId = $store->getStoreId();
				$config->setStore($storeId);
				if (!$langCode || ($langCode === $config->languageCode)) {
					$store->setLanguageCode($config->languageCode);
					$stores[$storeId] = $store;
				}
			}
		}
		return $stores;
	}
	/**
	 * Get a simple array of all language codes used in this installation
	 * @return array
	 */
	public function getLanguageCodesList()
	{
		$languages = array();
		foreach ($this->getStores() as $store) {
			$languages[] = $store->getLanguageCode();
		}
		return array_unique($languages);
	}

	/**
	 * Check for the language code to be a valid xml:lang. Uses a simple xsd
	 * and schema validation to ensure the value is valid. This will use the
	 * same schema definitions for xml:lang attributes as those used in the
	 * feed xsds, so any values that are valid in the feeds should also be
	 * valid in this case.
	 *
	 * @param string
	 * @return bool
	 */
	public function validateLanguageCode($languageCode)
	{
		// Swallow libxml warnings. In this tightly controlled case, all that
		// can be expected to be invalid in the XML is the language code so
		// the warnings won't tell us anything more useful anyway.
		set_error_handler(function () {});
		// Need to capture results instead of just returning them so the error
		// handler used to swallow the libxml errors can be removed.
		$isValid = $this->_buildLanguageCheckDocument($languageCode)
			->schemaValidate($this->_getLanguageCodeValidationSchemaFile());
		restore_error_handler();
		return $isValid;
	}

	/**
	 * Build an DOMDocument that can be schema validated to check for the
	 * language code to be a valid xml:lang.
	 *
	 * @param string
	 * @return DOMDocument
	 */
	protected function _buildLanguageCheckDocument($languageCode)
	{
		// Construct a DOMDocument conforming to the language code schema xsd.
		// Requires a "root" node with an xml:lang attribute.
		$doc = $this->_coreHelper->getNewDomDocument();
		$doc->loadXML("<root xml:lang='$languageCode'/>");
		return $doc;
	}

	/**
	 * Get the path to the XSD schema file to use to validate the DOMDocument
	 * used to check an xml:lang.
	 *
	 * @return string
	 */
	protected function _getLanguageCodeValidationSchemaFile()
	{
		$cfg = $this->_coreHelper->getConfigModel();
		return Mage::getBaseDir() . DS . $cfg->apiXsdPath . DS . self::LANGUAGE_CODE_SCHEMA;
	}
}

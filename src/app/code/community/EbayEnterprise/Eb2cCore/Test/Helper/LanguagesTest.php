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

class EbayEnterprise_Eb2cCore_Test_Helper_LanguagesTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @loadFixture testLanguages.yaml
	 */
	public function testGetStoreLanguageCodesList()
	{
		$actualList   = Mage::helper('eb2ccore/languages')->getLanguageCodesList();
		$expectedList = array('default_lang', 'store3_lang', 'store4_lang');
		sort($expectedList);
		sort($actualList);

		$this->assertEquals(
			$expectedList,
			$actualList,
			'Failed Language Code List test.'
		);
	}
	/**
	 * @loadFixture testLanguages.yaml
	 */
	public function testGetAllStores()
	{
		// We have 3 stores configured, across several websites
		$stores = Mage::helper('eb2ccore/languages')->getStores();
		foreach ($stores as $key=>$store) {
			$this->assertEquals(		// Array key is the StoreId
				$key,
				$store->getStoreId()
			);
			switch ($store->getStoreId())
			{
			case 2: // Test Team USA Store is configured to default to website language
				$this->assertEquals(
					'default_lang',
					$store->getLanguageCode()
				);
				break;
			case 3: // Store 3 is configured for store3_lang
				$this->assertEquals(
					'store3_lang',
					$store->getLanguageCode()
				);
				break;
			case 4: // Store 4 is configured for store4_lang
				$this->assertEquals(
					'store4_lang',
					$store->getLanguageCode()
				);
				break;
			}
		}
	}
	/**
	 * @loadFixture testLanguages.yaml
	 */
	public function testGetStoreByLanguage()
	{
		// StoreId 3 is configured for 'store3_lang'
		$defaultLangStores = Mage::helper('eb2ccore/languages')->getStores('store3_lang');
		$this->assertEquals(1, count($defaultLangStores));
		$store = $defaultLangStores[key($defaultLangStores)];
		$this->assertEquals(3, $store->getStoreId());
	}

	/**
	 * Valid language codes should pass validation.
	 *
	 * @param string
	 */
	public function testValidateValidLanguageCode()
	{
		$this->assertTrue(Mage::helper('eb2ccore/languages')->validateLanguageCode('en-us'));
	}

	/**
	 * Invalid language codes should fail validation.
	 *
	 * @param string
	 */
	public function testValidateInvalidLanguageCode()
	{
		$this->assertFalse(Mage::helper('eb2ccore/languages')->validateLanguageCode('3nadbadfaefa04'));
	}
}

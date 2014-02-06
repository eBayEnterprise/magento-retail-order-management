<?php
class TrueAction_Eb2cCore_Test_Helper_LanguagesTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @test
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
	 * @test
	 * @loadFixture testLanguages.yaml
	 */
	public function testGetAllStores()
	{
		// We have 3 stores configured, across several websites
		$stores = Mage::helper('eb2ccore/languages')->getStores();
		foreach ($stores as $store) {
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
	 * @test
	 * @loadFixture testLanguages.yaml
	 */
	public function testGetStoreByLanguage()
	{
		// StoreId 3 is configured for 'store3_lang'
		$defaultLangStores = Mage::helper('eb2ccore/languages')->getStores('store3_lang');
		$this->assertEquals(1, count($defaultLangStores));
		$store = $defaultLangStores[0];
		$this->assertEquals(3, $store->getStoreId());
	}
}

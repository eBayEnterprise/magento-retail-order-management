<?php

class TrueAction_Eb2cProduct_Test_Model_Feed_CleanerTest
	extends TrueAction_Eb2cCore_Test_Base
{

	/**
	 * Test getting a collection of products that need to be cleaned.
	 * @test
	 * @loadFixture cleanerProductFixtures.yaml
	 */
	public function testGettingDirtyProducts()
	{
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner');
		$dirtyProducts = $cleaner->getProductsToClean();
		$this->assertSame(6, count($dirtyProducts), 'Found incorrect number of products.');

		$testProd = Mage::getModel('catalog/product')->load(1);
		foreach ($dirtyProducts as $product) {
			$this->assertFalse($product->getIsClean(), 'Included a product that is already clean.');
		}
	}

	/**
	 * Test updating the product links on a "dirty" product
	 * @test
	 * @loadFixture cleanerProductFixtures.yaml
	 * @dataProvider dataProvider
	 * @param  int $productId Product entity id
	 */
	public function testCleaningProduct($productId)
	{
		$product = Mage::getModel('catalog/product')->load($productId);
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner');
		$cleaner->cleanProduct($product);
		$expect = $this->expected('entity-%s', $productId);

		$savedProduct = Mage::getModel('catalog/product')->load($productId);
		$linkTypes = array('Related', 'UpSell', 'CrossSell');
		foreach ($linkTypes as $linkType) {
			$linkGetter = 'get' . $linkType . 'Products';
			$expectedLinks = $expect->$linkGetter();
			$actualLinks = $savedProduct->$linkGetter();
			$this->assertSame(
				count($expectedLinks),
				count($actualLinks),
				'Incorrect number of ' . $linkType . ' products'
			);
			foreach ($actualLinks as $linkedProduct) {
				$this->assertEquals(
					$expectedLinks,
					array_map(function ($p) { return $p->getSku(); }, $actualLinks),
					'Not all expected ' . $linkType . 'Products were linked.'
				);
			}
		}

		if ($savedProduct->getTypeId() === 'configurable') {
			$usedProducts = $savedProduct->getTypeInstance()->getUsedProducts();
			$this->assertEquals(
				$expect->getUsedProducts(),
				array_map(function ($p) { return $p->getSku(); }, $usedProducts),
				'Not all expected used products were linked.'
			);
		}
		$this->assertEquals(
			$expect->getUnresolvedProductLinks(),
			$savedProduct->getUnresolvedProductLinks(),
			'unresolved_product_links not properly updated'
		);
		$this->assertSame(
			$expect->getIsClean(),
			$savedProduct->getIsClean(),
			'is_clean flag not set properly'
		);
	}

	/**
	 * Test cleaning all products. Assertions are intentionally simplistic as the
	 * more rigorous coverage is included in the other test methods.
	 * I just want to ensure that when put together, all the prices work to some
	 * verifiable degree.
	 * @test
	 * @loadFixture cleanerProductFixtures.yaml
	 * @loadExpectation testCleaningProduct.yaml
	 */
	public function testCleanAllProducts()
	{
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner');
		$cleaner->cleanAllProducts();

		$products = Mage::getModel('catalog/product')->getCollection();
		foreach ($products as $product) {
			$expect = $this->expected('entity-' . $product->getId());
			$this->assertSame($expect->getIsClean(), $product->getIsClean());
		}
	}

}

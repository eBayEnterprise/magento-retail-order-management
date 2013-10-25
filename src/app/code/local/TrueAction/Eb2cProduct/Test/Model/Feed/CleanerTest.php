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
		$this->assertSame(4, count($dirtyProducts), 'Found incorrect number of products.');
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

		$this->assertSame($expect->getIsClean(), $product->getIsClean());
		$linkTypes = array('Related', 'UpSell', 'CrossSell');
		foreach ($linkTypes as $linkType) {
			$linkGetter = 'get' . $linkType . 'Products';
			$expectedLinks = $expect->$linkGetter();
			$actualLinks = $product->$linkGetter();
			$this->assertSame(count($expectedLinks), count($actualLinks));
			foreach ($actualLinks as $linkedProduct) {
				$this->assertEquals(
					$expectedLinks,
					array_map(function ($p) { return $p->getSku(); }, $actualLinks),
					'Not all expected ' . $linkType . 'Products were linked.'
				);
			}
		}

		if ($product->getTypeId() === 'configurable') {
			$usedProducts = $product->getTypeInstance()->getUsedProducts();
			$this->assertEquals(
				$expected->getUsedProducts(),
				array_map(function ($p) { return $p->getSku(); }, $usedProducts),
				'Not all expected used products were linked.'
			);
		}
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

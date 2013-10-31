<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_CleanerTest extends TrueAction_Eb2cCore_Test_Base
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

		foreach ($dirtyProducts as $product) {
			// boolean attributes don't actually come back as real booleans (true or false)
			// but instead as '1', '', '0' and the like so loose checks are necessary
			$this->assertSame($product->getIsClean(), '0', 'Included a product that is already clean.');
		}
	}

	/**
	 * Test updating the product links on a "dirty" product
	 * @test
	 * @loadfixture base.yaml
	 * @loadfixture cleanerproductfixtures.yaml
	 * @dataProvider dataProvider
	 * @param int $productId Product entity id
	 */
	public function testCleaningProduct($productId)
	{
		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('save'))
			->getMock();
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/product', $catalogModelProductMock);

		$product = Mage::getModel('catalog/product')->load($productId);
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner');
		$cleaner->cleanProduct($product);
		$expect = $this->expected('entity-%s', $productId);

		$savedProduct = Mage::getModel('catalog/product')->load($productId);

		$this->assertSame(1, (int) $savedProduct->getIsClean());
	}

	/**
	 * Test cleaning all products. Assertions are intentionally simplistic as the
	 * more rigorous coverage is included in the other test methods.
	 * I just want to ensure that when put together, all the pieces work to some
	 * verifiable degree.
	 * @test
	 * @loadFixture cleanerProductFixtures.yaml
	 * @loadExpectation testCleaningProduct.yaml
	 * @large
	 */
	public function testCleanAllProducts()
	{
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner');
		$cleaner->cleanAllProducts();

		$products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		foreach ($products as $product) {
			$expect = $this->expected('entity-' . $product->getId());
			$this->_productCleanedAssertions($product, $expect);
		}
	}

	protected function _productCleanedAssertions($product, $expectations)
	{
		$linkTypes = array('Related', 'UpSell', 'CrossSell');
		foreach ($linkTypes as $linkType) {
			$linkGetter = 'get' . $linkType . 'Products';
			$expectedLinks = $expectations->$linkGetter();
			$actualLinks = $product->$linkGetter();
			$this->assertSame(
				count($expectedLinks),
				count($actualLinks),
				'Incorrect number of ' . $linkType . ' products for ' . $product->getSku()
			);
			foreach ($actualLinks as $linkedProduct) {
				$this->assertEquals(
					$expectedLinks,
					array_map(function ($p) { return $p->getSku(); }, $actualLinks),
					'Not all expected ' . $linkType . 'Products were linked to ' . $product->getSku()
				);
			}
		}

		if ($product->getTypeId() === 'configurable') {
			$usedProducts = $product->getTypeInstance()->getUsedProducts();
			$this->assertEquals(
				$expectations->getUsedProducts(),
				array_map(function ($p) { return $p->getSku(); }, $usedProducts),
				'Not all expected used products were linked to ' . $product->getSku()
			);
		}
		$this->assertEquals(
			unserialize($expectations->getUnresolvedProductLinks()),
			unserialize($product->getUnresolvedProductLinks()),
			'unresolved_product_links not properly updated for ' . $product->getSku()
		);

		$this->assertEquals(
			$expectations->getIsClean(),
			$product->getIsClean(),
			'is_clean flag not set properly for ' . $product->getSku()
		);
	}
}

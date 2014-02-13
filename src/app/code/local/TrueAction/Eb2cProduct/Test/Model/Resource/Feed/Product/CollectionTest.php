<?php

class TrueAction_Eb2cProduct_Test_Model_Resource_Feed_Product_Collection
	extends TrueAction_Eb2cCore_Test_Base
{
	public function testGetItemId()
	{
		$product = Mage::getModel('catalog/product', array('sku' => 'sku-12345'));

		$collection = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame(
			'sku-12345',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$collection,
				'_getItemId',
				array($product)
			)
		);
	}
}

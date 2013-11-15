<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_ProcessorTest extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'var/eb2c';

	/**
	 * @loadFixture
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testTransformation($scenario)
	{
		$e = $this->expected($scenario);
		$checkData = function($dataObj) use ($e) {
			$keys = $e->getData('keys');
			$rootData = $dataObj->getData();
			foreach ($keys as $key) {
				PHPUnit_Framework_Assert::assertArrayHasKey(
					$key,
					$rootData,
					"missing [$key]"
				);
			}
			foreach (array('catalog_id', 'gsi_store_id', 'gsi_client_id') as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$e->getData($key),
					$dataObj->getData($key),
					"value of [$key] is not as expected"
				);
			}
			$expData = $e->getData('item_id');
			$actData = $dataObj->getData('item_id');
			foreach (array_keys($expData) as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$expData[$key],
					$actData->getData($key),
					"value of [$key] is not as expected"
				);
			}
			$expData = $e->getData('extended_attributes');
			$actData = $dataObj->getData('extended_attributes');
			foreach (array_keys($expData) as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$expData[$key],
					$actData->getData($key),
					"value of [$key] is not as expected"
				);
			}
			if ($e->hasData('color_attributes')) {
				$expData = $e->getData('color_attributes');
				$actData = $dataObj->getData('extended_attributes');
				$actData = $actData['color_attributes'];
				foreach (array_keys($expData) as $key) {
					PHPUnit_Framework_Assert::assertSame(
						$expData[$key],
						$actData->getData($key),
						"value of [$key] is not as expected"
					);
				}
			}
			if ($e->hasData('configurable_attributes')) {
				$expData = $e->getData('configurable_attributes');
				$actData = $dataObj->getData('configurable_attributes');
				foreach (array_keys($expData) as $key) {
					PHPUnit_Framework_Assert::assertSame(
						$expData[$key],
						$actData[$key],
						"value of [$key] is not as expected"
					);
				}
			}
		};
		$testModel = $this->getModelMock('eb2cproduct/feed_processor', array('_synchProduct', '_isAtLimit'));
		$testModel->expects($this->atLeastOnce())
			->method('_synchProduct')
			->will($this->returnCallback($checkData));
		$dataObj = new Varien_Object($this->getLocalFixture($scenario));
		$testModel->processUpdates(array($dataObj));
	}

	/**
	 * @loadFixture
	 */
	public function testStockItemData()
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$dataObj = new Varien_Object($this->getLocalFixture('itemmaster-exists'));
		// confirm preconditions
		$product = Mage::helper('eb2cproduct')->loadProductBySku('book');
		$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		$this->assertSame('0', $stock->getData('backorders'));
		$this->assertSame('1', $stock->getData('use_config_backorders'));
		$this->assertSame('100.0000', $stock->getData('qty'));
		// run test
		$testModel->processUpdates(array($dataObj));
		// verify results
		$product = Mage::helper('eb2cproduct')->loadProductBySku('book');
		$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		$this->assertSame('1', $stock->getData('backorders'));
		$this->assertSame('0', $stock->getData('use_config_backorders'));
		$this->assertSame('100.0000', $stock->getData('qty'));
	}

	/**
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testConfigurableData($scenario)
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$extractedUnits = $this->getLocalFixture($scenario);
		$processList = array();
		foreach ($extractedUnits as $extractedUnit) {
			$processList[] = new Varien_Object($extractedUnit);
		}
		$dataObj = new Varien_Object();
		// confirm preconditions
		// assert theparent exists
		// assert theotherparent doesnt exist
		// assert 45-000906014545 doesnt exist
		$product = Mage::helper('eb2cproduct')->loadProductBySku('45-000906014545');
		$testModel->processUpdates($processList);
	}

	/**
	 * Testing that we throw proper exception if we can't find an attribute
	 *
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInGetAttributeOptionId()
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$fn = $this->_reflectMethod($testModel, '_getAttributeOptionId');
		$fn->invoke($testModel, '', '');
	}

	/**
	 * Testing that we throw proper exception if we can't find an attribute
	 *
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInAddOptionToAttribute()
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$fn = $this->_reflectMethod($testModel, '_addOptionToAttribute');
		$fn->invoke($testModel, '', '', '');
	}
}

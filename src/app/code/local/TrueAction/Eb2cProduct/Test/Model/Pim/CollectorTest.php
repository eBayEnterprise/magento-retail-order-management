<?php
class TrueAction_Eb2cProduct_Model_Pim_CollectorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function testLoadConfig()
	{
		$config = $this->buildCoreConfigRegistry(array('pimExportFeedCutoffDate' => '2014-01-01T00:00:00'));
		$helper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$collector = Mage::getModel('eb2cproduct/pim_collector');

		$this->replaceByMock('helper', 'eb2cproduct', $helper);

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($config));

		$this->assertSame($collector, EcomDev_Utils_Reflection::invokeRestrictedMethod($collector, '_loadConfig'));
		$this->assertSame('2014-01-01T00:00:00', EcomDev_Utils_Reflection::getRestrictedPropertyValue($collector, '_cutoffDate'));
	}
	public function testGetExportableProducts()
	{
		$dateString = '2014-01-01T00:00:00';
		$fieldExpression = array('gteq' => $dateString);
		$collection = $this->getResourceModelMock('catalog/product_collection', array('addAttributeToSelect', 'addFieldToFilter'));
		$collector = Mage::getModel('eb2cproduct/pim_collector');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collector, '_cutoffDate', $dateString);
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);

		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('entity_id'))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('updated_at'), $this->identicalTo($fieldExpression))
			->will($this->returnSelf());

		$this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod($collector, '_getExportableProducts'));
	}
	public function testGetExportableProductsNoDate()
	{
		$collection = $this->getResourceModelMock('catalog/product_collection', array('addAttributeToSelect', 'addFieldToFilter'));
		$collector = Mage::getModel('eb2cproduct/pim_collector');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collector, '_cutoffDate', null);
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);

		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->will($this->returnSelf());
		$collection->expects($this->never())
			->method('addFieldToFilter');

		$this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod($collector, '_getExportableProducts'));
	}
	/**
	 * gather a collection of applicable products and export them.
	 * @test
	 */
	public function testRunExport()
	{
		$filepath = 'path/to/file';
		$collection = $this->getResourceModelMock('catalog/product_collection', array('getColumnValues'));
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('buildFeed'))
			->getMock();
		$logHelper = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logInfo', 'logDebug', 'logCrit'))
			->getMock();
		$collector = $this->getModelMock('eb2cproduct/pim_collector', array('_loadConfig', '_getExportableProducts', '_updateCutoffDate'));

		$this->replaceByMock('model', 'eb2cproduct/pim', $pim);
		$this->replaceByMock('helper', 'trueaction_magelog', $logHelper);

		$collector->expects($this->once())
			->method('_loadConfig')
			->will($this->returnSelf());
		$collector->expects($this->once())
			->method('_getExportableProducts')
			->will($this->returnValue($collection));
		$collector->expects($this->once())
			->method('_updateCutoffDate')
			->will($this->returnSelf());

		$collection->expects($this->once())
			->method('getColumnValues')
			->with($this->identicalTo('entity_id'))
			->will($this->returnValue(array('product ids')));

		$pim->expects($this->once())
			->method('buildFeed')
			->with($this->identicalTo(array('product ids')))
			->will($this->returnValue($filepath));

		$date = new Zend_Date();

		$logHelper->expects($this->exactly(2))
			->method('logInfo')
			->will($this->returnValueMap(array(
				array(
					'[%s] Starting PIM Export with cutoff date "%s"',
					array('TrueAction_Eb2cProduct_Model_Pim_Collector', $date->toString('c')),
					$logHelper
				),
				array(
					'[%s] Finished PIM Export',
					array('TrueAction_Eb2cProduct_Model_Pim_Collector'),
					$logHelper
				),
			)));

		$logHelper->expects($this->exactly(1))
			->method('logDebug')
			->will($this->returnValueMap(array(
				array(
					"[%s] Exportable Entity Ids:\n%s",
					array('TrueAction_Eb2cProduct_Model_Pim_Collector', '["product ids"]'),
					$logHelper
				),
			)));

		$this->assertSame($collector, $collector->runExport());
		$newDate = EcomDev_Utils_Reflection::getRestrictedPropertyValue($collector, '_startDate');
		$this->assertContains($date->toString('Y-MM-ddTH:mm'), $newDate);
	}
	/**
	 * if the built document fails to validate, then logCritical and exit.
	 * @test
	 */
	public function testRunException()
	{
		$collection = $this->getResourceModelMock('catalog/product_collection', array('_load'));
		$pim = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('buildFeed'))
			->getMock();
		$exception = new TrueAction_Eb2cCore_Exception_InvalidXml();
		$logHelper = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logCrit', 'logInfo', 'logDebug'))
			->getMock();
		$collector = $this->getModelMock('eb2cproduct/pim_collector', array('_loadConfig', '_getExportableProducts', '_updateCutoffDate'));

		$this->replaceByMock('model', 'eb2cproduct/pim', $pim);
		$this->replaceByMock('helper', 'trueaction_magelog', $logHelper);

		$collector->expects($this->once())
			->method('_getExportableProducts')
			->will($this->returnValue($collection));
		$collector->expects($this->never())
			->method('_updateCutoffDate');

		$logHelper->expects($this->at(2))
			->method('logCrit')
			->with(
				$this->identicalTo("[%s] Error building PIM Export:\n%s"),
				$this->identicalTo(array('TrueAction_Eb2cProduct_Model_Pim_Collector', $exception))
			)
			->will($this->returnSelf());

		$pim->expects($this->once())
			->method('buildFeed')
			->will($this->throwException($exception));

		$this->assertSame($collector, $collector->runExport());
	}
	/**
	 * save the start date as the last cutoff date.
	 * get a collection of products to export and
	 * use the pim model to build the feed.
	 * @test
	 */
	public function testUpdateCutoffDate()
	{
		$configData = $this->getModelMock('core/config_data', array('addData', 'save'));
		$collector = $this->getModelMock('eb2cproduct/pim_collector', array('_loadConfig', '_getExportableProducts'));

		$this->replaceByMock('model', 'core/config_data', $configData);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collector, '_startDate', 'date string');

		$configData->expects($this->once())
			->method('addData')
			->with($this->identicalTo(array(
				'path' => TrueAction_Eb2cProduct_Model_Pim_Collector::CUTOFF_DATE_PATH,
				'value' => 'date string',
				'scope' => 'default',
				'scope_id' => 0,
			)))
			->will($this->returnSelf());
		$configData->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$this->assertSame($collector, EcomDev_Utils_Reflection::invokeRestrictedMethod($collector, '_updateCutoffDate'));
	}
}

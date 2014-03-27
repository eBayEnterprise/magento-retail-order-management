<?php

class TrueAction_Eb2cProduct_Test_Model_Pim_CollectorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim_Collector::runExport for the following expectations
	 * Expectation 1: this test will invoke the method TrueAction_Eb2cProduct_Model_Pim_Collector::runExport and expects
	 *                the method TrueAction_Eb2cProduct_Model_Pim_Collector::_getNewZendDate method to be called and returned
	 *                a mocked Zend_Date object and expected the method Zend_Date::toString to be called given string value
	 *                and return a know time stamp string which will be assigned to the class property
	 *                TrueAction_Eb2cProduct_Model_Pim_Collector::_startDate
	 * Expectation 2: the method TrueAction_Magelog_Helper_Data::logInfo is expected to be called 2 time and the method
	 *                TrueAction_Magelog_Helper_Data::logDebug is expected to called 1 time
	 * Expectation 3: the method TrueAction_Eb2cProduct_Model_Pim_Collector::_loadConfig is expected to be called once
	 *                then the method TrueAction_Eb2cProduct_Model_Pim_Collector::_getExportableProducts is expected
	 *                to be invoked once and return mock of Mage_Catalog_Model_Resource_Product_Collection object
	 *                then the method Mage_Catalog_Model_Resource_Product_Collection::getColumnValues is expected to be called
	 *                once given a string value which will return an array list of entity ids then this list of enitity ids
	 *                is given as parameter to the method TrueAction_Eb2cProduct_Model_Pim::buildFeed, then the method
	 *                TrueAction_Eb2cProduct_Model_Pim_Collector::_updateCutoffDate is expected to be called once
	 */
	public function testRunExport()
	{
		$startTime = '2014-03-27T13:56:32+00:00';
		$entityIds = array(87, 98);

		$logData = array(
			array(
				'msg_template' => '[%s] Starting PIM Export with cutoff date "%s"',
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', $startTime)
			),
			array(
				'msg_template' => "[%s] Exportable Entity Ids:\n%s",
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', json_encode($entityIds))
			),
			array(
				'msg_template' => '[%s] Finished PIM Export',
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector')
			)
		);

		$zendDateMock = $this->getMockBuilder('Zend_Date')
			->disableOriginalConstructor()
			->setMethods(array('toString'))
			->getMock();
		$zendDateMock->expects($this->once())
			->method('toString')
			->with($this->identicalTo('c'))
			->will($this->returnValue($startTime));

		$magelogHelperMock = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logInfo', 'logDebug'))
			->getMock();
		$magelogHelperMock->expects($this->at(0))
			->method('logInfo')
			->with(
				$this->identicalTo($logData[0]['msg_template']),
				$this->identicalTo($logData[0]['msg_data'])
			)
			->will($this->returnSelf());
		$magelogHelperMock->expects($this->at(2))
			->method('logInfo')
			->with(
				$this->identicalTo($logData[2]['msg_template']),
				$this->identicalTo($logData[2]['msg_data'])
			)
			->will($this->returnSelf());
		$magelogHelperMock->expects($this->at(1))
			->method('logDebug')
			->with(
				$this->identicalTo($logData[1]['msg_template']),
				$this->identicalTo($logData[1]['msg_data'])
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'trueaction_magelog', $magelogHelperMock);

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getColumnValues'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('getColumnValues')
			->with($this->identicalTo('entity_id'))
			->will($this->returnValue($entityIds));

		$pimMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('buildFeed'))
			->getMock();
		$pimMock->expects($this->once())
			->method('buildFeed')
			->with($this->identicalTo($entityIds))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/pim', $pimMock);

		$collectorMock = $this->getModelMockBuilder('eb2cproduct/pim_collector')
			->disableOriginalConstructor()
			->setMethods(array('_getNewZendDate', '_loadConfig', '_getExportableProducts', '_updateCutoffDate'))
			->getMock();
		$collectorMock->expects($this->once())
			->method('_getNewZendDate')
			->will($this->returnValue($zendDateMock));
		$collectorMock->expects($this->once())
			->method('_loadConfig')
			->will($this->returnSelf());
		$collectorMock->expects($this->once())
			->method('_getExportableProducts')
			->will($this->returnValue($collectionMock));
		$collectorMock->expects($this->once())
			->method('_updateCutoffDate')
			->will($this->returnSelf());

		$this->assertSame($collectorMock, $collectorMock->runExport());
	}

	/**
	 * @see self::testRunExport except this time we are testing when the method TrueAction_Eb2cProduct_Model_Pim::buildFeed
	 *      thrown a TrueAction_Eb2cCore_Exception_InvalidXml exception
	 */
	public function testRunExportBuildFeedThrowException()
	{
		$startTime = '2014-03-27T13:56:32+00:00';
		$entityIds = array(87, 98);
		$invalidXml = 'Unittest Throwing exception';
		$xmlException = new TrueAction_Eb2cCore_Exception_InvalidXml($invalidXml);

		$logData = array(
			array(
				'msg_template' => '[%s] Starting PIM Export with cutoff date "%s"',
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', $startTime)
			),
			array(
				'msg_template' => "[%s] Exportable Entity Ids:\n%s",
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', json_encode($entityIds))
			),
			array(
				'msg_template' => "[%s] Error building PIM Export:\n%s",
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', $xmlException)
			),
			array(
				'msg_template' => '[%s] Finished PIM Export',
				'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector')
			)
		);

		$zendDateMock = $this->getMockBuilder('Zend_Date')
			->disableOriginalConstructor()
			->setMethods(array('toString'))
			->getMock();
		$zendDateMock->expects($this->once())
			->method('toString')
			->with($this->identicalTo('c'))
			->will($this->returnValue($startTime));

		$magelogHelperMock = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logInfo', 'logDebug', 'logCrit'))
			->getMock();
		$magelogHelperMock->expects($this->at(0))
			->method('logInfo')
			->with(
				$this->identicalTo($logData[0]['msg_template']),
				$this->identicalTo($logData[0]['msg_data'])
			)
			->will($this->returnSelf());
		$magelogHelperMock->expects($this->at(3))
			->method('logInfo')
			->with(
				$this->identicalTo($logData[3]['msg_template']),
				$this->identicalTo($logData[3]['msg_data'])
			)
			->will($this->returnSelf());
		$magelogHelperMock->expects($this->at(1))
			->method('logDebug')
			->with(
				$this->identicalTo($logData[1]['msg_template']),
				$this->identicalTo($logData[1]['msg_data'])
			)
			->will($this->returnSelf());
		$magelogHelperMock->expects($this->at(2))
			->method('logCrit')
			->with(
				$this->identicalTo($logData[2]['msg_template']),
				$this->identicalTo($logData[2]['msg_data'])
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'trueaction_magelog', $magelogHelperMock);

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getColumnValues'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('getColumnValues')
			->with($this->identicalTo('entity_id'))
			->will($this->returnValue($entityIds));

		$pimMock = $this->getModelMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('buildFeed'))
			->getMock();
		$pimMock->expects($this->once())
			->method('buildFeed')
			->with($this->identicalTo($entityIds))
			->will($this->throwException($xmlException));
		$this->replaceByMock('model', 'eb2cproduct/pim', $pimMock);

		$collectorMock = $this->getModelMockBuilder('eb2cproduct/pim_collector')
			->disableOriginalConstructor()
			->setMethods(array('_getNewZendDate', '_loadConfig', '_getExportableProducts', '_updateCutoffDate'))
			->getMock();
		$collectorMock->expects($this->once())
			->method('_getNewZendDate')
			->will($this->returnValue($zendDateMock));
		$collectorMock->expects($this->once())
			->method('_loadConfig')
			->will($this->returnSelf());
		$collectorMock->expects($this->once())
			->method('_getExportableProducts')
			->will($this->returnValue($collectionMock));
		$collectorMock->expects($this->once())
			->method('_updateCutoffDate')
			->will($this->returnSelf());

		$this->assertSame($collectorMock, $collectorMock->runExport());
	}

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim_Collector::_loadConfig method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Pim_Collector::_loadConfig will be invoked by this test
	 *                and is expected the method TrueAction_Eb2cProduct_Helper_Data::getConfigModel to be called and
	 *                return a mocked of TrueAction_Eb2cCore_Model_Config_Registry in which the magic propery
	 *                TrueAction_Eb2cCore_Model_Config_Registry::pimExportFeedCutoffDate will be assigned to the
	 *                class property TrueAction_Eb2cProduct_Model_Pim_Collector::_cutoffDate
	 */
	public function testLoadConfig()
	{
		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'pimExportFeedCutoffDate' => ''
			))));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$collectorMock = $this->getModelMockBuilder('eb2cproduct/pim_collector')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($collectorMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$collectorMock, '_loadConfig', array()
		));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim_Collector::_getExportableProducts method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Pim_Collector::_getExportableProducts is expected to be invoked
	 *                by this test, the method Mage_Catalog_Model_Resource_Product_Collection::addAttributeToSelect is
	 *                expected to be called once and given a string, then the class property
	 *                TrueAction_Eb2cProduct_Model_Pim_Collector::_cutoffDate will be set to a know timestamp value
	 *                in which the method Mage_Catalog_Model_Resource_Product_Collection::addFieldToFilter will be invoked
	 *                given a known string as the first parameter and an array with key value as the second parameter
	 */
	public function testGetExportableProducts()
	{
		$cutoffDate = '2014-03-27T13:56:32+00:00';

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('entity_id'))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('updated_at'), $this->identicalTo(array('gteq' => $cutoffDate)))
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collectionMock);

		$collectorMock = $this->getModelMockBuilder('eb2cproduct/pim_collector')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collectorMock, '_cutoffDate', $cutoffDate);

		$this->assertSame($collectionMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$collectorMock, '_getExportableProducts', array()
		));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Model_Pim_Collector::_updateCutoffDate method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Pim_Collector::_updateCutoffDate is expected to be called
	 *                by this test and expected the method TrueAction_Magelog_Helper_Data::logDebug to be called once
	 *                given a know string and an array, then the method Mage_Core_Model_Config_Data::addData is expected
	 *                to be called given an array of data
	 */
	public function testUpdateCutoffDate()
	{
		$startTime = '2014-03-27T13:56:32+00:00';
		$logData = array(
			'msg_template' => '[%s] Updateding cutoff date to "%s"',
			'msg_data' => array('TrueAction_Eb2cProduct_Model_Pim_Collector', $startTime)
		);
		$data = array(
			'path' => TrueAction_Eb2cProduct_Model_Pim_Collector::CUTOFF_DATE_PATH,
			'value' => $startTime,
			'scope' => 'default',
			'scope_id' => 0,
		);

		$magelogHelperMock = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logDebug'))
			->getMock();
		$magelogHelperMock->expects($this->once())
			->method('logDebug')
			->with(
				$this->identicalTo($logData['msg_template']),
				$this->identicalTo($logData['msg_data'])
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'trueaction_magelog', $magelogHelperMock);

		$configMock = $this->getModelMockBuilder('core/config_data')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save'))
			->getMock();
		$configMock->expects($this->once())
			->method('addData')
			->with($this->identicalTo($data))
			->will($this->returnSelf());
		$configMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'core/config_data', $configMock);

		$collectorMock = $this->getModelMockBuilder('eb2cproduct/pim_collector')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collectorMock, '_startDate', $startTime);

		$this->assertSame($collectorMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$collectorMock, '_updateCutoffDate', array()
		));
	}
}

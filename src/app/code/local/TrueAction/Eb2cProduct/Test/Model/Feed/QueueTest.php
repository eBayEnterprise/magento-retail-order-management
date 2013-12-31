<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_QueueTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * operation types.
	 * denote wether the unit will be used for adding, deleting or updating
	 * product data.
	 */
	public static $mapping = array(
		'add' => TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface::OPERATION_TYPE_ADD,
		'update' => TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface::OPERATION_TYPE_UPDATE,
		'delete' => TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface::OPERATION_TYPE_REMOVE,
	);

	/**
	 * verify add requires a valid operation type
	 */
	public function testAddFail()
	{
		$this->setExpectedException('Mage_Core_Exception', 'invalid operation type ');
		$processorModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $processorModel);

		$testModel = Mage::getModel('eb2cproduct/feed_queue');
		$testModel->add(new Varien_Object(), 'foo');
	}

	/**
	 * verify items are added to the proper list.
	 * @dataProvider dataProvider
	 */
	public function testAdd($operation)
	{

		$processor = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('processDeletions', 'processUpserts'))
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $processor);

		$testModel = $this->getModelMock('eb2cproduct/feed_queue', array('_isAtEntryLimit', 'process'));
		$testModel->expects($this->atLeastOnce())
			->method('_isAtEntryLimit')
			->will($this->returnValue(false));
		$testModel->expects($this->any())
			->method('process');

		$data = new Varien_Object();
		$testModel->add($data, self::$mapping[$operation]);
		$list = $operation === 'delete' ? '_deletionList' : '_upsertList';
		$list = $this->_reflectProperty($testModel, $list)->getValue($testModel);
		$this->assertContains($data, $list);
	}

	/**
	 * verify process is called when queue reaches limit
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testAddLimits($numAdds, $numDeletes)
	{
		$e = $this->expected('%s-%s', $numAdds, $numDeletes);
		$processor = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('processDeletions', 'processUpdates'))
			->getMock();
		$processor->expects($e->getValue() ? $this->atLeastOnce() : $this->never())
			->method('processDeletions');
		$processor->expects($e->getValue() ? $this->atLeastOnce() : $this->never())
			->method('processUpdates');
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $processor);

		$testModel = Mage::getModel('eb2cproduct/feed_queue');
		$this->_reflectProperty($testModel, '_maxEntries')->setValue($testModel, 10);
		$this->_reflectProperty($testModel, '_maxTotalEntries')->setValue($testModel, 15);

		for ($i = 0; $i < $numAdds; $i++) {
			$testModel->add(new Varien_Object(), self::$mapping['add']);
		}
		for ($i = 0; $i < $numDeletes; $i++) {
			$testModel->add(new Varien_Object(), self::$mapping['delete']);
		}
	}
}

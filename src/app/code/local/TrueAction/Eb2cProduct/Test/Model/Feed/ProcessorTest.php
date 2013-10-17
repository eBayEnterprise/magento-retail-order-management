<?php
class TrueAction_Eb2cProduct_Test_Model_ProcessorTest
	extends TrueAction_Eb2cCore_Test_Base
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
			PHPUnit_Framework_Assert::assertEquals(
				$e->getData(),
				$dataObj->getData()
			);
		};
		$testModel = $this->getModelMock('eb2cproduct/feed_processor', array('_synchProduct', '_isAtLimit'));
		$testModel->expects($this->atLeastOnce())
			->method('_synchProduct')
			->will($this->returnCallback($checkData));
		$dataObj = new Varien_Object($this->getLocalFixture($scenario));
		$testModel->processUpdates(array($dataObj));
	}
}

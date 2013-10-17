<?php
class TrueAction_Eb2cProduct_Test_Model_ProcessorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'var/eb2c';

	/**
	 * @loadFixture testExtraction.yaml
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testTransformation($scenario, $feedFile)
	{
		$data = $this->getLocalFixture($scenario);
		$checkData = function($dataObj) use ($e) {
			PHPUnit_Framework_Assert::assertEquals(
				$dataObj->getData(),
				$e->getData()
			);
		};
		$testModel = $this->getModelMock('eb2cproduct/feed_processor', array('saveBatch'));
		$testModel->expects($this->atLeastOnce())
			->method('saveBatch');
		$testModel->processUpserts($iterable);
		$xformedData = $this->_reflectAttribute($testModel, '_upsertList')->getValue($testValue);
		$this->assertEquals($e->getData(), $xformedData);
	}
}

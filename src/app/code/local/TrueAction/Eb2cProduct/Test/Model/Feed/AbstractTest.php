<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_AbstractTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify getChunks always returns an array
	 */
	public function testGetChunks($expectation, $xml)
	{
		$mapping = array('sku' => 'sku/text()', 'other' => 'sku/@attr');
		$basePath = 'root/foo';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root><foo><sku attr="stuff">thesku</sku></foo><foo><sku>othersku</sku></foo></root>');
		$model = $this->getModelMock('eb2cproduct/feed_abstract', array('getBasePath', 'getMapping'), true, array(), 'eb2cproduct/feed_abstractmock', false);
		$model->expects($this->any())
			->method('getBasePath')
			->will($this->returnValue($basePath));
		$model->expects($this->any())
			->method('getMapping')
			->will($this->returnValue($mapping));
		$result = $model->getChunks($doc);
		$this->assertTrue(is_array($result), 'result was not an array');
		$e = $this->expected($expectation);
		$this->assertSame(2, count($result));
		$expected = array(
			'thesku' => array(
				'sku' => 'thesku',
				'other' => 'stuff'
			),
			'othersku' => array(
				'sku' => 'othersku',
				'other' => null
			)
		);
		foreach ($result as $sku => $chunk) {
			$this->assertInstanceOf('Varien_Object', $chunk);
			$this->assertEquals($expected[$sku], $chunk->getData());
		}
	}
}

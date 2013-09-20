<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_ExtractorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify getChunks always returns an array
	 */
	public function testGetChunks()
	{
		$this->markTestSkipped('the "superfeed" code needs to be debugged.');
		$mapping = array('sku' => 'sku/text()', 'other' => 'sku@attr');
		$basePath = '/root/foo';
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root><foo><sku attr="stuff">thesku</sku></foo><foo><sku>othersku</sku></foo></root>');
		$extractionModel = new Varien_Object();
		$extractionModel->setBaseXpath($basePath)
			->setMapping($mapping);
		$model = Mage::getModel('eb2cproduct/feed_abstract');
		$model->setExtractionModel($extractionModel)
			->setXpath(new DomXPath($doc));
		$result = $model->getChunks($doc);
		$this->assertNotNull($result);
		$this->assertTrue(is_array($result), 'result was not an array');
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

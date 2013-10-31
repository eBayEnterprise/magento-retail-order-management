<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_Specialized_OperationtypeTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 */
	public function testExtract()
	{
		$xml = '<foo> cat <bar>dog</bar></foo>';

		$mapping = array(
			'foo' => './text()',
			'baz' => 'bar/text()',
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_specialized_operationtype');
		$result = $x->extract($xpath, $doc->documentElement);
		$this->assertEquals(array('operation' => 'ADD'), $result);
	}

	/**
	 */
	public function testGetOperation()
	{
		$xml = '<foo> cat <bar>dog</bar></foo>';

		$mapping = array(
			'foo' => './text()',
			'baz' => 'bar/text()',
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$testModel = Mage::getModel('eb2cproduct/feed_extractor_specialized_operationtype');
		$result = $testModel->getValue($xpath, $doc->documentElement);
		$this->assertSame('ADD', $result);
	}
}

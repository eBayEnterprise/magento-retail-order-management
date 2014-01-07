<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_TypecastTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test __construct method
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testTypecastConstructorWithException()
	{
		Mage::getModel('eb2cproduct/feed_extractor_typecast', array(null, null));
	}

	/**
	 */
	public function testExtract()
	{
		$xml = '<foo> 1 <bar>100</bar></foo>';

		$mapping = array(
			'foo' => './text()',
			'baz' => 'bar/text()',
		);

		$type = 'float';

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_typecast', array($mapping, $type));
		$result = $x->extract($xpath, $doc->documentElement);
		$this->assertSame(array('foo' => (float) 1.0000, 'baz' => (float) 100.0000), $result);
	}
}

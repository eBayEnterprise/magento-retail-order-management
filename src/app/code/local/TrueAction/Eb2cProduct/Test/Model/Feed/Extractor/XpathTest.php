<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_XpathTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test __construct method
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testXpathConstructorWithException()
	{
		Mage::getModel('eb2cproduct/feed_extractor_xpath', array(null, null));
	}

	/**
	 * @loadExpectation
	 */
	public function testExtract()
	{
		$xml = '<foo> cat <bar>dog</bar></foo>';

		$mapping = array(
			'foo' => './text()',
			'baz' => 'bar/text()',
			'wrong' => '',
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_xpath', array($mapping));
		$result = $x->extract($xpath, $doc->documentElement);
		$e = $this->expected('0');
		$this->assertEquals($e->getData(), $result);

		$x = Mage::getModel('eb2cproduct/feed_extractor_xpath', array($mapping, false));
		$result = $x->extract($xpath, $doc->documentElement);
		$e = $this->expected('1');
		$this->assertEquals($e->getData(), $result);
	}
}

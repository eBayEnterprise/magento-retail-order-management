<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_XpathTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @loadExpectation
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
		$x = Mage::getModel('eb2cproduct/feed_extractor_xpath', $mapping);
		$result = $x->extract($xpath, $doc->documentElement);
		$e = $this->expected('0');
		$this->assertEquals($e->getData(), $result);
	}
}

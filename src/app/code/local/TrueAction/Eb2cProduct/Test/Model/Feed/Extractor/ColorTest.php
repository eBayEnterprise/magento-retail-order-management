<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_ColorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 */
	public function testExtract()
	{
		$xml = '
			<root>
				<foo><Value>1</Value><Description xml:lang="en_US">desc1</Description></foo>
			</root>';

		$base = array('thefoo' => 'foo');
		$valueAlias = array('code' => 'value/text()');

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($base));
		$result = $x->extract($xpath, $doc->documentElement);
		$this->assertSame(
			array(
				'thefoo' => array(
					array(
						'value' => '1',
						'description' => array(
							'description' => 'desc1',
							'lang' => 'en_US'
						)
					)
				)
			),
			$result
		);
	}

	public function testExtractAliasedValue()
	{
		$xml = '
			<root>
				<foo><Code>1</Code><Description xml:lang="en_US">desc1</Description></foo>
			</root>';
		$base = array('thefoo' => 'foo');
		$valueAlias = array('code' => 'Code/text()');

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($base, $valueAlias));
		$result = $x->extract($xpath, $doc->documentElement);
		$this->assertSame(
			array(
				'thefoo' => array(
					array(
						'code' => '1',
						'description' => array(
							'description' => 'desc1',
							'lang' => 'en_US'
						)
					)
				)
			),
			$result
		);
	}

	public function testExtractNoValue()
	{
		$xml = '
			<root>
				<foo><Code></Code><Description xml:lang="en_US">desc1</Description></foo>
			</root>';
		$base = array('thefoo' => 'foo');
		$valueAlias = array('code' => 'Code/text()');

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($base, $valueAlias));
		$result = $x->extract($xpath, $doc->documentElement);
		$this->assertSame(array(), $result);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testBadArg1($scenario, $arg1)
	{
		$this->setExpectedException('Mage_Core_Exception', 'The 1st argument in the initializer array must be an array mapping the top-level key to an xpath string');
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($arg1));
	}
}

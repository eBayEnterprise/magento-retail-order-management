<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_ColorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test __construct method
	 * @test
	 */
	public function testConstruct()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Extractor_Color',
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('thefoo' => 'foo'),
				array('code' => 'Code/text()')
			))
		);
	}

	/**
	 * Test __construct method, passing the wrong argument 1 will throw exception
	 * @test
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Extractor_Exception
	 */
	public function testConstructWrongArgumentOneException()
	{
		Mage::getModel('eb2cproduct/feed_extractor_color', array(
			'wrong argument 1',
			array('code' => 'Code/text()')
		));
	}

	/**
	 * Test __construct method, passing the wrong argument 2 will throw exception
	 * @test
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Extractor_Exception
	 */
	public function testConstructWrongArgumentTwoException()
	{
		Mage::getModel('eb2cproduct/feed_extractor_color', array(
			array('thefoo' => 'foo'),
			'wrong argument 2'
		));
	}

	/**
	 * Test extract method, the newly refactor extract method
	 * @test
	 */
	public function testExtractRefactored()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value>1</Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array('_queryNodeList', '_extractValue', '_extractLocalizedDescription'))
			->getMock();
		$colorModelMock->expects($this->once())
			->method('_queryNodeList')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'), $this->equalTo('foo'))
			->will($this->returnValue($xpath->query('foo', $doc->documentElement)));
		$colorModelMock->expects($this->once())
			->method('_extractValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'), $this->equalTo('Value/text()'))
			->will($this->returnValue('1'));
		$colorModelMock->expects($this->once())
			->method('_extractLocalizedDescription')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue(array('en_US' => 'desc1')));

		$this->_reflectProperty($colorModelMock, '_baseXpath')->setValue($colorModelMock, 'foo');
		$this->_reflectProperty($colorModelMock, '_valueXpath')->setValue($colorModelMock, 'Value/text()');
		$this->_reflectProperty($colorModelMock, '_baseKey')->setValue($colorModelMock, 'foo');
		$this->_reflectProperty($colorModelMock, '_valueKeyAlias')->setValue($colorModelMock, 'value');

		$this->assertSame(
			array(
				'foo' => array(
					'value' => '1',
					'localization' => array(
						'en_US' => 'desc1'
					)
				)
			),
			$colorModelMock->extract($xpath, $doc->documentElement)
		);
	}

	/**
	 * Test extract method, with empty value node
	 * @test
	 */
	public function testExtractWithEmptyValueNode()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value></Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array('_queryNodeList', '_extractValue'))
			->getMock();
		$colorModelMock->expects($this->once())
			->method('_queryNodeList')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'), $this->equalTo('foo'))
			->will($this->returnValue($xpath->query('foo', $doc->documentElement)));
		$colorModelMock->expects($this->once())
			->method('_extractValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'), $this->equalTo('Value/text()'))
			->will($this->returnValue(''));

		$this->_reflectProperty($colorModelMock, '_baseXpath')->setValue($colorModelMock, 'foo');
		$this->_reflectProperty($colorModelMock, '_valueXpath')->setValue($colorModelMock, 'Value/text()');
		$this->_reflectProperty($colorModelMock, '_baseKey')->setValue($colorModelMock, 'foo');
		$this->_reflectProperty($colorModelMock, '_valueKeyAlias')->setValue($colorModelMock, 'value');

		$this->assertSame(
			array(),
			$colorModelMock->extract($xpath, $doc->documentElement)
		);
	}

	/**
	 * Test _queryNodeList method
	 * @test
	 */
	public function testQueryNodeList()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value>1</Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'DOMNodeList',
			$this->_reflectMethod($colorModelMock, '_queryNodeList')->invoke($colorModelMock, $xpath, $doc->documentElement, 'foo')
		);
	}

	/**
	 * Test _queryNodeList method, wrong xpath will cause exception
	 * @test
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testQueryNodeListWithExption()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value>1</Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectMethod($colorModelMock, '_queryNodeList')->invoke(
			$colorModelMock, new DOMXPath(new TrueAction_Dom_Document('1.0', 'UTF-8')), $doc->documentElement, 'foo'
		);
	}

	/**
	 * Test _extractValue method
	 * @test
	 */
	public function testExtractValue()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value>1</Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);
		$newNode = null;
		foreach ($xpath->query('foo', $doc->documentElement) as $node) {
			$newNode = $node;
			break;
		}

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array('_queryNodeList'))
			->getMock();
		$colorModelMock->expects($this->once())
			->method('_queryNodeList')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'), $this->equalTo('Value/text()'))
			->will($this->returnValue($xpath->query('Value/text()', $newNode)));

		$this->assertSame('1', $this->_reflectMethod($colorModelMock, '_extractValue')->invoke($colorModelMock, $xpath, $newNode, 'Value/text()'));
	}

	/**
	 * Test _extractLocalizedDescription method
	 * @test
	 */
	public function testExtractLocalizedDescription()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<Value>1</Value>
					<Description xml:lang="en_US">desc1</Description>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);
		$newNode = null;
		foreach ($xpath->query('foo', $doc->documentElement) as $node) {
			$newNode = $node;
			break;
		}

		$colorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_color')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			array('en_US' => 'desc1'),
			$this->_reflectMethod($colorModelMock, '_extractLocalizedDescription')->invoke($colorModelMock, $xpath, $newNode)
		);
	}

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
					'value' => '1',
					'localization' => array(
						'en_US' => 'desc1',
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
					'code' => '1',
					'localization' => array(
						'en_US' => 'desc1',
					)
				)
			),
			$result
		);
	}

	public function testMultipleLocalizations()
	{
		$xml = '
			<ColorAttributes>
				<Color>
					<Code>700</Code>
					<Description xml:lang="en-US">Vanilla</Description>
					<Description xml:lang="ja-JP">バニラ</Description>
					<Description xml:lang="he-IL">וניל</Description>
				</Color>
			</ColorAttributes>';

		$base = array('theColor' => 'Color');
		$valueAlias = array('code' => 'Code/text()');

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($base, $valueAlias));
		$result = $x->extract($xpath, $doc->documentElement);

		$this->assertSame(
			array(
				'theColor' => array(
					'code' => '700',
					'localization' => array(
						'en-US' => 'Vanilla',
						'ja-JP' => 'バニラ',
						'he-IL' => 'וניל',
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
	public function testBadArgA($scenario, $argA)
	{
		$this->setExpectedException('Mage_Core_Exception', 'The 1st argument in the initializer array must be an array mapping the top-level key to an xpath string');
		$x = Mage::getModel('eb2cproduct/feed_extractor_color', array($argA));
	}
}

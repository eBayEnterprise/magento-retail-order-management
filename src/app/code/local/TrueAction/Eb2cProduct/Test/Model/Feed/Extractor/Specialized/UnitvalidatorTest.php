<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Extractor_Specialized_UnitvalidatorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test __construct method
	 * @test
	 */
	public function testUnitvalidatorConstructor()
	{
		$unitvalidator = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_unitvalidator',
			array(
				array(
					'catalog_id' => './@catalog_id',
					'gsi_client_id' => './@gsi_client_id',
				),
				array(
					'catalog_id' => 'ABCD',
					'gsi_client_id' => '1234',
				),
			)
		);

		$this->assertSame(
			array('catalog_id' => 'ABCD', 'gsi_client_id' => '1234'),
			$this->_reflectProperty($unitvalidator, '_answer')->getValue($unitvalidator)
		);
	}

	/**
	 * Test __construct method, instantiating the class empty array will throw exception
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testUnitvalidatorConstructorWithException()
	{
		Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_unitvalidator',
			array(
				array(
					'catalog_id' => './@catalog_id',
					'gsi_client_id' => './@gsi_client_id',
				)
			)
		);
	}

	/**
	 * test extract method
	 * @test
	 */
	public function testExtract()
	{
		$unitvalidator = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_unitvalidator',
			array(
				array(
					'foo' => './text()',
					'baz' => 'bar/text()',
				),
				array()
			)
		);
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<foo> cat <bar>dog</bar></foo>');
		$this->assertSame(array(), $unitvalidator->extract(new DOMXPath($doc), $doc->documentElement));

		$this->_reflectProperty($unitvalidator, '_answer')->setValue($unitvalidator, array(
			'foo' => 'cat',
			'baz' => 'dog'
		));

		$this->assertSame(array('foo' => 'cat', 'baz' => 'dog'), $unitvalidator->extract(new DOMXPath($doc), $doc->documentElement));
	}

	/**
	 * test getValue method
	 * @test
	 */
	public function testGetValue()
	{
		$unitvalidator = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_unitvalidator',
			array(
				array(
					'foo' => './text()',
					'baz' => 'bar/text()',
				),
				array()
			)
		);
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<foo> cat <bar>dog</bar></foo>');
		$this->assertSame(false, $unitvalidator->getValue(new DOMXPath($doc), $doc->documentElement));

		$this->_reflectProperty($unitvalidator, '_answer')->setValue($unitvalidator, array(
			'foo' => 'cat',
			'baz' => 'dog'
		));

		$this->assertSame(true, $unitvalidator->getValue(new DOMXPath($doc), $doc->documentElement));
	}
}

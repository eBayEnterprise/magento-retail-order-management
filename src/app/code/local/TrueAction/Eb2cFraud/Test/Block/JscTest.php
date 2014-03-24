<?php
class TrueAction_Eb2cFraud_Test_Block_JscTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Constructor should populate the block instance with "magic" data about the
	 * collector to use.
	 * @test
	 */
	public function testConstruct()
	{
		$jscUrl = 'https://magento.domain/js';
		$fraudHelper = $this->getHelperMock('eb2cfraud/data', array('getJscUrl'));
		$fraudHelper->expects($this->once())
			->method('getJscUrl')
			->will($this->returnValue($jscUrl));
		$this->replaceByMock('helper', 'eb2cfraud', $fraudHelper);

		// provide a single collector so it should be easy to tell which is
		// randomly selected
		$collectors = array(array(
			'filename' => 'jsc_filename.js',
			'formfield' => 'jsc_formfield',
			'function' => "jsc_function",
		));
		$block = new TrueAction_Eb2cFraud_Block_Jsc(array('collectors' => $collectors));
		$this->assertSame(
			$jscUrl . DS . 'jsc_filename.js',
			$block->getCollectorUrl()
		);
		$this->assertSame(
			"jsc_function('jsc_formfield');",
			$block->getCall()
		);
		$this->assertSame(
			'<input type="hidden" name="jsc_formfield" id="jsc_formfield" />',
			$block->getField()
		);
		$this->assertSame(
			// name of this field comes from a const on the helper used to retrieve
			// the data from the request POST data
			// @see TrueAction_Eb2cFraud_Helper_Data::JSC_FIELD_NAME
			'<input type="hidden" name="eb2cszyvl" id="eb2cszyvl" value="jsc_formfield" />',
			$block->getMappingField()
		);
	}
}

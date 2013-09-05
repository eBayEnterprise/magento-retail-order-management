<?php
class TrueAction_Eb2cFraud_Test_Model_JscTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test the 3 functions that put out necessary html/ javascript we need to inject into the checkout form
	 * @test
	 */
	public function testJscHtml()
	{
		$collectors = array(array(
			'function' => 'testfunction1',
			'formfield' => 'testformfield1',
			'filename' => 'testfilename1',
		));
		$helper = Mage::helper('eb2cfraud');
		$expectedHtml = sprintf(TrueAction_Eb2cFraud_Model_Jsc::JSC_HTML_TMPL,
			$helper->getJscUrl() . '/testfilename1',
			'<input type="hidden" name="testformfield1" id="testformfield1" />',
			"testfunction1('testformfield1');"
		);
		$expectedTag = sprintf(
			TrueAction_Eb2cFraud_Model_Jsc::JSC_SCRIPT_TAG_TMPL,
			$helper->getJscUrl() . '/testfilename1'
		);

		$jsc = Mage::getModel('eb2cfraud/jsc', array('collectors' => $collectors));
		$scriptTagHtml = $jsc->getJscHtml();
		$this->assertSame($expectedHtml, $scriptTagHtml);

		$scriptTag = $jsc->getJscScriptTag();
		$this->assertSame($expectedTag, $scriptTag);
	}
}

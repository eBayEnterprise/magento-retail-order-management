
<?php
/**
 *
 *
 */
class TrueAction_Eb2cFraud_Test_Model_JscTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test the 3 functions that put out necessary html/ javascript we need to inject into the checkout form
	 * @test
	 *
	 */
	public function testBuildHtml() {
		$jsc = Mage::getModel('eb2cfraud/jsc');
		$scriptTagHtml = $jsc->getJscHtml();
		$scriptTagMatcher = array('tag' => 'script');
		$this->assertTag( $scriptTagMatcher, $scriptTagHtml, 'Script Tag Error');

		$scriptTagHtml = $jsc->getJscScriptTag();
		$scriptTagMatcher = array('tag' => 'script');
		$this->assertTag( $scriptTagMatcher, $scriptTagHtml, 'Script Tag Error');

		$formFieldHtml = $jsc->getJscFormField();
		$formFieldTagMatcher = array('tag' => 'input');
		$this->assertTag( $formFieldTagMatcher, $formFieldHtml, 'Form Field Tag Error');

		$jscFunctionCall = $jsc->getJscFunctionCall();
		$this->assertStringEndsWith(");", $jscFunctionCall);
	}
}

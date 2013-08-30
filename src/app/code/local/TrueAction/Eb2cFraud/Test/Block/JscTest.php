<?php
class TrueAction_Eb2cFraud_Test_Block_JscTest extends EcomDev_PHPUnit_Test_Case
{
	const JSC_SCRIPT_SNIPPET = '<script type="text/javascript" src="http';
	/**
	 * Make sure that we can get the widget, call the html function. This also ensures that getCacheLifetime() is called
	 * to make sure we don't cache this block in the real world.
	 * @test
	 * @medium
	 */
	public function testWidgetBlock()
	{
		$widget = Mage::getSingleton('core/layout')->createBlock('eb2cfraud/jsc');
		$html = $widget->toHtml();
		$this->assertStringStartsWith(self::JSC_SCRIPT_SNIPPET, $html);
	}
}

<?php
class TrueAction_Eb2cFraud_Model_Jsc extends Mage_Core_Model_Abstract
{
	const EB2C_FRAUD_COLLECTOR_PATH = 'default/eb2cfraud/collectors';
	const JSC_HTML_TMPL = <<<'JS'
<script type="text/javascript" src="%s"></script>
<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded", function() {
	$('co-payment-form').insert('%s');
	Review.prototype.save = function() {
		%s
		if (checkout.loadWaiting!=false) return;
		checkout.setLoadWaiting('review');
		Form.enable(payment.form);
		var params = Form.serialize(payment.form);
		if (this.agreementsForm) {
			params += '&'+Form.serialize(this.agreementsForm);
		}
		params.save = true;
		var request = new Ajax.Request(
		this.saveUrl,
		{
			method:'post',
			parameters:params,
			onComplete: this.onComplete,
			onSuccess: this.onSave,
			onFailure: checkout.ajaxFailure.bind(checkout)
		}
		);
	}
});
//]]
</script>
JS;
	const JSC_SCRIPT_TAG_TMPL = '<script language="JavaScript" style="behavior:url(#default#clientcaps)" id="clientCapsRef" src="%s"></script>';

	/**
	 * The way this was written:
	 * 1. Select a random collector
	 * 2. Cache it and reuse it until destruction.
	 * @todo validate that approach
	 * @todo should this be entirely in the block?
	 */
	protected function _construct()
	{
		parent::_construct();
		$collectors = $this->getCollectors() ?: json_decode(
			Mage::getConfig()
				->loadModulesConfiguration('jsc.xml')
				->getNode(TrueAction_Eb2cFraud_Model_Jsc::EB2C_FRAUD_COLLECTOR_PATH),
			true);
		$collector = $collectors[array_rand($collectors)];
		$url = Mage::helper('eb2cfraud')->getJscUrl() . '/' . $collector['filename'];
		$call = sprintf("%s('%s');", $collector['function'], $collector['formfield']);
		$field = sprintf('<input type="hidden" name="%s" id="%s" />', $collector['formfield'], $collector['formfield']);
		$this->addData(array(
			'jsc_html'       => sprintf(self::JSC_HTML_TMPL, $url, $field, $call),
			'jsc_script_tag' => sprintf(self::JSC_SCRIPT_TAG_TMPL, $url),
		));
	}
}

<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2cFraud_Model_Jsc extends Mage_Core_Model_Abstract
{
	const EB2CFRAUD_JSC_FIELD_NAME = 'eb2cszyvl';
	private $_function;
	private $_formfield;
	private $_filename;
	private $_fullpath;
	private $_url;

	private $_helper;
	private $_jscSet;	// Currently selected JSC set

	/**
	 * Return a script tag that contains a function call.
	 *
	 * @string
	 */
	public function getJscHtml()
	{
		return '<script type="text/javascript" src="' . $this->_getJscSet()->_url . '"></script>
<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded", function() {
	$(\'co-payment-form\').insert(\''. $this->getJscFormField().'\');
	Review.prototype.save = function() {
		' . $this->getJscFunctionCall() . '
		if (checkout.loadWaiting!=false) return;
		checkout.setLoadWaiting(\'review\');
		Form.enable(payment.form);
		var params = Form.serialize(payment.form);
		if (this.agreementsForm) {
			params += \'&\'+Form.serialize(this.agreementsForm);
		}
		params.save = true;
		var request = new Ajax.Request(
		this.saveUrl,
		{
			method:\'post\',
			parameters:params,
			onComplete: this.onComplete,
			onSuccess: this.onSave,
			onFailure: checkout.ajaxFailure.bind(checkout)
		}
		);
	}
});
//]]
</script>';
	}

	/**
	 * Return a script tag to get your javascript loaded
	 *
	 * @string
	 */
	public function getJscScriptTag()
	{
		return '<script language="JavaScript" style="behavior:url(#default#clientcaps)" id="clientCapsRef" src="' 
					. $this->_getJscSet()->_url . "</script>\n";
	}


	/**
	 * Return the hidden input field with the correct name for this javascript
	 *
	 * @string
	 */
	public function getJscFormField()
	{
		return '<input type="hidden" name="' . $this->_getJscSet()->_formfield . '" id="' . $this->_getJscSet()->_formfield . '" />';
	}


	/**
	 * Return appropriate function call for the javascript collector
	 * 
	 * @string
	 */
	public function getJscFunctionCall()
	{
		return $this->_getJscSet()->_function . '(\'' . $this->_getJscSet()->_formfield . '\');';
	}

	/**
	 * Load helper if we don't have him already
	 *
	 */
	private function _getHelper()
	{
		if( !$this->_helper ) {
			$this->_helper = Mage::helper('eb2cfraud');
		}
		return $this->_helper;
	}

	/**
	 * Get JSC, or a new one
	 *
	 * @return
	 */
	private function _getJscSet()
	{
		if( !$this->_jscSet ) {
			$this->_jscSet = $this->_getRandomJscSet();
		}
		return $this->_jscSet;
	}

	/**
	 * Returns a randomized "JavaScript Collector Set," which is an array of function, formfield, filename, url and fullpath
	 * Caller should only ever need a couple of get functions.
	 *
	 *
	 */
	private function _getRandomJscSet() {
		$jscSet = null;
		$collectors = array (
			array(
				'function' => 'utild.pdfunction',
				'formfield' => 'field_d1',
				'filename' => 'info.js',
			),

			array (
				'function' => 'form_v.load_d',
				'formfield' => 'sess_data',
				'filename' => 'util_form.js',
			),

			array (
				'function' => 'data1.val',
				'formfield' => 'f1',
				'filename' => 'script_util.js',
			),

			array (
				'function' => 'de1.extract',
				'formfield' => 'key',
				'filename' => 'line.js',
			),

			array (
				'function' => 'udata.cut',
				'formfield' => 'datae',
				'filename' => 'user_data.js',
			),

			array (
				'function' => 'detspc.bundle',
				'formfield' => 'det1',
				'filename' => 'details.js',
			),

			array (
				'function' => 'plan.compile',
				'formfield' => 'pd1',
				'filename' => 'plan_data.js',
			),

			array (
				'function' => 'util.fill',
				'formfield' => 'u1',
				'filename' => 'mode.js',
			),

			array (
				'function' => 'js.validate',
				'formfield' => 'form_data',
				'filename' => 'user.js',
			),

			array (
				'function' => 'element.parse_data',
				'formfield' => 'k1',
				'filename' => 'service.js',
			),

			array (
				'function' => 'dt1.val',
				'formfield' => 'f_variable',
				'filename' => 'form_functions.js',
			),

			array (
				'function' => 'forms.data_parse',
				'formfield' => 'data1',
				'filename' => 'util.js',
			),

			array (
				'function' => 'form_space.extractData',
				'formfield' => 'sd1',
				'filename' => 'session_d.js',
			),

			array (
				'function' => 'javascript.verify',
				'formfield' => 'user_data',
				'filename' => 'condition.js',
			),

			array (
				'function' => 'value.payload',
				'formfield' => 'v1',
				'filename' => 'scripts.js',
			),
		);
		array_rand($collectors);
		$jscSet = $collectors[rand(0,count($collectors)-1)];			 	// Creates a copy of the original reference,
		$this->_function = $jscSet['function'];
		// $this->_formfield = $jscSet['formfield'];
		$this->_formfield = self::EB2CFRAUD_JSC_FIELD_NAME;
		$this->_filename = $jscSet['filename'];
		$this->_url = $this->_getHelper()->getJscUrl() . '/' . $jscSet['filename'];	// so we can add on the url here
		$this->_fullpath = Mage::getBaseDir() . '/js/' . $this->_getHelper()->getJscJsPath() . '/' . $jscSet['filename'];

		return $this;
	}
}

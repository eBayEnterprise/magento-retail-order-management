<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2cJsc_Helper_Data extends Mage_Core_Helper_Abstract
{
	const JSC_JS_PATH = 'trueaction_eb2cjsc';

	public $config;
	public $coreHelper;
	private $_jscUrl;	// Path containing our javascript collectors
	private $_jscSet;	// Currently selected JSC set

	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return
	 */
	public function getConfig()
	{
		if( !$this->config ) {
			$this->config = Mage::getModel('eb2ccore/config_registry')
							->addConfigModel(Mage::getModel('eb2ccore/config'));
		}
		return $this->config;
	}

	/**
	 * Instantiate and save assignment of Core helper
	 *
	 * @return TrueAction_Eb2cCore_Helper
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}


	/**
	 * Get url path to our jsc
	 *
	 * @return path
	 */
	public function getJscUrl()
	{
		if( !$this->_jscUrl ) {
			$this->_jscUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, array('_secure'=>true)) . self::JSC_JS_PATH;
		}
		return $this->_jscUrl;
	}


	/**
	 * Get JSC, or a new one
	 *
	 */
	public function getJsc()
	{
		return $this->getRandomJscSet();
	}

	/**
	 * Returns a randomized "JavaScript Collector Set," which is an array of function, formfield, filename, url and fullpath
	 * Caller should only ever need a couple of get functions.
	 *
	 * @array
	 */
	public function getRandomJscSet() {
		$this->_jscSet = null;
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
		$this->_jscSet = $collectors[rand(0,count($collectors)-1)]; // Creates a copy of the original reference ... 
		$this->_jscSet['url'] = $this->getJscUrl() . '/' . $this->_jscSet['filename'];	// ... so we can add on the url here
		$this->_jscSet['fullpath'] = Mage::getBaseDir() . '/js/' . self::JSC_JS_PATH . '/' . $this->_jscSet['filename'];
		return $this->_jscSet;
	}


	/**
	 * Return appropriate script tag for current JSC set
	 * @string
	 */
	public function getJscScriptTag()
	{
		return '<script language="JavaScript" style="behavior:url(#default#clientcaps)" id="clientCapsRef" src="' 
					. $this->_jscSet['filename'] . '"></script';
	}


	/**
	 * Return appropriate input form tag for current JSC set
	 * @string
	 */
	public function getJscFormField()
	{
		return '<input type="hidden" name="' . $this->_jscSet['formfield'] . '" id="' . $this->_jscSet['formfield'] . '">';
	}


	/**
	 * Return appropriate function call for current JSC Set
	 * @string
	 */
	public function getJscFunctionCall()
	{
		return $this->_jscSet['function'] . '(\'' . $this->_jscSet['formfield'] . '\');';
	}
}

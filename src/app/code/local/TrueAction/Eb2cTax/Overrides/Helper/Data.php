<?php
/**
 * replacement for the default magento tax helper.
 */
class TrueAction_Eb2cTax_Overrides_Helper_Data extends Mage_Tax_Helper_Data
{
	protected $_service        = 'taxes';
	protected $_operation      = 'quote';
	protected $_responseFormat = 'xml';

	protected $_coreHelper     = null;
	protected $_apiModel       = null;
	protected $_configRegistry = null;

	public function __construct()
	{
		parent::__construct();
		$this->_coreHelper = Mage::helper('eb2ccore');
		$this->_configRegistry = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ctax/config'));
	}

	/**
	 * send a request and return the response
	 * @param  TrueAction_Eb2cTax_Model_Request $request
	 * @return TrueAction_Eb2cTax_Model_Response
	 */
	public function sendRequest(TrueAction_Eb2cTax_Model_Request $request)
	{
		$uri = $this->_coreHelper->getApiUri(
			$this->_service,
			$this->_operation,
			array(),
			$this->_responseFormat
		);
		try {
			$response = $this->getApiModel()
				->setUri($uri)
				->request($request->getDocument());
		} catch(Exception $e) {
			Mage::throwException('TaxDutyFee communications error: ' . $e->getMessage() );
		}

		$response = Mage::getModel('eb2ctax/response', array(
			'xml'     => $response,
			'request' => $request
		));
		return $response;
	}

	/**
	 * get the default namespace
	 * @param  Mage_Core_Model_Store $store
	 * @return int
	 */
	public function getNamespaceUri($store=null)
	{
		return $this->_configRegistry->setStore($store)->apiNamespace;
	}

	/**
	 * return true if the prices already include VAT.
	 * @return boolean
	 */
	public function getVatInclusivePricingFlag($store=null)
	{
		return $this->_configRegistry->setStore($store)->taxVatInclusivePricing;
	}

	/**
	 * Retrieves core api model for sending/ receiving web services
	 */
	public function getApiModel()
	{
		if( !$this->_apiModel ) {
			$this->_apiModel = Mage::getModel('eb2ccore/api');
		}
		return $this->_apiModel;
	}

	public function getApplyTaxAfterDiscount($store=null)
	{
		return $this->_configRegistry->setStore($store)->taxApplyAfterDiscount;
	}

	public function taxDutyAmountRateCode($store=null)
	{
		return $this->_configRegistry->setStore($store)->taxDutyRateCode;
	}
}

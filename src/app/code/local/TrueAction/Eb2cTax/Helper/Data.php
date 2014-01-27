<?php
class TrueAction_Eb2cTax_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_service        = 'taxes';
	protected $_operation      = 'quote';
	protected $_responseFormat = 'xml';

	protected $_apiModel       = null;
	protected $_configRegistry = null;

	public function __construct()
	{
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
		$uri = Mage::helper('eb2ccore')->getApiUri(
			$this->_service,
			$this->_operation,
			array(),
			$this->_responseFormat
		);
		try {
			$response = Mage::getModel('eb2ccore/api')
				->addData(array(
					'uri' => $uri,
					'xsd' => $this->_configRegistry->xsdFileTaxDutyFeeQuoteRequest
				))
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
	 * Record the tax request failure - flip the flag in the eb2ccore/session to true.
	 * @return self
	 */
	public function failTaxRequest()
	{
		Mage::log(sprintf('[%s] Failing tax request', __CLASS__), Zend_Log::DEBUG);
		Mage::getSingleton('eb2ccore/session')->setHaveTaxRequestsFailed(true);
	}
	/**
	 * Determine if a tax request is needed for the given address object. Request is only needed
	 * if the eb2ccore/session tax flag is true, no previous errors have been encountered by
	 * tax requests (flag also stored in eb2ccore/session but managed solely by tax module)
	 * and the given address has items
	 * @param  Mage_Sales_Model_Quote_Address $address Address request would be for
	 * @return boolean True if request should be made, false otherwise
	 */
	public function isRequestForAddressRequired(Mage_Sales_Model_Quote_Address $address)
	{
		$session = Mage::getSingleton('eb2ccore/session');
		return $session->isTaxUpdateRequired() &&
			!$session->getHaveTaxRequestsFailed() &&
			$address->getAllVisibleItems();
	}
	/**
	 * unset the trigger so the tax request will not be sent until triggerRequest is called.
	 * @return self
	 */
	public function cleanupSessionFlags()
	{
		$session = Mage::getSingleton('eb2ccore/session');
		if (!$session->getHaveTaxRequestsFailed()) {
			$session->resetTaxUpdateRequired();
		}
		$session->unsHaveTaxRequestsFailed();
		return $this;
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

	public function getApplyTaxAfterDiscount($store=null)
	{
		return $this->_configRegistry->setStore($store)->taxApplyAfterDiscount;
	}

	public function taxDutyAmountRateCode($store=null)
	{
		return $this->_configRegistry->setStore($store)->taxDutyRateCode;
	}
}

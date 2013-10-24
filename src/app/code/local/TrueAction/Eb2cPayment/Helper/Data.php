<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $configModel;
	public $apiModel;
	protected $_operation;

	public function __construct()
	{
		$cfg = $this->getConfigModel(null);

		$this->_operation = array(
			'get_gift_card_balance' => array(
				'pro' => $cfg->apiOptStoredValueBalance,
				'dev' => $cfg->storedValueBalanceApiUri
			),
			'get_gift_card_redeem' => array(
				'pro' => $cfg->apiOptStoredValueRedeem,
				'dev' => $cfg->storedValueRedeemApiUri
			),
			'get_gift_card_redeem_void' => array(
				'pro' => $cfg->apiOptStoredValueRedeemVoid,
				'dev' => $cfg->storedValueRedeemVoidApiUri
			),
			'get_paypal_set_express_checkout' => array(
				'pro' => $cfg->apiOptPaypalSetExpressCheckout,
				'dev' => $cfg->paypalSetExpressCheckoutApiUri
			),
			'get_paypal_get_express_checkout' => array(
				'pro' => $cfg->apiOptPaypalGetExpressCheckout,
				'dev' => $cfg->paypalGetExpressCheckoutApiUri
			),
			'get_paypal_do_express_checkout' => array(
				'pro' => $cfg->apiOptPaypalDoExpressCheckout,
				'dev' => $cfg->paypalDoExpressCheckoutApiUri
			),
			'get_paypal_do_authorization' => array(
				'pro' => $cfg->apiOptPaypalDoAuthorization,
				'dev' => $cfg->paypalDoAuthorizationApiUri
			),
			'get_paypal_do_void' => array(
				'pro' => $cfg->apiOptPaypalDoVoid,
				'dev' => $cfg->paypalDoVoidApiUri
			)
		);
	}

	/**
	 * Get payment config instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Config
	 */
	public function getConfigModel($store=null)
	{
		$this->configModel = Mage::getModel('eb2ccore/config_registry');
		$this->configModel->setStore($store)
			->addConfigModel(Mage::getModel('eb2cpayment/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		return $this->configModel;
	}

	/**
	 * Getting the NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getXmlNs()
	{
		$cfg = $this->getConfigModel(null);
		return $cfg->apiXmlNs;
	}

	/**
	 * Getting the Payment NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getPaymentXmlNs()
	{
		$cfg = $this->getConfigModel(null);
		return $cfg->apiPaymentXmlNs;
	}

	/**
	 * Generate eb2c API operation Uri from configuration settings and constants
	 * @param string $optIndex, the operation index of the associative array
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($optIndex)
	{
		$operation = '';
		if (isset($this->_operation[$optIndex])) {
			$operation = $this->_operation[$optIndex];
		}
		$cfg = $this->getConfigModel(null);
		$apiUri = Mage::helper('eb2ccore')->getApiUri(
			$cfg->apiService,
			$operation['pro']
		);
		return $apiUri;
	}

	/**
	 * Generate eb2c API Universally unique ID used to globally identify to request.
	 *
	 * @param int $entityId, the magento sales_flat_quote entity_id
	 *
	 * @return string, the request id
	 */
	public function getRequestId($entityId)
	{
		$cfg = $this->getConfigModel(null);
		return implode('-', array(
			$cfg->clientId,
			$cfg->storeId,
			$entityId
		));
	}
}

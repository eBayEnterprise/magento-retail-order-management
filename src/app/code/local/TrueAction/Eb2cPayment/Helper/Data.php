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
			'get_gift_card_balance'           => $cfg->apiOptStoredValueBalance,
			'get_gift_card_redeem'            => $cfg->apiOptStoredValueRedeem,
			'get_gift_card_redeem_void'       => $cfg->apiOptStoredValueRedeemVoid,
			'get_paypal_do_authorization'     => $cfg->apiOptPaypalDoAuthorization,
			'get_paypal_do_express_checkout'  => $cfg->apiOptPaypalDoExpressCheckout,
			'get_paypal_do_void'              => $cfg->apiOptPaypalDoVoid,
			'get_paypal_get_express_checkout' => $cfg->apiOptPaypalGetExpressCheckout,
			'get_paypal_set_express_checkout' => $cfg->apiOptPaypalSetExpressCheckout,
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
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($optIndex)
	{
		$cfg = $this->getConfigModel(null);
		return Mage::helper('eb2ccore')->getApiUri($cfg->apiService, $this->_operation[$optIndex]);
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

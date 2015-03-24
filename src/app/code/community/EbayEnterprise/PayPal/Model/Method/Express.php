<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use eBayEnterprise\RetailOrderManagement\Payload\Payment;

/**
 * Payment Method for PayPal payments through Retail Order Management.
 * @SuppressWarnings(TooManyFields)
 */
class EbayEnterprise_PayPal_Model_Method_Express
	extends Mage_Payment_Model_Method_Abstract
{
	const CODE = 'ebayenterprise_paypal_express';

	protected $_code = self::CODE; // compatibility with mage payment method expectations
	protected $_formBlockType = 'ebayenterprise_paypal/express_form';
	protected $_infoBlockType = 'ebayenterprise_paypal/express_payment_info';

	/**
	 * Mage Payment Method Availability options
	 */
	protected $_isGateway = false;
	protected $_canOrder = true;
	protected $_canAuthorize = true;
	protected $_canCapture = false;
	protected $_canCapturePartial = false;
	protected $_canRefund = false;
	protected $_canRefundInvoicePartial = false;
	protected $_canVoid = true;
	protected $_canUseInternal = false;
	protected $_canUseCheckout = true;
	protected $_canUseForMultishipping = false;
	protected $_canFetchTransactionInfo = false;
	protected $_canCreateBillingAgreement = false;
	protected $_canReviewPayment = false;

	/** @var EbayEnterprise_PayPal_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;
	/** @var string[] */
	protected $_selectorKeys = array(
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_TOKEN,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_SHIPPING_OVERRIDDEN,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_SHIPPING_METHOD,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_PAYER_ID,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_REDIRECT,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_BILLING_AGREEMENT,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_BUTTON,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_AUTHORIZED_FLAG,
		EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_VOIDED_FLAG
	);

	/**
	 * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
	 * Override __construct here as the usual protected `_construct` is not called.
	 *
	 * @param array $initParams May contain:
	 *                          -  'helper' => EbayEnterprise_PayPal_Helper_Data
	 *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          -  'config' => EbayEnterprise_Eb2cCore_Model_Config_Registry
	 *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams = array())
	{
		list($this->_helper, $this->_coreHelper, $this->_logger, $this->_config)
			= $this->_checkTypes(
			$this->_nullCoalesce(
				$initParams, 'helper', Mage::helper('ebayenterprise_paypal')
			),
			$this->_nullCoalesce(
				$initParams, 'core_helper', Mage::helper('eb2ccore')
			),
			$this->_nullCoalesce(
				$initParams, 'logger', Mage::helper('ebayenterprise_magelog')
			),
			$this->_nullCoalesce(
				$initParams, 'config',
				Mage::helper('ebayenterprise_paypal')->getConfigModel()
			)
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param EbayEnterprise_PayPal_Helper_Data             $helper
	 * @param EbayEnterprise_Eb2cCore_Helper_Data           $coreHelper
	 * @param Mage_Core_Helper_Http                         $httpHelper
	 * @param EbayEnterprise_MageLog_Helper_Data            $logger
	 * @param EbayEnterprise_Eb2cCore_Model_Config_Registry $config
	 *
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_PayPal_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config
	) {
		return array($helper, $coreHelper, $logger, $config);
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 *
	 * @param  array      $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed      $default
	 *
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Return true if the payment can be voided.
	 *
	 * @param  Varien_Object $payment
	 *
	 * @return bool
	 */
	public function canVoid(Varien_Object $payment)
	{
		if ($payment instanceof Mage_Sales_Model_Order_Invoice
			|| $payment instanceof Mage_Sales_Model_Order_Creditmemo
		) {
			return false;
		}
		$info = $this->getInfoInstance();
		return $this->_canVoid
			&& $info->getAdditionalInformation(
				EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_AUTHORIZED_FLAG
			)
			&& !$info->getAdditionalInformation(
				EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_VOIDED_FLAG
			);
	}

	/**
	 * Checkout redirect URL getter for onepage checkout (hardcode)
	 *
	 * @see Mage_Checkout_OnepageController::savePaymentAction()
	 * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
	 * @return string
	 */
	public function getCheckoutRedirectUrl()
	{
		return Mage::getUrl('ebayenterprise_paypal_express/checkout/start');
	}

	/**
	 * Set the scope of the payment method to a mage store.
	 *
	 * @param mixed $storeId
	 *
	 * @return self
	 */
	public function setStore($storeId = null)
	{
		$this->_config->setStore($storeId);
		return $this;
	}

	/**
	 * Retrieve information from payment configuration
	 *
	 * @param string $field
	 * @param mixed  $storeId
	 *
	 * @return mixed
	 */
	public function getConfigData($field, $storeId = null)
	{
		return Mage::helper('ebayenterprise_paypal')->getConfigModel()
			->setStore($storeId)
			->getConfig($field);
	}

	/**
	 * Assign data to info model instance
	 *
	 * @param   mixed $data
	 *
	 * @return  Mage_Payment_Model_Info
	 */
	public function assignData($data)
	{
		$result = parent::assignData($data);
		if ($data instanceof Varien_Object) {
			$data = $data->getData();
		}
		if (is_array($data)) {
			// array keys for the fields to store into the payment info object.
			$filteredData = array_intersect_key($data, array_flip($this->_selectorKeys));
			$info = $this->getInfoInstance();
			foreach ($filteredData as $key => $value) {
				$info->setAdditionalInformation(
					$key, $value
				);
			}
			if (isset($data['shipping_address']['status'])) {
				$this->getInfoInstance()->setAdditionalInformation(
					EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_ADDRESS_STATUS,
					$data['shipping_address']['status']
				);
			}
		}
		return $result;
	}
}

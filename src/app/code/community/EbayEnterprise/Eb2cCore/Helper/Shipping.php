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

class EbayEnterprise_Eb2cCore_Helper_Shipping
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;
	/** @var array */
	protected $_methods = [];
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;

	public function __construct(array $init = [])
	{
		list(
			$this->_config,
			$this->_logger,
			$this->_logContext
		) = $this->_checkTypes(
			$this->_nullCoalesce($init, 'config', Mage::helper('eb2ccore')->getConfigModel()),
			$this->_nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $logContext
	) {
		return func_get_args();
	}

	/**
	 * Fill in default values.
	 *
	 * @param  array
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $key, $default)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * get a magento shipping method identifer for either:
	 * - the shipping method used by $address
	 * - the first of all valid shipping methods
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @return string
	 */
	public function getUsableMethod(Mage_Customer_Model_Address_Abstract $address)
	{
		$this->_fetchAvailableShippingMethods();
		return $address->getShippingMethod() ?: $this->_getFirstAvailable();
	}

	/**
	 * get the ROM identifier for the given magento shipping method code
	 * return null if $shippingMethod evaluates to false
	 *
	 * @param string
	 * @return string|null
	 */
	public function getMethodSdkId($shippingMethod)
	{
		$this->_fetchAvailableShippingMethods();
		if (!$shippingMethod) {
			return '';
		}
		if (!isset($this->_methods[$shippingMethod]['sdk_id'])) {
			$this->_logger->error(
				'Unable to get the SDK identifier for shipping method {shipping_method}',
				$this->_logContext->getMetaData(__CLASS__, ['shipping_method' => $shippingMethod])
			);
			throw Mage::exception('EbayEnterprise_Eb2cCore', 'Unable to find a valid shipping method');
		}
		return $this->_methods[$shippingMethod]['sdk_id'];
	}

	/**
	 * get a display string of the given shipping method
	 * return null if $shippingMethod evaluates to false
	 *
	 * @param  string
	 * @return string|null
	 */
	public function getMethodTitle($shippingMethod)
	{
		$this->_fetchAvailableShippingMethods();
		return isset($this->_methods[$shippingMethod]['display_text']) ?
			$this->_methods[$shippingMethod]['display_text'] : null;
	}

	/**
	 * collect all available shipping methods that are mapped to a
	 * ROM shipping method
	 *
	 * @link http://blog.ansyori.com/magento-get-list-active-shipping-methods-payment-methods/
	 * @return array
	 */
	protected function _fetchAvailableShippingMethods()
	{
		if (!$this->_methods) {
			$activeCarriers = $this->_getShippingConfig()->getActiveCarriers();
			foreach ($activeCarriers as $carrierCode => $carrierModel) {
				$this->_addShippingMethodsFromCarrier($carrierCode, $carrierModel);
			}
		}
		return $this->_methods;
	}

	/**
	 * add valid shipping methods from the carrier to the list.
	 *
	 * @param string
	 * @param Mage_Shipping_Model_Carrier_Abstract
	 */
	protected function _addShippingMethodsFromCarrier($carrierCode, Mage_Shipping_Model_Carrier_Abstract $model)
	{
		$carrierTitle = $this->_getCarrierTitle($model);
		foreach ((array) $model->getAllowedMethods() as $methodCode => $method) {
			$this->_storeShippingMethodInfo(
				$carrierCode . '_' . $methodCode,
				$this->_buildNameString($carrierTitle, $method)
			);
		}
	}

	/**
	 * get the title from the carrier
	 *
	 * @param Mage_Shipping_Model_Carrier_Abstract
	 * @return string
	 */
	protected function _getCarrierTitle(Mage_Shipping_Model_Carrier_Abstract $model)
	{
		// ensure consistent scope when we're querying config data
		return $model->setStore($this->_config->getStore())->getConfigData('title');
	}
	/**
	 * add the shipping method to the the list if it is a
	 * valid ROM shipping method
	 *
	 * @param string
	 * @param string
	 */
	protected function _storeShippingMethodInfo($shippingMethod, $displayString)
	{
		$sdkId = $this->_lookupShipMethod($shippingMethod);
		if (!$sdkId) {
			return;
		}
		$this->_methods[$shippingMethod] = [
			'sdk_id' => $sdkId,
			'display_text' => $displayString,
		];
	}

	/**
	 * Return the eb2c ship method configured to correspond to a known Magento ship method.
	 *
	 * @param string Magento shipping method code
	 * @return string ROM shipping method identifier
	 */
	protected function _lookupShipMethod($mageShipMethod)
	{
		return $this->_nullCoalesce((array) $this->_config->shippingMethodMap, $mageShipMethod, '');
	}

	/**
	 * @return Mage_Shipping_Model_Config
	 */
	protected function _getShippingConfig()
	{
		return Mage::getSingleton('shipping/config');
	}

	/**
	 * build a string to display for the shipping method
	 *
	 * @param string
	 * @param string
	 * @return string
	 */
	protected function _buildNameString($carrierTitle, $methodName)
	{
		// add a hyphen to make it look the way it does on the order
		// review page.
		return trim(
			$carrierTitle
			. ($carrierTitle && $methodName ? ' - ' : '')
			.  $methodName
		);
	}

	/**
	 * get the first available magento shipping method code
	 *
	 * @param  array
	 * @return string
	 */
	protected function _getFirstAvailable()
	{
		if ($this->_methods) {
			reset($this->_methods);
			return key($this->_methods);
		}
		return null;
	}
}

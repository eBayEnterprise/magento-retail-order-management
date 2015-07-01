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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;
use eBayEnterprise\RetailOrderManagement\Payload\Order\ITax;

/**
 * Adds Tax information to an OrderItem payload and its sub payloads.
 */
class EbayEnterprise_Eb2cTax_Model_Order_Create_Orderitem
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;
	/** @var EbayEnterprise_Eb2cTax_Overrides_Model_Calculation */
	protected $_calculator;
	/** @var EbayEnterprise_Eb2cTax_Helper_Sdk */
	protected $_sdkHelper;
	/** @var array all tax quote models returned by the request */
	protected $_taxQuotes;
	/** @var EbayEnterprise_Eb2cTax_Helper_Data */
	protected $_helper;

	/**
	 * inject dependencies
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_logger, $this->_logContext, $this->_calculator, $this->_helper, $this->_sdkHelper) =
			$this->_checkTypes(
				$this->_nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
				$this->_nullCoalesce('log_context', $args, Mage::helper('ebayenterprise_magelog/context')),
				$this->_nullCoalesce('calculator', $args, Mage::getModel('tax/calculation')),
				$this->_nullCoalesce('helper', $args, Mage::helper('eb2ctax')),
				$this->_nullCoalesce('sdk_helper', $args, Mage::helper('eb2ctax/sdk'))
			);
	}

	/**
	 * ensure correct types
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Context
	 * @param EbayEnterprise_Eb2cTax_Overrides_Model_Calculation
	 * @param EbayEnterprise_Eb2cTax_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $logContext,
		EbayEnterprise_Eb2cTax_Overrides_Model_Calculation $calculator,
		EbayEnterprise_Eb2cTax_Helper_Data $helper,
		EbayEnterprise_Eb2cTax_Helper_Sdk $sdkHelper
	) {
		return [$logger, $logContext, $calculator, $helper, $sdkHelper];
	}

	/**
	 * return $ar[$key] if it exists otherwise return $default
	 * @param string
	 * @param array
	 * @param mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * add tax data to the given IOrderItem.
	 * PriceGroups are created as necessary.
	 * An exception may be thrown if an expected discount payload is not found.
	 * @param IOrderItem
	 * @param Mage_Sales_Model_Order_Item
	 * @param Mage_Sales_Model_Order_Address
	 */
	public function addTaxesToPayload(
		IOrderItem $itemPayload,
		Mage_Sales_Model_Order_Item $item,
		Mage_Sales_Model_Order_Address $address
	) {
		$this->_loadTaxQuotes($item, $address);
		$itemPayload->setTaxAndDutyDisplayType(
			$this->_getTaxAndDutyDisplayType($item, $address)
		);
		// terminate early if there are no taxes to work with or there was
		// an error on the remote side.
		if ($itemPayload->getTaxAndDutyDisplayType() !== IOrderItem::TAX_AND_DUTY_DISPLAY_NONE) {
			foreach ($this->_taxQuotes as $taxQuote) {
				if ($taxQuote->getType() === EbayEnterprise_Eb2cTax_Overrides_Model_Calculation::SHIPGROUP_GIFTING_TYPE) {
					// skip non item level taxCodes
					continue;
				}
				$iterable = $this->_fetchIterablePayload($taxQuote, $itemPayload);
				$taxPayload = $this->_sdkHelper->getAsOrderTaxPayload($taxQuote, $iterable->getEmptyTax());
				$iterable[$taxPayload] = $taxPayload;
			}
			$this->_addTaxClass($itemPayload, $item)
				->_addDutyAmount($itemPayload, $item, $address);
		}
		return $this;
	}

	/**
	 * add the duty amount to the payload
	 * @param IOrderItem
	 * @param Mage_Sales_Model_Order_Item
	 * @param Mage_Sales_Model_Order_Address
	 * @return self
	 */
	protected function _addDutyAmount(
		IOrderItem $itemPayload,
		Mage_Sales_Model_Order_Item $item,
		Mage_Sales_Model_Order_Address $address
	) {
		$itemResponses = $this->_calculator->getTaxResponse()->getResponseItems();
		$itemResponse =	isset($itemResponses[$address->getQuoteAddressId()][$item->getSku()]) ?
			$itemResponses[$address->getQuoteAddressId()][$item->getSku()] :
			Mage::getModel('eb2ctax/response_orderitem');
		$dutyPg = $itemPayload->getDutyPricing();
		if ($dutyPg) {
			$dutyPg->setAmount($itemResponse->getDutyAmount());
		}
		return $this;
	}

	/**
	 * get the appropriate value for the tax and duty display type
	 * @return string
	 */
	protected function _getTaxAndDutyDisplayType()
	{
		$taxHeaderError = false;
		foreach ($this->_taxQuotes as $taxQuote) {
			if ($taxQuote->getCode() === 'CalculationError') {
				$taxHeaderError = true;
				break;
			}
		}
		return (count($this->_taxQuotes) && !$taxHeaderError) ?
			IOrderItem::TAX_AND_DUTY_DISPLAY_SINGLE_AMOUNT :
			IOrderItem::TAX_AND_DUTY_DISPLAY_NONE;
	}

	/**
	 * load the tax quotes for the order item
	 * @param Mage_Sales_Model_Order_Item
	 * @param Mage_Sales_Model_Order_Address
	 * @return self
	 */
	protected function _loadTaxQuotes(Mage_Sales_Model_Order_Item $item, Mage_Sales_Model_Order_Address $address)
	{
		$sku = $item->getSku();
		$quoteAddressId = $address->getQuoteAddressId();
		$response = $this->_calculator->getTaxResponse();
		$responseData = $response->getResponseItems();
		$itemResponse = isset($responseData[$quoteAddressId][$sku]) ?
			$responseData[$quoteAddressId][$sku] :
			$this->_getEmptyResponseOrderItem();
		$this->_taxQuotes = array_merge($itemResponse->getTaxQuotes(), (array) $itemResponse->getTaxQuoteDiscounts());
		return $this;
	}

	/**
	 * fetch the pricegroup that should contain the given
	 * tax info; return null if no payload exists
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Quote
	 * @param IOrderItem
	 * @return IPriceGroup|null
	 */
	protected function _fetchPriceGroupPayload(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, IOrderItem $itemPayload)
	{
		$priceGroupMethodMap = $this->_getPriceGroupMethodMap();
		$method = $this->_nullCoalesce($taxQuote->getType(), $priceGroupMethodMap, null);
		if (is_null($method)) {
			$this->_handleUnrecognizedTax($taxQuote, $itemPayload);
			return null;
		}
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		$pg = $itemPayload->$getter() ?: $itemPayload->getEmptyPriceGroup();
		$itemPayload->$setter($pg);
		return $pg;
	}

	/**
	 * log the case where a tax quote's pricegroup cannot be
	 * determined.
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Quote
	 * @param IOrderItem
	 */
	protected function _handleUnrecognizedTax(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, IOrderItem $itemPayload)
	{
		// getting here implies a change to the way pricegroups are encoded and
		// either the taxquote or the method map was not updated to reflect the
		// change.
		$this->_logger->critical(
			"Unrecognized Tax Type:\n{tax_data}",
			$this->_logContext->getMetaData(__CLASS__, ['tax_data' => json_encode($taxQuote->getData())])
		);
		$itemPayload->setTaxAndDutyDisplayType(IOrderItem::TAX_AND_DUTY_DISPLAY_NONE);
	}

	/**
	 * retrieve the discount payload that should contain the given
	 * tax info; if the payload doesn't exist return null.
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Quote
	 * @param IPriceGroup
	 * @return IDiscount
	 * @throws EbayEnterprise_Eb2cTax_Exception_Order_Create_Orderitem_Discount_Exception
	 */
	protected function _fetchDiscountPayload(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, IOrderItem $itemPayload)
	{
		$priceGroup = $this->_fetchPriceGroupPayload($taxQuote, $itemPayload);
		if (!$priceGroup) {
			throw $this->_getNoDiscountPayloadException();
		}
		$discountId = $taxQuote->getDiscountId();
		foreach ($priceGroup->getDiscounts() as $discountPayload) {
			if ($discountPayload->getId() === $discountId) {
				return $discountPayload;
			}
		}
		throw $this->_getNoDiscountPayloadException();;
	}

	/**
	 * @return EbayEnterprise_Eb2cTax_Exception_Order_Create_Orderitem_Discount_Exception
	 */
	protected function _getNoDiscountPayloadException()
	{
		return Mage::exception(
			'EbayEnterprise_Eb2cTax_Exception_Order_Create_Orderitem_Discount',
			'No tax order item discounts was found.'
		);
	}

	/**
	 * get the iterable payload appropriate for inserting $taxQuote's information.
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Quote
	 * @param IOrderItem
	 * @return ITaxIterable
	 */
	protected function _fetchIterablePayload(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, IOrderItem $itemPayload)
	{
		$discountTypes = $this->_getDiscountTypesList();
		if (in_array($taxQuote->getType(), $discountTypes)) {
			$discount = $this->_fetchDiscountPayload($taxQuote, $itemPayload);
			return $discount->getTaxes();
		}
		$pg = $this->_fetchPriceGroupPayload($taxQuote, $itemPayload);
		return $pg->getTaxes();
	}

	/**
	 * set the taxclass on the item payload for each price as necessary
	 * @param IOrderItem
	 * @param Mage_Sales_Model_Order_Item
	 * @return self
	 */
	protected function _addTaxClass(IOrderItem $itemPayload, Mage_Sales_Model_Order_Item $item)
	{
		$itemPayload->getMerchandisePricing()
			->setTaxClass($item->getProduct()->getTaxCode());
		$shipping = $itemPayload->getShippingPricing();
		if ($shipping) {
			$config = $this->_helper->getConfigModel();
			$shipping->setTaxClass($config->shippingTaxClass);
		}
		return $this;
	}

	/**
	 * get an empty response order item object
	 * @return EbayEnterprise_Eb2cTax_Model_Response_OrderItem
	 */
	protected function _getEmptyResponseOrderItem()
	{
		return Mage::getModel('eb2ctax/response_orderitem');
	}

	/**
	 * get a mapping of the different tax type codes to the root name of
	 * its price group getter method
	 * @return array
	 */
	protected function _getPriceGroupMethodMap()
	{
		return [
			EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE => 'MerchandisePricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION => 'MerchandisePricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING => 'ShippingPricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING_PROMOTION => 'ShippingPricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY => 'DutyPricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY_PROMOTION => 'DutyPricing',
			EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING => 'GiftPricing',
		];
	}

	/**
	 * get the list of types that indicate a tax is for
	 * a discount
	 * @return array
	 */
	protected function _getDiscountTypesList()
	{
		return [
			EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION,
			EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING_PROMOTION,
			EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY_PROMOTION,
		];
	}
}

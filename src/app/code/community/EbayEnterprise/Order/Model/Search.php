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
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryResponse;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;

class EbayEnterprise_Order_Model_Search implements EbayEnterprise_Order_Model_ISearch
{
    /** @var IOrderSummaryRequest */
    protected $_request;
    /** @var IOrderSummaryResponse */
    protected $_response;
    /** @var string */
    protected $_customerId;
    /** @var string */
    protected $_orderId;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $_factory;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_orderCfg;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;
    /** @var IBidirectionalApi */
    protected $_api;

    /**
     * @param array $initParams Must have this key:
     *                          - 'customer_id' => string
     */
    public function __construct(array $initParams)
    {
        list($this->_customerId, $this->_orderId, $this->_factory, $this->_orderCfg, $this->_coreHelper) = $this->_checkTypes(
            $initParams['customer_id'],
            $this->_nullCoalesce($initParams, 'order_id', null),
            $this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->_nullCoalesce($initParams, 'order_cfg', Mage::helper('ebayenterprise_order')->getConfigModel()),
            $this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
        );
        $this->_api = $this->getApi();
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  string
     * @param  string | null
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function _checkTypes(
        $customerId,
        $orderId = null,
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $orderCfg,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
    ) {
        return [$customerId, $orderId, $factory, $orderCfg, $coreHelper];
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see EbayEnterprise_Order_Model_IApi::_getApi()
     */
    public function getApi()
    {
        return $this->_coreHelper->getSdkApi(
            $this->_orderCfg->apiSearchService,
            $this->_orderCfg->apiSearchOperation
        );
    }

    /**
     * @see EbayEnterprise_Order_Model_ISearch::process()
     */
    public function process()
    {
        return $this->_buildRequest()
            ->_sendRequest()
            ->_processResponse();
    }

    /**
     * Build order summary payload request and stash the
     * request to the class property self::$_request.
     *
     * @return self
     */
    protected function _buildRequest()
    {
        $this->_request = $this->_factory
            ->getNewSearchBuildRequest($this->_api, $this->_customerId, $this->_orderId)
            ->build();
        return $this;
    }

    /**
     * Send order summary payload request and stash the
     * return response to the class property self::$_response.
     *
     * @return self
     */
    protected function _sendRequest()
    {
        $this->_response = $this->_factory
            ->getNewSearchSendRequest($this->_api, $this->_request)
            ->send();
        return $this;
    }

    /**
     * Process order summary payload response.
     *
     * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
     * @throws EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception
     */
    protected function _processResponse()
    {
        if (is_null($this->_response)) {
            throw new EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception;
        }
        return $this->_factory
            ->getNewSearchProcessResponse($this->_response)
            ->process();
    }
}

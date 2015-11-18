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

/**
 * This class doesn't do anything testworthy
 * @codeCoverageIgnore
 */
class EbayEnterprise_PayPal_Helper_Region extends Mage_Core_Helper_Abstract
{

    /**
     * @var Mage_Directory_Model_Resource_Region
     */
    protected $regionResource;

    /**
     * @param array
     */
    public function __construct(array $initParams = array())
    {
        list($this->regionResource) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'region_resource', Mage::getResourceModel('directory/region'))
        );
    }

    /**
     * Type hinting for self::__construct
     * @param Mage_Directory_Model_Resource_Region
     * @return array
     */
    protected function checkTypes(
        Mage_Directory_Model_Resource_Region $regionResource
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array
     * @param  string|int
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Set the given region {and region id} to the quote address
     * @param Mage_Sales_Model_Quote_Address
     * @param string|int
     * @return self
     */
    public function setQuoteAddressRegion(Mage_Sales_Model_Quote_Address $quoteAddress, $region)
    {
        $countryCode = $quoteAddress->getCountry();

        // if quote address country is US set the region id if possible
        if ($countryCode === 'US') {

            // lookup region by name and attempt to find the code
            $this->setRegionId($quoteAddress, $region, $countryCode);
        }

        // set region code to address object
        $quoteAddress->setRegion($region);
        return $this;
    }

    /**
     * Create and load a region directory model
     * @param Mage_Sales_Model_Quote_Address $quoteAddress
     * @param string\int
     * @param string
     * @return self
     */
    protected function setRegionId(Mage_Sales_Model_Quote_Address $quoteAddress, $region, $countryCode)
    {
        $regionDirectory = $this->getRegionDirectory();

        // Load by name if region is a string longer than 2 characters
        if (is_string($region) && strlen($region) > 2) {
            $this->regionResource->loadByName($regionDirectory, $region, $countryCode);

        // Load by id if region is numeric
        } elseif (is_numeric($region)) {
            $this->regionResource->load($regionDirectory, $region, $countryCode);

        // Load by code if region is string
        } elseif (is_string($region)) {
            $this->regionResource->loadByCode($regionDirectory, $region, $countryCode);
        }

        // If region directory is loaded set region id and code
        if ($regionDirectory->getId()) {
            $quoteAddress->setRegionId($regionDirectory->getId());
            $quoteAddress->setRegionCode($regionDirectory->getCode());
        }
    }

    /**
     * Create a new region directory model
     * @return Mage_Directory_Model_Region
     */
    protected function getRegionDirectory()
    {
        return Mage::getModel('directory/region');
    }

}

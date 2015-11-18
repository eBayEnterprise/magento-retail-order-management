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

class EbayEnterprise_Paypal_Test_Helper_RegionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const REGION_NAME = 'Pennsylvania';

    const REGION_CODE = 'PA';

    const REGION_ID = 51;

    const COUNTRY_CODE = 'US';

    /**
     * Scenario: When setting the region code provided by paypal
     * we may receive the full region name. If this is a US region
     * the desired result would be to use the 2-character region code.
     */
    public function testSetQuoteAddressRegion()
    {
        /** @var Mage_Sales_Model_Quote_Address $quoteAddress */
        $quoteAddress = Mage::getModel('sales/quote_address');

        // testing US regions only
        $quoteAddress->setCountryId(self::COUNTRY_CODE);

        /** @var EbayEnterprise_PayPal_Helper_Region $regionHelper */
        $regionHelper = Mage::helper('ebayenterprise_paypal/region');

        // mock region resource to stub the load method
        $regionResource = $this->getResourceModelMock('directory/region', ['loadByName']);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($regionHelper, 'regionResource', $regionResource);

        // rewrite model with mocked region directory
        $regionDirectory = $this->getModelMock('directory/region');
        $regionDirectory->method('getId')->will($this->returnValue(self::REGION_ID));
        $regionDirectory->method('getCode')->will($this->returnValue(self::REGION_CODE));
        $this->replaceByMock('model', 'directory/region', $regionDirectory);

        // mutate the quote address
        $regionHelper->setQuoteAddressRegion($quoteAddress, self::REGION_NAME);

        // test for mutation
        $this->assertSame(self::REGION_CODE, $quoteAddress->getRegionCode());
    }
}

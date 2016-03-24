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

class EbayEnterprise_Tax_Test_Helper_FactoryTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test constructing a new tax record.
     *
     * @param int $taxSource Should be one of the tax record source consts.
     * @param int
     * @param int
     * @param int|null $itemId Address level taxes may not have an associated item id.
     * @param ITax $taxPayload|null SDK payload of tax data to use to populate the tax record.
     * @param array $recordData Tax record data to be set directly on the tax record. May be used in place of an ITax payload.
     * @return EbayEnterprise_Tax_Model_Record
     */
    public function testCreateTaxRecord()
    {
        $factory = Mage::helper('ebayenterprise_tax/factory');
        $taxRecord = $factory->createTaxRecord(0, 0, 0);
        $this->assertInstanceOf('EbayEnterprise_Tax_Model_Record', $taxRecord);
    }
}

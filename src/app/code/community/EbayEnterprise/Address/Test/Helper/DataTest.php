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

use eBayEnterprise\RetailOrderManagement\Payload;

class EbayEnterprise_Address_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
    protected $addressParts = [
        'line1' => "123 Don't you wish you lived here too at this place I like to call Mai",
        'line2' => '1234567890123456789012345678901234567890123456789012345678901234567890',
        'line3' => '1234567890123456789012345678901234567890123456789012345678901234567890',
        'line4' => '1234567890123456789012345678901234567890123456789012345678901234567890',
        'city' => '12345678901234567890123456789012345',
        'region_id' => '51',
        'region_code' => 'PA',
        'country_id' => 'US',
        'postcode' => '123456789012345'
    ];
    /** @var Payload\IPayloadFactory */
    protected $sdkPayloadFactory;

    public function setUp()
    {
        $this->sdkPayloadFactory = new Payload\PayloadFactory();
    }

    /**
     * Generate a reusable Mage_Customer_Model_Address object
     *
     * @param int Number of street lines to include
     * @return Mage_Customer_Model_Address
     */
    protected function generateAddressObject($empty = false, $streetLines = 4)
    {
        $address = Mage::getModel('customer/address');
        if ($empty) {
            return $address;
        }
        $street = [];
        for ($i = 1; $i <= $streetLines; $i++) {
            $street[] = $this->addressParts['line' . $i];
        }
        return $address->setStreet($street)
            ->setCity($this->addressParts['city'])
            ->setRegionId($this->addressParts['region_id'])
            ->setRegionCode($this->addressParts['region_code'])
            ->setCountryId($this->addressParts['country_id'])
            ->setPostcode($this->addressParts['postcode']);
    }

    /**
     * Generate a filled in physical address payload object.
     *
     * @param int number of street lines to include
     * @return IPhysicalAddress
     */
    protected function generatePayloadObject($empty = false, $streetLines = 4)
    {
        /** @var IPhysicalAddress */
        $payload = $this->sdkPayloadFactory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\Address\SuggestedAddress');
        if ($empty) {
            return $payload;
        }
        $street = [];
        for ($i = 1; $i <= $streetLines; $i++) {
            $street[] = $this->addressParts['line' . $i];
        }
        return $payload->setLines(implode("\n", $street))
            ->setCity($this->addressParts['city'])
            ->setMainDivision($this->addressParts['region_code'])
            ->setCountryCode($this->addressParts['country_id'])
            ->setPostalCode($this->addressParts['postcode']);
    }

    /**
     * Test transferring address data to a physical address payload.
     */
    public function testTransferingAddressDataToPhysicalAddressPayload()
    {
        $payload = $this->generatePayloadObject(true);
        $address = $this->generateAddressObject(false);
        Mage::helper('ebayenterprise_address')->transferAddressToPhysicalAddressPayload($address, $payload);
        $this->assertSame(
            implode("\n", [$this->addressParts['line1'], $this->addressParts['line2'], $this->addressParts['line3'], $this->addressParts['line4']]),
            $payload->getLines()
        );
        $this->assertSame($this->addressParts['city'], $payload->getCity());
        $this->assertSame($this->addressParts['region_code'], $payload->getMainDivision());
        $this->assertSame($this->addressParts['country_id'], $payload->getCountryCode());
        $this->assertSame($this->addressParts['postcode'], $payload->getPostalCode());
    }

    /**
     * Test transferring physical address payload data onto a Magento address object.
     */
    public function testTransferingPhysicalAddressPayloadDataToAddress()
    {
        $payload = $this->generatePayloadObject(false);
        $address = $this->generateAddressObject(true);
        Mage::helper('ebayenterprise_address')->transferPhysicalAddressPayloadToAddress($payload, $address);
        $this->assertSame(
            implode("\n", [$this->addressParts['line1'], $this->addressParts['line2'], $this->addressParts['line3'], $this->addressParts['line4']]),
            $address->getStreetFull()
        );
        $this->assertSame($this->addressParts['city'], $address->getCity());
        $this->assertSame($this->addressParts['region_code'], $address->getRegionCode());
        $this->assertSame($this->addressParts['country_id'], $address->getCountry());
        $this->assertSame($this->addressParts['postcode'], $address->getPostcode());
    }
}

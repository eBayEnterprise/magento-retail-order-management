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

class EbayEnterprise_Tax_Test_Helper_Item_SelectionTest extends EcomDev_PHPUnit_Test_Case
{
    public function provideItems()
    {
        $parents = [];
        $items = [];
        foreach(range(0, 1) as $i) {
            $productType = ($i ?
                Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                Mage_Catalog_Model_Product_Type::TYPE_BUNDLE);
            $parent = $this->getModelMock('sales/quote_item_abstract', ['getProductType'], true);
            $parent
                ->method('getProductType')
                ->willReturn($productType);
            $parents[] = $parent;
            $item = $this->getModelMock('sales/quote_item_abstract', ['getParentItem'], true);
            $item
                ->method('getParentItem')
                ->willReturn(null);
            $items[] = $item;
        }
        foreach($parents as $parent) {
            $item = $this->getModelMock('sales/quote_item_abstract', ['getParentItem'], true);
            $item
                ->method('getParentItem')
                ->willReturn($parent);
            $items[] = $item;
        }
        return [[$items]];
    }

    /**
     * Test filtering child configurable items.
     *
     * @dataProvider provideItems
     */
    public function testSelectFrom(array $items)
    {
        $helper = Mage::helper('ebayenterprise_tax/item_selection');
        $selected = $helper->selectFrom($items);
        $this->assertCount(4, $items);
        $this->assertCount(2, $selected);
        $this->assertContainsOnlyInstancesOf('Mage_Sales_Model_Quote_Item_Abstract', $items);
        $this->assertContainsOnlyInstancesOf('Mage_Sales_Model_Quote_Item_Abstract', $selected);
    }
}

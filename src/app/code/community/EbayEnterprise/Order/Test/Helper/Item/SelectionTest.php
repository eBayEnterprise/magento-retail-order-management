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

class EbayEnterprise_Order_Test_Helper_Item_SelectionTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Order_Helper_Item_Selection */
	protected $_itemSelection;

	protected function setUp()
	{
		$this->_itemSelection = Mage::helper('ebayenterprise_order/item_selection');
	}

	/**
	 * Create a quote item stubbed to return the given product type, parent
	 * item and quote item.
	 *
	 * @param string
	 * @param Mage_Sales_Model_Order_Item|null
	 * @return Mage_Sales_Model_Order_Item
	 */
	protected function _mockItem($productType=null, Mage_Sales_Model_Order_Item $parentItem=null)
	{
		$item = $this->getModelMock(
			'sales/order_item',
			['getProductType', 'getParentItem']
		);
		$item->expects($this->any())
			->method('getProductType')
			->will($this->returnValue($productType));
		$item->expects($this->any())
			->method('getParentItem')
			->will($this->returnValue($parentItem));
		return $item;
	}

	/**
	 * When selecting form only simple items, no items should be filtered out.
	 */
	public function testSelectFromSimpleProducts()
	{
		$item = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
		$selectedItems = $this->_itemSelection->selectFrom([$item]);
		$this->assertCount(1, $selectedItems);
		$this->assertSame([$item], $selectedItems);
	}

	/**
	 * When selecting form a list with configurable items - parent config and
	 * simple child, only the parent item should be returned in the array of items.
	 */
	public function testSelectFromConfigurableProducts()
	{
		$parentItem = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
		$childItem = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, $parentItem);
		$selectedItems = $this->_itemSelection->selectFrom([$parentItem, $childItem]);
		$this->assertCount(1, $selectedItems);
		$this->assertEquals([$parentItem], $selectedItems);
	}

	/**
	 * When selecting form a list with bundle items - parent bundle and simple
	 * child - both parent and child items should be returned in the array of
	 * selected items.
	 */
	public function testSelectFromBundleProducts()
	{
		$parentItem = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_BUNDLE);
		$childItem = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, $parentItem);
		$selectedItems = $this->_itemSelection->selectFrom([$parentItem, $childItem]);
		$this->assertCount(2, $selectedItems);
		$this->assertEquals([$parentItem, $childItem], $selectedItems);
	}

	/**
	 * When filtering from a list with a grouped item, the grouped item item,
	 * which will simply be one of the items added by the group, should be included.
	 */
	public function testSelectFromGroupedProducts()
	{
		$groupedItem = $this->_mockItem(Mage_Catalog_Model_Product_Type::TYPE_GROUPED);
		$selectedItems = $this->_itemSelection->selectFrom([$groupedItem]);
		$this->assertCount(1, $selectedItems);
		$this->assertSame([$groupedItem], $selectedItems);
	}
}

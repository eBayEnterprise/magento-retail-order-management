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


class EbayEnterprise_Catalog_Test_Model_Pim_Product_CollectionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test formatting a sku, client id and catalog id into an item identifier
     * for an item in the collection.
     */
    public function testFormatId()
    {
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        $this->assertSame(
            '45-12345-clientId-catalogId',
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $collection,
                '_formatId',
                array('45-12345', 'clientId', 'catalogId')
            )
        );
    }
    /**
     * Create the item's identifier by using the sku, client id and catalog id
     * of the item to create an ID.
     */
    public function testGetItemId()
    {
        $item = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->setMethods(array('getSku', 'getClientId', 'getCatalogId'))
            ->getMock();
        $collection = $this->getModelMock('ebayenterprise_catalog/pim_product_collection', array('_formatId'));

        $item->expects($this->once())
            ->method('getSku')
            ->will($this->returnValue('45-12345'));
        $item->expects($this->once())
            ->method('getClientId')
            ->will($this->returnValue('clientId'));
        $item->expects($this->once())
            ->method('getCatalogId')
            ->will($this->returnValue('catalogId'));

        $collection->expects($this->once())
            ->method('_formatId')
            ->with($this->identicalTo('45-12345'), $this->identicalTo('clientId'), $this->identicalTo('catalogId'))
            ->will($this->returnValue('45-12345-clientId-catalogId'));

        $this->assertSame(
            '45-12345-clientId-catalogId',
            EcomDev_Utils_Reflection::invokeRestrictedMethod($collection, '_getItemId', array($item))
        );
    }

    /**
     * Test getting an item from the collection for a given product.
     */
    public function testGetItemForProduct()
    {
        $sku = '45-12345';
        $clientId = 'client_id';
        $catalogId = 'catalog_id';
        $id = "{$sku}-{$clientId}-{$catalogId}";

        $collection = $this->getModelMock(
            'ebayenterprise_catalog/pim_product_collection',
            array('getItemById', '_formatId')
        );

        $item = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getModelMock('catalog/product', array('getSku', 'getStore'));
        $store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->getMock();
        $config = $this->buildCoreConfigRegistry(array('clientId' => $clientId, 'catalogId' => $catalogId));
        $coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));

        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);

        $product->expects($this->once())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($store));
        $coreHelper->expects($this->once())
            ->method('getConfigModel')
            ->with($this->identicalTo($store))
            ->will($this->returnValue($config));
        $collection->expects($this->once())
            ->method('_formatId')
            ->with($this->identicalTo($sku), $this->identicalTo($clientId), $this->identicalTo($catalogId))
            ->will($this->returnValue($id));
        $collection->expects($this->once())
            ->method('getItemById')
            ->with($this->identicalTo($id))
            ->will($this->returnValue($item));

        $this->assertSame($item, $collection->getItemForProduct($product));
    }
    /**
     * Will return the first item if there are items in the collection.
     */
    public function testGetFirstItem()
    {
        $pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->getMock();
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', array($pimProduct));
        $this->assertSame($pimProduct, $collection->setPageSize(1)->getFirstItem());
    }
    /**
     * When there are no items in the collection, should thrown an exception.
     */
    public function testGetFirstItemEmptyCollection()
    {
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        $this->setExpectedException('EbayEnterprise_Catalog_Model_Pim_Product_Collection_Exception', 'EbayEnterprise_Catalog_Model_Pim_Product_Collection::getFirstItem cannot get item from an empty collection');
        $collection->setPageSize(1)->getFirstItem();
    }
    /**
     * Will return the last item if there are items in the collection.
     */
    public function testGetLastItem()
    {
        $pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->getMock();
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', array($pimProduct));
        $this->assertSame($pimProduct, $collection->getLastItem());
    }
    /**
     * When there are no items, should throw an exception.
     */
    public function testGetLastItemEmptyCollection()
    {
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        $this->setExpectedException('EbayEnterprise_Catalog_Model_Pim_Product_Collection_Exception', 'EbayEnterprise_Catalog_Model_Pim_Product_Collection::getLastItem cannot get item from an empty collection');
        $collection->getLastItem();
    }
    /**
     * Cannot be implemented for the PIM Product model so will always raise a
     * NotImplemented error (or something like that)
     */
    public function testGetNewEmptyItem()
    {
        $this->setExpectedException('EbayEnterprise_Eb2cCore_Exception_NotImplemented', 'EbayEnterprise_Catalog_Model_Pim_Product_Collection::getNewEmptyItem is not implemented');
        $collection = Mage::getModel('ebayenterprise_catalog/pim_product_collection');
        $collection->getNewEmptyItem();
    }
}

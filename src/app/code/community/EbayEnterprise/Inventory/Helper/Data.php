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

class EbayEnterprise_Inventory_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $coreConfig;
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $detailFactory;

    public function __construct(array $initParams = [])
    {
        list($this->coreConfig, $this->detailFactory) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'core_config', Mage::helper('eb2ccore')->getConfigModel()),
            $this->nullCoalesce($initParams, 'detail_factory', Mage::helper('ebayenterprise_inventory/details_factory'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Inventory_Helper_Details_Factory
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Model_Config_Registry $coreConfig,
        EbayEnterprise_Inventory_Helper_Details_Factory $detailFactory
    )
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * @param mixed
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_inventory/config'));
    }

    /**
     * Returns a Varien_Object containing the from and to date for a backorderable product that
     * doesn't make inventory detail call to ROM. If the ROM setting is configured to use
     * street date as estimated delivery date and the backorderable item has a valid date
     * that's in the future, then the from and to date will be constructed using the street date
     * in product and to date using the street date plus the configured number of days in the future.
     * If any of these conditions are not met, then this method will simply return null.
     *
     * @param Mage_Core_Model_Abstract
     * @return Varien_Object | null
     */
    public function getStreetDateForBackorderableItem(Mage_Core_Model_Abstract $item)
    {
        if ($this->coreConfig->isUseStreetDateAsEddDate) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection */
            $products = $this->getAllProductsFromItem($item);
            /** @var string $streetDate */
            $streetDate = $this->getStreetDateFromProduct($products);
            if ($streetDate && $this->isStreetDateInTheFuture($streetDate)) {
                return $this->getNewVarienObject([
                    'delivery_window_from_date' => $this->getNewDateTime($streetDate),
                    'delivery_window_to_date' => $this->getStreetToDate($streetDate),
                ]);
            }
        }
        return null;
    }

    /**
     * Determine if the passed in product is a backorderable product.
     *
     * @param  Mage_Catalog_Model_Product
     * @return bool
     */
    protected function isBackorderable(Mage_Catalog_Model_Product $product)
    {
        $stockItem = $product->getStockItem();
        return ((int) $stockItem->getBackorders() > Mage_CatalogInventory_Model_Stock::BACKORDERS_NO);
    }

    /**
     * Determine if the passed in street date string is in the future.
     *
     * @param  string
     * @return bool
     */
    protected function isStreetDateInTheFuture($streetDate)
    {
        $now = $this->getNewDateTime('now');
        $future = $this->getNewDateTime($streetDate);
        return $now <= $future;
    }

    /**
     * Using configuration setting to determine the to date for street date.
     *
     * @param  string
     * @return DateTime
     */
    protected function getStreetToDate($streetDate)
    {
        $toDate = $this->getNewDateTime($streetDate);
        $intervalSpec = "P{$this->coreConfig->toStreetDateRange}D";
        return $toDate->add($this->getNewDateInterval($intervalSpec));
    }

    /**
     * Returns a new DateTime instance.
     *
     * @param  string
     * @return DateTime
     */
    protected function getNewDateTime($date)
    {
        return new DateTime($date);
    }

    /**
     * Returns a new Varien_Object instance.
     *
     * @param  string
     * @return Varien_Object
     */
    protected function getNewVarienObject(array $data = [])
    {
        return new Varien_Object($data);
    }

    /**
     * Returns a new DateInterval instance.
     *
     * @param  string
     * @return DateInterval
     */
    protected function getNewDateInterval($intervalSpec)
    {
        return new DateInterval($intervalSpec);
    }

    /**
     * Return the first street date found in the passed in collection of products.
     *
     * @param  Mage_Catalog_Model_Resource_Product_Collection
     * @return string | null
     */
    protected function getStreetDateFromProduct(Mage_Catalog_Model_Resource_Product_Collection $products)
    {
        foreach ($products as $product) {
            /** @var string | null */
            $streetDate = $product->getStreetDate();
            if ($streetDate) {
                return $streetDate;
            }
        }
        return null;
    }

    /**
     * Get estimated delivery data if the item is backorderable.
     *
     * @param  Mage_Core_Model_Abstract
     * @return EbayEnterprise_Inventory_Model_Details_Item | null
     */
    public function getOcrBackorderableEddData(Mage_Core_Model_Abstract $item)
    {
        $streetData = $this->getStreetDateForBackorderableItem($item);
        if ($streetData) {
            return $this->detailFactory->createItemDetails([
                'item_id' => $item->getId(),
                'sku' => $item->getSku(),
                'delivery_window_from_date' => $streetData->getDeliveryWindowFromDate(),
                'delivery_window_to_date' => $streetData->getDeliveryWindowToDate(),
                'shipping_window_from_date' => null,
                'shipping_window_to_date' => null,
                'delivery_estimate_creation_time' => null,
                'delivery_estimate_display_flag' => null,
                'delivery_estimate_message' => null,
                'ship_from_lines' => null,
                'ship_from_city' => null,
                'ship_from_main_division' => null,
                'ship_from_country_code' => null,
                'ship_from_postal_code' => null,
            ]);
        }
        return null;
    }

    /**
     * Ensure that the SKU actually the right ROM SKU, especially for bundle products
     * that tend to concatenate the parent SKU with the child SKUs.
     *
     * @param  string
     * @return string
     */
    public function getRomSku($sku)
    {
        $prefix = $this->coreConfig->catalogId . '-';
        $splitSkus = array_filter(explode($prefix, $sku));
        return count($splitSkus) > 1 ? $prefix . substr(reset($splitSkus), 0, -1) : $sku;
    }

    /**
     * Get all child products and parent product from the quote item.
     *
     * @param  Mage_Core_Model_Abstract
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getAllProductsFromItem(Mage_Core_Model_Abstract $item)
    {
        /** @var array */
        $skus = $this->getAllItemSkus($item);
        return Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect(['street_date'])
            ->addAttributeToFilter([['attribute' => 'sku', 'in' => $skus]])
            ->load();
    }

    /**
     * Get all child product SKUs and parent product SKUs from the quote item.
     *
     * @param  Mage_Core_Model_Abstract
     * @return array
     */
    protected function getAllItemSkus(Mage_Core_Model_Abstract $item)
    {
        return array_merge(
            $this->getAllChildSkusFromItem($item),
            $this->getAllParentSkuFromItem($item)
        );
    }

    /**
     * Get all child product SKUs from the quote item.
     *
     * @param  Mage_Core_Model_Abstract
     * @return array
     */
    protected function getAllChildSkusFromItem(Mage_Core_Model_Abstract $item)
    {
        /** @var array */
        $skus = [];
        $children = $item->getChildren();
        if ($children) {
            foreach ($children as $childItem) {
                $skus[] = $childItem->getSku();
            }
        }
        return $skus;
    }

    /**
     * Get current and parent product SKU from the quote item.
     *
     * @param  Mage_Core_Model_Abstract
     * @return array
     */
    protected function getAllParentSkuFromItem(Mage_Core_Model_Abstract $item)
    {
        $skus = [$item->getSku()];
        /** @var Mage_Sales_Model_Quote_Item */
        $parentItem = $item->getParentItem();
        if ($parentItem) {
            if ($item->getProductId() !== $parentItem->getProductId()) {
                $skus[] = $parentItem->getSku();
            }
        }
        return $skus;
    }
}

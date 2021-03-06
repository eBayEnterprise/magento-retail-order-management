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
 * Pim Product model
 *
 * @method string getCatalogId()
 * @method EbayEnterprise_Catalog_Model_Pim_Product setCatalogId(string)
 * @method string getClientId()
 * @method EbayEnterprise_Catalog_Model_Pim_Product setClientId(string)
 * @method string getSku()
 * @method EbayEnterprise_Catalog_Model_Pim_Product setSku(string)
 * @method array getPimAttributes()
 * @method EbayEnterprise_Catalog_Model_Pim_Product setPimAttributes(array)
 *
 * catalog_id     string the external catalog id
 * client_id      string the external client id
 * sku            string product sku
 * pim_attributes array  list of PIM attributes for this product
 */
class EbayEnterprise_Catalog_Model_Pim_Product extends Varien_Object
{
    const ERROR_INVALID_ARGS = '%s missing arguments: %s';
    /**
     * Validate initialization data
     * Trigger an error if catalog_id, client_id, sku are not in the
     * initilization data.
     */
    protected function _construct()
    {
        $missingData = array_diff(array('client_id', 'catalog_id', 'sku'), array_keys($this->getData()));
        if ($missingData) {
            Mage::helper('eb2ccore')->triggerError(sprintf(
                self::ERROR_INVALID_ARGS,
                __METHOD__,
                implode(', ', $missingData)
            ));
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd
        $this->setPimAttributes(array());
    }
    /**
     * Generate Pim Attribute models for the product.
     * For every attribute the product has, create a new PIM Attribute model using
     * the PIM Attribute Factory singleton, then filter out any `null` values
     * returned from the factory and merge the new attribute models with the
     * existing set of PIM attribute models.
     * @param  Mage_Catalog_Model_Product $product
     * @param  EbayEnterprise_Dom_Document    $doc
     * @param  array $config
     * @param  array $attributes list of attributes from the configuration per feed type
     * @return self
     */
    public function loadPimAttributesByProduct(
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc,
        array $config,
        array $attributes
    ) {
        $attributeFactory = Mage::getSingleton('ebayenterprise_catalog/pim_attribute_factory');

        return $this->setPimAttributes(
            array_merge(
                $this->getPimAttributes(),
                array_filter(
                    array_map(
                        function ($attr) use ($product, $attributeFactory, $doc, $config) {
                            return $attributeFactory->getPimAttribute($attr, $product, $doc, $config);
                        },
                        $attributes
                    )
                )
            )
        );
    }
}

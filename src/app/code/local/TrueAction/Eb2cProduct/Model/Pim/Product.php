<?php
/**
 * Pim Product model
 *
 * @method string getCatalogId()
 * @method self   setCatalogId(string)
 * @method string getClientId()
 * @method self   setClientId(string)
 * @method string getSku()
 * @method self   setSku(string)
 * @method array  getPimAttributes()
 * @method self   setPimAttributes(array)
 *
 * catalog_id     string the external catalog id
 * client_id      string the external client id
 * sku            string product sku
 * pim_attributes array  list of PIM attributes for this product
 */
class TrueAction_Eb2cProduct_Model_Pim_Product
	extends Varien_Object
{
	/**
	 * Validate initialization data
	 * Trigger an error if catalog_id, client_id, sku are not in the
	 * initilization data.
	 */
	protected function _construct()
	{
		if ($missingData = array_diff(array('client_id', 'catalog_id', 'sku'), array_keys($this->getData()))) {
			trigger_error(
				sprintf('%s missing arguments: %s', __METHOD__, implode(', ', $missingData)),
				E_USER_ERROR
			);
		}
		$this->setPimAttributes(array());
	}
	/**
	 * Generate Pim Attribute models for the product.
	 * For every attribute the product has, create a new PIM Attribute model using
	 * the PIM Attribute Factory singleton, then filter out any `null` values
	 * returned from the factory and merge the new attribute models with the
	 * existing set of PIM attribute models.
	 * @param  Mage_Catalog_Model_Product $product
	 * @param  TrueAction_Dom_Document    $doc
	 * @return self
	 */
	public function loadPimAttributesByProduct(Mage_Catalog_Model_Product $product, TrueAction_Dom_Document $doc)
	{
		$attributeFactory = Mage::getSingleton('eb2cproduct/pim_attribute_factory');

		return $this->setPimAttributes(
			array_merge(
				$this->getPimAttributes(),
				array_filter(
					array_map(
						function ($attr) use ($product, $attributeFactory, $doc) {
							return $attributeFactory->getPimAttribute($attr, $product, $doc);
						},
						array_values($product->getAttributes())
					)
				)
			)
		);
	}
}

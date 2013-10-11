<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Extractor
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	/**
	 * extract ExtendedAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		$colorData = array();
		$nodeColorAttributes = $xpath->query('ExtendedAttributes/ColorAttributes/Color', $item);
		$colorIndex = 1;
		foreach ($nodeColorAttributes as $colorRecord) {
			// Description of the color used for specific store views/languages.
			$nodeColorDescription = $xpath->query('Description', $colorRecord);
			$colorDescriptionData = array();
			$colorDescriptionIndex = 1;
			foreach ($nodeColorDescription as $colorDescriptionRecord) {
				$colorDescriptionData[] = array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($colorDescriptionRecord->getAttribute('xml:lang')),
					'description' => $colorDescriptionRecord->nodeValue
				);
				$colorDescriptionIndex++;
			}

			$colorData[] = array(
				// Color value/name with a locale specific description.
				// Name of the color used as the default and in the admin.
				'code' => (string) $prdHlpr->extractNodeVal($xpath->query('Code/text()', $colorRecord)),
				'description' => $colorDescriptionData,
			);
			$colorIndex++;
		}

		// Selling name and description used to identify a product for advertising purposes
		// Short description of the selling/promotional name.
		$brandDescriptionData = array();
		$nodeBrandDescription = $xpath->query('ExtendedAttributes/Brand/Description', $item);
		foreach ($nodeBrandDescription as $brandDescriptionRecord) {
			$brandDescriptionData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($brandDescriptionRecord->getAttribute('xml:lang')),
				'description' => $brandDescriptionRecord->nodeValue
			);
		}

		$sizeData = array();
		$nodeSizeAttributes = $xpath->query('ExtendedAttributes/SizeAttributes/Size', $item);
		foreach ($nodeSizeAttributes as $sizeRecord) {
			$sizeData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($sizeRecord->getAttribute('xml:lang')), // Language code for the natural language of the size data.
				// Size code.
				'code' => (string) $prdHlpr->extractNodeVal($xpath->query('Code/text()', $sizeRecord)),
				// Size Description.
				'description' => (string) $prdHlpr->extractNodeVal($xpath->query('Description/text()', $sizeRecord)),
			);
		}
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$attributeData = array();
		$prdHlpr = Mage::helper('eb2cproduct');

		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			$attributeData[] = array(
				// The name of the attribute.
				'name' => (string) $attributeRecord->getAttribute('name'),
				// Type of operation to take with this attribute. enum: ('Add', 'Change', 'Delete')
				'operationType' => (string) $attributeRecord->getAttribute('operation_type'),
				// Language code for the natural language or the <Value /> element.
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeRecord->getAttribute('xml:lang')),
				'value' => (string) $prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord)),
			);
		}

		return new Varien_Object(
			array(
				'attributes' => $attributeData
			)
		);
	}

	/**
	 * extract productType from CustomAttributes data
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return string, the productType
	 */
	protected function _extractProductType(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'PRODUCTTYPE') {
				return strtolower(trim($prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord))));
			}
		}

		return '';
	}

	/**
	 * extract ConfigurableAttributes from CustomAttributes data
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return array, the configurable attribute sets
	 */
	protected function _extractConfigurableAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'CONFIGURABLEATTRIBUTES') {
				return explode(',', (string) $prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord)));
			}
		}

		return array();
	}

	/**
	 * extract feed data into a collection of varien objects
	 * @param DOMXPath $xpath, the DOMXPath with the loaded feed data
	 * @return array, an collection of varien objects
	 */
	public function extract(DOMXPath $xpath)
	{
		$collectionOfItems = array();
		$baseNode = self::FEED_BASE_NODE;

		$master = $xpath->query("//$baseNode");
		$idx = 1; // xpath uses 1-based indexing
		Mage::log(sprintf('[ %s ] Found %d items to extract', __CLASS__, $master->length), Zend_Log::DEBUG);
		foreach ($master as $item) {
			$catalogId = (string) $item->getAttribute('catalog_id');

			// setting item object into the collection of item objects.
			$collectionOfItems[] = new Varien_Object(
				array(
					// setting catalog id
					'catalog_id' => $catalogId,
					// setting gsi_client_id id
					'gsi_client_id' => (string) $item->getAttribute('gsi_client_id'),
					// Defines the action requested for this item. enum:("Add", "Change", "Delete")
					'operation_type' => (string) $item->getAttribute('operation_type'),
					// get varien object of item id node
					'item_id' => $this->_extractItemId($xpath, $item),
					// get varien object of base attributes node
					'base_attributes' => $this->_extractBaseAttributes($xpath, $item),
					// get varien object of Drop Ship Supplier Information attribute node
					'drop_ship_supplier_information' => $this->_extractDropShipSupplierInformation($xpath, $item),
					// get varien object of Extended Attributes node
					'extended_attributes' => $this->_extractExtendedAttributes($xpath, $item),
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($xpath, $item),
					// get product type from Custom Attributes node
					'product_type' => $this->_extractProductType($xpath, $item),
					// get configurable attributes from Custom Attributes node
					'configurable_attributes' => $this->_extractConfigurableAttributes($xpath, $item),
				)
			);

			// increment item index
			Mage::log(sprintf('[ %s ] Extracted %d of %d items', __CLASS__, $idx, $master->length), Zend_Log::DEBUG);
			$idx++;
		}
		return $collectionOfItems;
	}
}

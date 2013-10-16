<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_I_Extractor
	implements TrueAction_Eb2cProduct_Model_Feed_IExtractor
{
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
	 * extract HTSCodes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return string, json encoded content string
	 */
	protected function _extractHtsCodes(DOMXPath $xpath, DOMElement $item)
	{
		$htsCodesData = array();

		// Name value paris of additional htsCodess for the product.
		$nodeHtsCode = $xpath->query('HTSCodes/HTSCode', $item);
		foreach ($nodeHtsCode as $htsCodeRecord) {
			$htsCodesData[] = array(
				// The mfn_duty_rate attributes.
				'mfn_duty_rate' => (string) $htsCodeRecord->getAttribute('mfn_duty_rate'),
				// The destination_country attributes
				'destination_country' => (string) $htsCodeRecord->getAttribute('operation_type'),
				// The restricted attributes
				'restricted' => (bool) $htsCodeRecord->getAttribute('restricted'),
				// The HTSCode node value
				'HTSCode' => (string) $htsCodeRecord->nodeValue,
			);
		}

		return json_encode($htsCodesData);
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMXPath $xpath, the DOMXPath with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extract(DOMXPath $xpath)
	{
		$collectionOfItems = array();
		$baseNode = self::FEED_BASE_NODE;

		$master = $xpath->query("//$baseNode");
		$idx = 1; // start index
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
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($xpath, $item),
					// get product type from Custom Attributes node
					'product_type' => $this->_extractProductType($xpath, $item),
					// get varien object of HTSCode node
					'hts_codes' => $this->_extractHtsCodes($xpath, $item),
				)
			);

			// increment item index
			Mage::log(sprintf('[ %s ] Extracted %d of %d items', __CLASS__, $idx, $master->length), Zend_Log::DEBUG);
			$idx++;
		}

		return $collectionOfItems;
	}
}

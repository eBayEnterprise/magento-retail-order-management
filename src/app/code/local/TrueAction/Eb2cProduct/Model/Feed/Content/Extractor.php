<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Content_Extractor
	implements TrueAction_Eb2cProduct_Model_Feed_IExtractor
{
	const FEED_BASE_NODE = 'Content';

	/**
	 * extract UniqueId data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractUniqueId(DOMXPath $xpath, DOMElement $content)
	{
		// Unique identifier for the item, SKU.
		return Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('UniqueID/text()', $content));
	}

	/**
	 * extract StyleID data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractStyleID(DOMXPath $xpath, DOMElement $content)
	{
		// The parent SKU, associated with this child item
		// should be the same as UniqueID if this item doesn't have a parent product.
		return Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('StyleID/text()', $content));
	}

	/**
	 * extract ProductLinks data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractProductLinks(DOMXPath $xpath, DOMElement $content)
	{
		$productLinks = array();

		// Child Content of this Content
		$nodeProductLink = $xpath->query('ProductLinks/ProductLink', $content);
		if ($nodeProductLink->length) {
			$productContentIndex = 1;
			foreach ($nodeProductLink as $productContent) {
				$productLinks[] = new Varien_Object(
					array(
						'link_type' => (string) $productContent->getAttribute('link_type'), // Type of link relationship.
						'operation_type' => (string) $productContent->getAttribute('operation_type'), // Operation to take with the product link. ("Add", "Delete")
						'link_to_unique_id' => Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('LinkToUniqueId/text()', $productContent)), // Unique ID (SKU) for the linked product.
					)
				);
				$productContentIndex++;
			}
		}

		return $productLinks;
	}

	/**
	 * extract CategoryLinks data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractCategoryLinks(DOMXPath $xpath, DOMElement $content)
	{
		$categoryLinks = array();

		// Child Content of this Content
		$nodeCategoryLink = $xpath->query('CategoryLinks/CategoryLink', $content);
		if ($nodeCategoryLink->length) {
			$categoryContentIndex = 1;
			foreach ($nodeCategoryLink as $categoryContent) {
				$categoryLinks[] = new Varien_Object(
					array(
						'default' => (bool) $categoryContent->getAttribute('default'), // if category is the default
						'catalog_id' => (string) $categoryContent->getAttribute('catalog_id'), // Used to link products across catalogs.
						'import_mode' => (string) $categoryContent->getAttribute('import_mode'), // Operation to take with the category.
						'name' => Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('Name/text()', $categoryContent)), // Unique ID (SKU) for the linked product.
					)
				);
				$categoryContentIndex++;
			}
		}

		return $categoryLinks;
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractBaseAttributes(DOMXPath $xpath, DOMElement $content)
	{
		$baseAttributes = array();

		// Child Content of this Content
		$nodeTitle = $xpath->query('BaseAttributes/Title', $content);
		if ($nodeTitle->length) {
			foreach ($nodeTitle as $titleContent) {
				$baseAttributes[] = new Varien_Object(
					array(
						// Targeted store language
						'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($titleContent->getAttribute('xml:lang')),
						// Localized product title
						'title' => (string) $titleContent->nodeValue,
					)
				);
			}
		}

		return $baseAttributes;
	}

	/**
	 * extract ExtendedAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $xpath, DOMElement $content)
	{
		$extendedAttributes = array();

		// extract Gift Wrap attributes
		$extendedAttributes['gift_wrap'] = new Varien_Object(
			array(
				// Can this item be gift wrapped? ("Y", "N")
				'gift_wrap' => Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('ExtendedAttributes/GiftWrap/text()', $content)),
			)
		);

		// extract color attributes
		$nodeDescription = $xpath->query('ExtendedAttributes/ColorAttributes/Color/Description', $content);

		$colorDescriptionCollection = array();
		foreach ($nodeDescription as $attributeColorContent) {
			$colorDescriptionCollection = new Varien_Object(
				array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeColorContent->getAttribute('xml:lang')), // Targeted store language
					'description' => (string) $attributeColorContent->nodeValue, // Localized descriptive name of the color.
				)
			);
		}

		$extendedAttributes['color_attributes'] = new Varien_Object(
			array(
				'code' => Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('ExtendedAttributes/ColorAttributes/Color/Code/text()', $content)), // Code used to identify the color
				'description' => $colorDescriptionCollection, // collection of description per language.
				'sequence' =>  Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('ExtendedAttributes/ColorAttributes/Color/Sequence/text()', $content)), // Color order/sequence.
			)
		);

		// extract long description attributes
		$nodeLongDescription = $xpath->query('ExtendedAttributes/LongDescription', $content);
		foreach ($nodeLongDescription as $attributeLongContent) {
			$extendedAttributes['long_description'][] = new Varien_Object(
				array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeLongContent->getAttribute('xml:lang')), // Targeted store language
					'long_description' => (string) $attributeLongContent->nodeValue, // Long description of the item.
				)
			);
		}

		// extract short description attributes
		$nodeShortDescription = $xpath->query('ExtendedAttributes/ShortDescription', $content);
		foreach ($nodeShortDescription as $attributeShortContent) {
			$extendedAttributes['short_description'][] = new Varien_Object(
				array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeShortContent->getAttribute('xml:lang')), // Targeted store language
					'short_description' => (string) $attributeShortContent->nodeValue, // short description of the item.
				)
			);
		}

		// extract short SearchKeywords attributes
		$nodeSearchKeywords = $xpath->query('ExtendedAttributes/SearchKeywords', $content);
		foreach ($nodeSearchKeywords as $attributeSearchContent) {
			$extendedAttributes['search_keywords'][] = new Varien_Object(
				array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeSearchContent->getAttribute('xml:lang')), // Targeted store language
					'search_keywords' => (string) $attributeSearchContent->nodeValue, // search keywords of the item.
				)
			);
		}

		return $extendedAttributes;
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $content, the current element
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $xpath, DOMElement $content)
	{
		$customAttributes = array();

		// attribute list
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $content);
		$attributeContentIndex = 1;
		foreach ($nodeAttribute as $customContent) {
			$customAttributes[] = new Varien_Object(
				array(
					'name' => (string) $customContent->getAttribute('name'), // Custom attribute name.
					'operation_type' => (string) $customContent->getAttribute('operation_type'), // Operation to take with the attribute. ("Add", "Change", "Delete")
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($customContent->getAttribute('xml:lang')), // Operation to take with the product link. ("Add", "Delete")
					'value' => Mage::helper('eb2cproduct')->extractNodeToString($xpath->query('Value/text()', $customContent)), // Unique ID (SKU) for the linked product.
				)
			);
			$attributeContentIndex++;
		}

		return $customAttributes;
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
		$collectionOfContents = array();
		$baseNode = self::FEED_BASE_NODE;

		$nodeContent = $xpath->query("//$baseNode");
		$idx = 1; // start index
		Mage::log(sprintf('[ %s ] Found %d items to extract', __CLASS__, $nodeContent->length), Zend_Log::DEBUG);
		foreach ($nodeContent as $content) {
			// setting catalog id
			$catalogId = (string) $content->getAttribute('catalog_id');

			// setting Content object into the collection of Content objects.
			$collectionOfContents[] = new Varien_Object(
				array(
					// Catalog ID of the client or shared catalog.
					'catalog_id' => $catalogId,
					// Client ID assigned by GSI
					'gsi_client_id' => (string) $content->getAttribute('gsi_client_id'),
					// Client store/channel.
					'gsi_store_id' => (string) $content->getAttribute('gsi_store_id'),
					// Unique identifier for the item, SKU.
					'unique_id' => $this->_extractUniqueId($xpath, $content),
					// the parent sku related to the this item
					'style_id' => $this->_extractStyleID($xpath, $content),
					// List of related products.
					'product_links' => $this->_extractProductLinks($xpath, $content),
					// Link the product into categories.
					'category_links' => $this->_extractCategoryLinks($xpath, $content),
					// base product attributes (name/title)
					'base_attributes' => $this->_extractBaseAttributes($xpath, $content),
					// Attributes known to eb2c.
					'extended_attributes' => $this->_extractExtendedAttributes($xpath, $content),
					// additional attributes
					'custom_attributes' => $this->_extractCustomAttributes($xpath, $content),
				)
			);

			// increment Content index
			Mage::log(sprintf('[ %s ] Extracted %d of %d items', __CLASS__, $idx, $nodeContent->length), Zend_Log::DEBUG);
			$idx++;
		}

		return $collectionOfContents;
	}
}

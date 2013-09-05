<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Content_Extractor extends Mage_Core_Model_Abstract
{
	/**
	 * extract UniqueId data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractUniqueId(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		// Unique identifier for the item, SKU.
		$nodeUniqueID = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/UniqueID");
		return ($nodeUniqueID->length)? (string) $nodeUniqueID->item(0)->nodeValue : null;
	}

	/**
	 * extract StyleID data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractStyleID(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		// The parent SKU, associated with this child item
		// should be the same as UniqueID if this item doesn't have a parent product.
		$nodeStyleID = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/StyleID");

		return ($nodeStyleID->length)? (string) $nodeStyleID->item(0)->nodeValue : null;
	}

	/**
	 * extract ProductLinks data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractProductLinks(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		$productLinks = array();

		// Contents included if this Content is a link product.
		$nodeProductLinks = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ProductLinks");
		if ($nodeProductLinks->length) {
			// Child Content of this Content
			$nodeProductLink = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ProductLinks/ProductLink");
			if ($nodeProductLink->length) {
				$productContentIndex = 1;
				foreach ($nodeProductLink as $productContent) {
					$linkToUniqueId = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ProductLinks/ProductLink[$productContentIndex]/LinkToUniqueId");
					$productLinks[] = new Varien_Object(
						array(
							'link_type' => (string) $productContent->getAttribute('link_type'), // Type of link relationship.
							'operation_type' => (string) $productContent->getAttribute('operation_type'), // Operation to take with the product link. ("Add", "Delete")
							'link_to_unique_id' => ($linkToUniqueId->length)? (string) $linkToUniqueId->item(0)->nodeValue : null, // Unique ID (SKU) for the linked product.
						)
					);
					$productContentIndex++;
				}
			}
		}

		return $productLinks;
	}

	/**
	 * extract CategoryLinks data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractCategoryLinks(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		$categoryLinks = array();

		// Link the product into categories.
		$nodeCategoryLinks = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CategoryLinks");
		if ($nodeCategoryLinks->length) {
			// Child Content of this Content
			$nodeCategoryLink = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CategoryLinks/CategoryLink");
			if ($nodeCategoryLink->length) {
				$categoryContentIndex = 1;
				foreach ($nodeCategoryLink as $categoryContent) {
					$categoryName = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CategoryLinks/CategoryLink[$categoryContentIndex]/Name");
					$categoryLinks[] = new Varien_Object(
						array(
							'default' => (bool) $categoryContent->getAttribute('default'), // if category is the default
							'catalog_id' => (string) $categoryContent->getAttribute('catalog_id'), // Used to link products across catalogs.
							'import_mode' => (string) $categoryContent->getAttribute('import_mode'), // Operation to take with the category.
							'name' => ($categoryName->length)? (string) $categoryName->item(0)->nodeValue : null, // Unique ID (SKU) for the linked product.
						)
					);
					$categoryContentIndex++;
				}
			}
		}

		return $categoryLinks;
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractBaseAttributes(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		$baseAttributes = array();

		// Link the product into categories.
		$nodeBaseAttributes = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/BaseAttributes");
		if ($nodeBaseAttributes->length) {
			// Child Content of this Content
			$nodeTitle = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/BaseAttributes/Title");
			if ($nodeTitle->length) {
				foreach ($nodeTitle as $titleContent) {
					$baseAttributes[] = new Varien_Object(
						array(
							'lang' => (string) $titleContent->getAttribute('xml:lang'), // Targeted store language
							'title' => (string) $titleContent->nodeValue, // Localized product title
						)
					);
				}
			}
		}

		return $baseAttributes;
	}

	/**
	 * extract ExtendedAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		$extendedAttributes = array();

		// Link the product into categories.
		$nodeExtendedAttributes = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes");
		if ($nodeExtendedAttributes->length) {
			// extract Gift Wrap attributes
			$nodeGiftWrap = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/GiftWrap");
			$extendedAttributes['gift_wrap'] = new Varien_Object(
				array(
					'gift_wrap' => ($nodeGiftWrap->length)? (string) $nodeGiftWrap->item(0)->nodeValue : null, // Can this item be gift wrapped? ("Y", "N")
				)
			);

			// extract color attributes
			$nodeColor = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color");
			if ($nodeColor->length) {
				$nodeCode = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color/Code");
				$nodeDescription = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color/Description");
				$nodeSequence = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color/Sequence");

				$colorDescriptionCollection = array();
				if ($nodeDescription->length) {
					foreach ($nodeDescription as $attributeColorContent) {
						$colorDescriptionCollection = new Varien_Object(
							array(
								'lang' => (string) $attributeColorContent->getAttribute('xml:lang'), // Targeted store language
								'description' => (string) $attributeColorContent->nodeValue, // Localized descriptive name of the color.
							)
						);
					}
				}
				$extendedAttributes['color_attributes'] = new Varien_Object(
					array(
						'code' => ($nodeCode->length)? (string) $nodeCode->item(0)->nodeValue : null, // Code used to identify the color
						'description' => $colorDescriptionCollection, // collection of description per language.
						'sequence' => ($nodeSequence->length)? (string) $nodeSequence->item(0)->nodeValue : null, // Color order/sequence.
					)
				);
			}

			// extract long description attributes
			$nodeLongDescription = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/LongDescription");
			if ($nodeLongDescription->length) {
				foreach ($nodeLongDescription as $attributeLongContent) {
					$extendedAttributes['long_description'][] = new Varien_Object(
						array(
							'lang' => (string) $attributeLongContent->getAttribute('xml:lang'), // Targeted store language
							'long_description' => (string) $attributeLongContent->nodeValue, // Long description of the item.
						)
					);
				}
			}

			// extract short description attributes
			$nodeShortDescription = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/ShortDescription");
			if ($nodeShortDescription->length) {
				foreach ($nodeShortDescription as $attributeShortContent) {
					$extendedAttributes['short_description'][] = new Varien_Object(
						array(
							'lang' => (string) $attributeShortContent->getAttribute('xml:lang'), // Targeted store language
							'short_description' => (string) $attributeShortContent->nodeValue, // short description of the item.
						)
					);
				}
			}

			// extract short SearchKeywords attributes
			$nodeSearchKeywords = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/ExtendedAttributes/SearchKeywords");
			if ($nodeSearchKeywords->length) {
				foreach ($nodeSearchKeywords as $attributeSearchContent) {
					$extendedAttributes['search_keywords'][] = new Varien_Object(
						array(
							'lang' => (string) $attributeSearchContent->getAttribute('xml:lang'), // Targeted store language
							'search_keywords' => (string) $attributeSearchContent->nodeValue, // search keywords of the item.
						)
					);
				}
			}
		}

		return $extendedAttributes;
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $contentIndex, the current content position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return array, a collection of Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $feedXPath, $contentIndex, $catalogId)
	{
		$customAttributes = array();

		// List of additional attributes that may be used by the client system/Magento.
		$nodeCustomAttributes = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CustomAttributes");
		if ($nodeCustomAttributes->length) {
			// attribute list
			$nodeAttribute = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CustomAttributes/Attribute");
			if ($nodeAttribute->length) {
				$attributeContentIndex = 1;
				foreach ($nodeAttribute as $customContent) {
					$nodeValue = $feedXPath->query("//Content[$contentIndex][@catalog_id='$catalogId']/CustomAttributes/Attribute[$attributeContentIndex]/Value");
					$customAttributes[] = new Varien_Object(
						array(
							'name' => (string) $customContent->getAttribute('name'), // Custom attribute name.
							'operation_type' => (string) $customContent->getAttribute('operation_type'), // Operation to take with the attribute. ("Add", "Change", "Delete")
							'lang' => (string) $customContent->getAttribute('xml:lang'), // Operation to take with the product link. ("Add", "Delete")
							'value' => ($nodeValue->length)? (string) $nodeValue->item(0)->nodeValue : null, // Unique ID (SKU) for the linked product.
						)
					);
					$attributeContentIndex++;
				}
			}
		}

		return $customAttributes;
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extractContentMasterFeed(DOMDocument $doc)
	{
		$collectionOfContents = array();
		$feedXPath = new DOMXPath($doc);

		$nodeContent = $feedXPath->query('//Content');
		$contentIndex = 1; // start index
		foreach ($nodeContent as $content) {
			// setting catalog id
			$catalogId = (string) $content->getAttribute('catalog_id');

			// setting Content object into the collection of Content objects.
			$collectionOfContents[] = new Varien_Object(
				array(
					'catalog_id' => $catalogId, // Catalog ID of the client or shared catalog.
					'gsi_client_id' => (string) $content->getAttribute('gsi_client_id'), // Client ID assigned by GSI
					'gsi_store_id' => (string) $content->getAttribute('gsi_store_id'), // Client store/channel.
					'unique_id' => $this->_extractUniqueId($feedXPath, $contentIndex, $catalogId), // Unique identifier for the item, SKU.
					'style_id' => $this->_extractStyleID($feedXPath, $contentIndex, $catalogId), // the parent sku related to the this item
					'product_links' => $this->_extractProductLinks($feedXPath, $contentIndex, $catalogId), // List of related products.
					'category_links' => $this->_extractCategoryLinks($feedXPath, $contentIndex, $catalogId), // Link the product into categories.
					'base_attributes' => $this->_extractBaseAttributes($feedXPath, $contentIndex, $catalogId), // base product attributes (name/title)
					'extended_attributes' => $this->_extractExtendedAttributes($feedXPath, $contentIndex, $catalogId), // Attributes known to eb2c.
					'custom_attributes' => $this->_extractCustomAttributes($feedXPath, $contentIndex, $catalogId), // additional attributes
				)
			);

			// increment Content index
			$contentIndex++;
		}

		return $collectionOfContents;
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Pricing_Extractor
{
	/**
	 * map an xpath to the name of the field the data will be extracted to.
	 * @var array
	 */
	protected $_extractionMap = array(
		'EventNumber/text()' => 'event_number',
		'Price/text()' => 'price',
		'MSRP/text()' => 'msrp',
		'AlternatePrice1/text()' => 'alternate_price',
		'StartDate/text()' => 'start_date',
		'EndDate/text()' => 'end_date',
		'PriceVatInclusive/text()' => 'price_vat_inclusive',
	);

	/**
	 * extract the data for a pricing event.
	 * @param  TrueAction_Dom_Element $eventNode
	 * @return array
	 */
	protected function _extractEvent(TrueAction_Dom_Element $eventNode)
	{
		$result = array();
		$x = new DOMXPath($eventNode->ownerDocument);
		foreach ($this->_extractionMap as $path => $fieldName) {
			$node = $x->query($path, $eventNode)->item(0);
			if ($node && $node->nodeValue) {
				$value = trim($node->nodeValue);
				if ($fieldName === 'price_vat_inclusive') {
					$value = (strtoupper($value) === 'TRUE') ? true : false;
				}
				$result[$fieldName] = $value;
			}
		}
		return $result;
	}

	/**
	 * extract the data from a PricePerItem node.
	 * @param  TrueAction_Dom_Element $eventNode
	 * @return array
	 */
	protected function _extractPricePerItem(TrueAction_Dom_Element $pricePerItemNode)
	{
		$result = array();
		$result['gsi_store_id'] = trim($pricePerItemNode->getAttribute('gsi_store_id'));
		$result['gsi_client_id'] = trim($pricePerItemNode->getAttribute('gsi_client_id'));
		$result['catalog_id'] = trim($pricePerItemNode->getAttribute('catalog_id'));
		$x = new DOMXPath($pricePerItemNode->ownerDocument);
		$path = 'ClientItemId/text()';
		$node = $x->query($path, $pricePerItemNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['client_item_id'] = trim($node->nodeValue);
		}
		$path = 'Event';
		$eventNodes = $x->query($path, $pricePerItemNode);
		$result['events'] = array();
		foreach ($eventNodes as $eventNode) {
			$result['events'][] = new Varien_Object($this->_extractEvent($eventNode));
		}
		return $result;
	}

	/**
	 * extract data from the feed document.
	 * @param  TrueAction_Dom_Document $doc the feed document
	 * @return array                        list containing a Varien_Object for each PricePerItem node
	 */
	public function extractPricingFeed(TrueAction_Dom_Document $doc)
	{
		$collectionOfItems = array();
		$feedXPath = new DOMXPath($doc);
		$nodeList = $feedXPath->query('//PricePerItem');
		foreach ($nodeList as $item) {
			// setting item object into the colelction of item objects.
			$collectionOfItems[] = new Varien_Object($this->_extractPricePerItem($item));
		}
		return $collectionOfItems;
	}
}

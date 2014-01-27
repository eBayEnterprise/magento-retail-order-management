<?php

class TrueAction_Eb2cProduct_Model_Feed_Extractor
{
	const CALLBACK_CONFIG_PATH = 'eb2cproduct/feed_attribute_mappings';
	/**
	 * XPath expression used to chunk the feed file into separate items
	 */
	const BASE_XPATH = '/Items/Item';
	/**
	 * Array of callback configuration
	 * @var array
	 */
	protected $_callbacks = array();
	/**
	 * Constructor should load up all of the callback configuration for product
	 * feed extraction.
	 */
	public function __construct()
	{
		$this->_callbacks = Mage::helper('eb2ccore/feed')->getConfigData(self::CALLBACK_CONFIG_PATH);
	}
	/**
	 * @param DOMDocument $doc
	 * @return array
	 */
	public function extractData(DOMDocument $doc)
	{
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
		$items = $xpath->query(self::BASE_XPATH);
		$extractedData = array();
		foreach ($items as $item) {
			$extractedData[] = $this->_extractItem($xpath, $item);
		}
		return $extractedData;
	}
	/**
	 * Extract data from a single item using the callback configuration.
	 * only callback methods on key value array with type not disabled
	 * @param  DOMXPath $xpath       DOMXPath object loaded with the DOMDocument to extract data from
	 * @param  DOMNode  $contextNode DOMNode to be used as the context for all XPath queries
	 * @return array Extracted data
	 */
	protected function _extractItem(DOMXPath $xpath, DOMNode $contextNode)
	{
		$coreHelper = Mage::helper('eb2ccore/feed');
		$itemData = array();
		foreach ($this->_callbacks as $attribute => $callback) {
			if ($callback['type'] !== 'disabled') {
				$result = $xpath->evaluate($callback['xpath'], $contextNode);
				if ($this->_validateResult($result)) {
					$callback['parameters'] = array($result);
					$itemData[$attribute] = $coreHelper->invokeCallback($callback);
				}
			}
		}
		return $itemData;
	}

	/**
	 * in order to determine if the result from the xpath evaluate fail
	 * because of a bad xpath expression or if the result is actually the value of
	 * expression we pass to. The evaluate method return false on failure of bad xpath expression
	 * and the actual value false if the expression we pass it is the boolean value false
	 * this method will test the return result of the evaluate if the result is false it will return
	 * false if the evualte result is a DOMNodeList object with item on it will return true or false
	 * if the DOMNodeList object don't have an item, any non boolean false value will return true
	 * @param mixed $result
	 * @return bool
	 */
	protected function _validateResult($result)
	{
		return !($result instanceof DOMNodeList && $result->length === 0);
	}
}

<?php
/**
 * extractor that extracts data from a ValueDesc structure.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Color
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	protected $_baseKey;
	protected $_baseXpath;
	protected $_valueKeyAlias;
	protected $_valueXpath;

	/**
	 * setup the extractor.
	 * @param array   $args
	 * array   single mapping the root key to the base xpath
	 * array   [optional] name and xpath for the contents of the "Value" node.
	 * @throws Mage_Core_Exception
	 */
	public function __construct(array $args)
	{
		if (!isset($args[0]) || !is_array($args[0]) || !$args[0]) {
			throw new TrueAction_Eb2cProduct_Model_Feed_Extractor_Exception(sprintf(
				'[ %s ] The 1st argument in the initializer array must be an array mapping the top-level key to an xpath string', __CLASS__
			));
		}
		$this->_baseXpath = current($args[0]);
		$this->_baseKey = key($args[0]);

		$this->_valueKeyAlias = 'value';
		$this->_valueXpath = 'Value/text()';
		if (isset($args[1])) {
			if (!is_array($args[1])) {
				throw new TrueAction_Eb2cProduct_Model_Feed_Extractor_Exception(sprintf(
					'[ %s ] The 2nd argument in the initializer array must be an array like array(key_alias => xpath_string)', __CLASS__
				));
			}
			$this->_valueKeyAlias = key($args[1]) ? key($args[1]) : $this->_valueKeyAlias;
			$this->_valueXpath = current($args[1]) ? current($args[1]) : $this->_valueXpath;
		}

		return $this;
	}

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array      extract structure as follows:
	 *
	 * array(
	 *     root_key => array(
	 *         key_alias => value_node_string,
	 *         'description' => array(
	 *             'description' => description_string,
	 *             'lang' => xml_language_string
	 *         ),
	 *     ),
	 *     ...
	 * )
	 *
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$value = null;
		$localizedValues = array();

		foreach ($this->_queryNodeList($xpath, $node, $this->_baseXpath) as $child) {
			$value = $this->_extractValue($xpath, $child, $this->_valueXpath);
			if (!$value) {
				Mage::log(
					sprintf('[ %s ] ValueDesc element at xpath "%s" contains an empty value node. skipping.', __CLASS__, $this->_baseXpath),
					Zend_Log::WARN
				);
				continue;
			}
			$localizedValues = $this->_extractLocalizedDescription($xpath, $child);
		}

		if ($value) {
			return array($this->_baseKey => array(
				$this->_valueKeyAlias => $value,
				'localization' => $localizedValues,
			));
		}
		return array();
	}

	/**
	 * query nodes list from xpath
	 * @param DOMXPath $xpath
	 * @param DOMElement $node
	 * @param string $baseXpath
	 * @return DOMNodeList | throw TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	protected function _queryNodeList(DOMXPath $xpath, DOMElement $node, $baseXpath)
	{
		try {
			return $xpath->query($baseXpath, $node);
		} catch (Exception $e) {
			throw new TrueAction_Eb2cProduct_Model_Feed_Exception(sprintf(
				'[ %s ] the xpath "%s" could not be queried: %s', __CLASS__, $baseXpath, $e->getMessage()
			));
		}
	}

	/**
	 * extract value from nodes list
	 * @param DOMXPath $xpath
	 * @param DOMElement $child
	 * @param string $valueXpath
	 * @return string
	 */
	protected function _extractValue(DOMXPath $xpath, DOMElement $child, $valueXpath)
	{
		$nodeList = $this->_queryNodeList($xpath, $child, $valueXpath);
		return ($nodeList->length && $nodeList->item(0))? trim($nodeList->item(0)->nodeValue) : null;
	}

	/**
	 * extract localized description
	 * @param DOMXPath $xpath
	 * @param DOMElement $child
	 * @param string $valueXpath
	 * @return array
	 */
	protected function _extractLocalizedDescription(DOMXPath $xpath, DOMElement $child)
	{
		$localizedValues = array();
		$localizedValuesNodes = $xpath->query('Description', $child);
		foreach ($localizedValuesNodes as $valueElement) {
			$localizedValues[$valueElement->getAttribute('xml:lang')] = $valueElement->nodeValue;
		}
		return $localizedValues;
	}
}

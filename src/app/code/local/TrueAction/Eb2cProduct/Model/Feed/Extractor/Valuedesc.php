<?php
/**
 * extractor that extracts data from a ValueDesc structure.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Valuedesc
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	protected $_baseKey;
	protected $_baseXpath;
	protected $_valueKeyAlias;
	protected $_valueXpath;

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array      extract structure as follows:
	 *
	 * array(
	 *     root_key => array(
	 *         key_alias => value_node_string,
	 *         'description' => description_string,
	 *         'lang' => xml_language_string
	 *     ),
	 *     ...
	 * )
	 *
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$result = array();
		try {
			$nodes = $xpath->query($this->_baseXpath, $node);
		} catch (Exception $e) {
			Mage::throwException(
				'[ ' . get_called_class() . ' ] the xpath "' . $this->_baseXpath . '" could not be queried: ' . $e->getMessage()
			);
		}
		foreach ($nodes as $child) {
			$value = null;
			$nodeList = $xpath->query($this->_valueXpath, $child);
			if ($nodeList->length && $nodeList->item(0)) {
				$value = trim($nodeList->item(0)->nodeValue);
			}
			if (!$value) {
				Mage::log(
					'[ ' . __CLASS__ . ' ] ValueDesc element at xpath "' . $this->_baseXpath . '" contains an empty value node. skipping.',
					Zend_Log::WARN
				);
				continue;
			}

			$result[] = array(
				$this->_valueKeyAlias => $value,
				'description' => trim($xpath->query('Description/text()', $child)->item(0)->nodeValue),
				'lang' => trim($xpath->query('Description/@xml:lang', $child)->item(0)->nodeValue),
			);
		}
		return empty($result) ? array() : array($this->_baseKey => $result);
	}

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
			Mage::throwException(
				'[ ' . __CLASS__ . ' ] The 1st argument in the initializer array must be an array mapping the top-level key to an xpath string'
			);
		}
		$this->_baseXpath = current($args[0]);
		$this->_baseKey = key($args[0]);

		$this->_valueKeyAlias = 'value';
		$this->_valueXpath = 'Value/text()';
		if (isset($args[1])) {
			if (!is_array($args[1])) {
				Mage::throwException(
					'[ ' . __CLASS__ . ' ] The 2nd argument in the initializer array must be an array like array(key_alias => xpath_string)'
				);
			}
			$this->_valueKeyAlias = key($args[1]) ? key($args[1]) : $this->_valueKeyAlias;
			$this->_valueXpath = current($args[1]) ? current($args[1]) : $this->_valueXpath;
		}
	}
}

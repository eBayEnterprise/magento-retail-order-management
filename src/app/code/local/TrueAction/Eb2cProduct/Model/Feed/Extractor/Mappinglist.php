<?php
/**
 * extractor that extracts data from a ValueDesc structure.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Mappinglist
	extends TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	protected $_baseKey;
	protected $_baseXpath;

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
			throw new Mage_Core_Exception(
				'[ ' . get_called_class() . ' ] the xpath "' . $this->_baseXpath . '" could not be queried: ' . $e->getMessage()
			);
		}
		foreach ($nodes as $child) {
			$struct = parent::extract($xpath, $child);
			// TODO: SHOULD I ADD A WARNING MESSAGE IF ONE OF THE VALUES IS UNREADABLE
			if (!empty($struct)) {
				array_push($result, $struct);
			}
		}
		return empty($result) ? array() : array($this->_baseKey => $result);
	}

	/**
	 * setup the extractor.
	 * @param array   $args
	 * array   single mapping the root key to the base xpath
	 * array   mapping of name to xpath relative to the base xpath.
	 * @throws Mage_Core_Exception
	 */
	public function __construct(array $args)
	{
		if (!isset($args[0]) || !is_array($args[0]) || !$args[0]) {
			throw new Mage_Core_Exception(
				'[ ' . __CLASS__ . ' ] The 1st argument in the initializer array must be an array mapping the top-level key to an xpath string'
			);
		}
		$this->_baseXpath = current($args[0]);
		$this->_baseKey = key($args[0]);

		if (!isset($args[1]) || !is_array($args[1])) {
			throw new Mage_Core_Exception(
				'[ ' . __CLASS__ . ' ] The 2nd argument in the initializer array must be a mapping array'
			);
		}
		parent::__construct(array($args[1]));
	}
}

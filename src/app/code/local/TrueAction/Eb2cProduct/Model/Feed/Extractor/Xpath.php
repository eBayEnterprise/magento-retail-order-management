<?php
/**
 * Abstract the extraction of values using a simple xpath.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	protected $_mapping;
	protected $_trimValue;

	/**
	 * extract the value of a node as text
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array      extracted data
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$result = array();
		foreach ($this->_mapping as $key => $xpathString) {
			$nodeList = $xpath->query($xpathString, $node);
			$value = null;
			if ($nodeList->length && $nodeList->item(0)) {
				$value = $nodeList->item(0)->nodeValue;
				if ($this->_trimValue) {
					$value = trim($value);
				}
			}
			$result[$key] = $value;
		}
		return $result;
	}

	/**
	 * @param $args an array containing the following:
	 * array   mapping of a field to the xpath string to the field's value
	 * boolean trim each extracted value if true; return values as is otherwise.
	 * @throws Mage_Core_Exception if the mapping parameter is missing.
	 */
	public function __construct(array $args)
	{
		if (count($args) < 1 || !is_array($args[0])) {
			throw new Mage_Core_Exception(
				'[' . __CLASS__ . '] initializer array must have the mapping as the first element'
			);
		}
		$this->_mapping = $args[0];
		$this->_trimValue = count($args) > 1 ? (boolean) $args[1] : true;
	}
}

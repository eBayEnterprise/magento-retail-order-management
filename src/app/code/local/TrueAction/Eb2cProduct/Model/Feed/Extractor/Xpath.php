<?php
/**
 * Abstract the extraction of values using a simple xpath.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath {
	protected $_mapping;
	protected $_trimValue;

	/**
	 * extract the value of a node as text
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array             key/value pairs of any extracted values
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
	 * @param array   $extractMap mapping of a field to the xpath string to the field's value
	 * @param boolean $trimValue  trim each extracted value if true; return values as is otherwise.
	 */
	public function __construct(array $extractMap, $trimValue=true)
	{
		$this->_mapping = $extractMap;
		$this->_trimValue = $trimValue;
	}
}

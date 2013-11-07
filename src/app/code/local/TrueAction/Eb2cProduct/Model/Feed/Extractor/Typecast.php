<?php
/**
 * extractor that typecasts each extracted value.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Typecast
	extends TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	protected $_destinationType = null;

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array      mapping of key to extracted value.
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$result = array();
		foreach ($this->_mapping as $key => $xpathString) {
			$nodeList = $xpath->query($xpathString, $node);
			if ($nodeList->length && $nodeList->item(0)) {
				$value = $nodeList->item(0)->nodeValue;
				if ($this->_trimValue) {
					$value = trim($value);
				}
				settype($value, $this->_destinationType);
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * setup the extractor.
	 * @param array   $args
	 * array   mapping
	 * string  type name to be passed into the settype function
	 * boolean [optional] trim whitespace from the value if true
	 * @throws Mage_Core_Exception if typeName is blank
	 */
	public function __construct(array $args)
	{
		$test = '0';
		if (!isset($args[1]) || !is_string($args[1]) || settype($test, $args[1]) === false) {
			throw new Mage_Core_Exception(
				'[ ' . __CLASS__ . ' ] initializer array must contain a valid type name'
			);
		}
		$trimValue = isset($args[2]) ? $args[2] : true;
		// force trim the value if the destination type is numeric
		$trimValue = $trimValue || array_search($args[1], array('int', 'float', 'double')) === false ?
			$trimValue :
			true;
		parent::__construct(array($args[0], $trimValue));
		$this->_destinationType = $args[1];
	}
}

<?php
/**
 * interface for models that extract the operation type from a unit.
 */
interface TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
	extends TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface {

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return string     extracted value
	 */
	public function getValue(DOMXPath $xpath, DOMElement $node);
}

<?php
/**
 * A common interface for all eb2cproduct feed extractor classes to implement
 */
interface TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface {
	/**
	 * extract the value of a node as text
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return array             key/value pairs of any extracted values
	 */
	public function extract(DOMXPath $xpath, DOMElement $node);
}

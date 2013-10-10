<?php
/**
 * returns add as the operation.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Operationtype
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
{
	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return Varien_Object     a varien object containing the operation type.
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		return array(
			'operation' => TrueAction_Eb2cProduct_Model_Feed_Queue_Interface::OPERATION_TYPE_ADD
		);
	}

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return string     extracted operation type
	 */
	public function getValue(DOMXPath $xpath, DOMElement $node)
	{
		return TrueAction_Eb2cProduct_Model_Feed_Queue_Interface::OPERATION_TYPE_ADD;
	}
}

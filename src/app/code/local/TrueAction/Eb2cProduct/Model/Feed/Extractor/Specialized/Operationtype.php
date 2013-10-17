<?php
/**
 * returns add as the operation.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Operationtype
	extends TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
{
	protected static $_defaultOperation = array(
		'operation' => TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface::OPERATION_TYPE_ADD
	);

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return Varien_Object     a varien object containing the operation type.
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$result = $this->_mapping ? parent::extract($xpath, $node) : self::$_defaultOperation;
		if (!isset($result['operation'])) {
			throw new Mage_Core_Exception('[ ' . __CLASS__ . '] unable to extract operation type.');
		}
		$result['operation'] = strtoupper($result['operation']);
		return $result;
	}

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return string     extracted operation type
	 */
	public function getValue(DOMXPath $xpath, DOMElement $node)
	{
		$result = $this->extract($xpath, $node);
		return  $result['operation'];
	}

	public function __construct($xPathStr=null)
	{
		if ($xPathStr) {
			$this->_mapping = array('operation' => $xPathStr);
		}
	}
}

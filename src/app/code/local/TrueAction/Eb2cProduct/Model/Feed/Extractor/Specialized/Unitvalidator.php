<?php
/**
 * returns add as the operation.
 */
class TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Unitvalidator
	extends TrueAction_Eb2cProduct_Model_Feed_Extractor_Xpath
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
{
	protected $_answer;
	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return Varien_Object     a varien object
	 */
	public function extract(DOMXPath $xpath, DOMElement $node)
	{
		$result = parent::extract($xpath, $node);
		if (array_diff_assoc($result, $this->_answer)) {
			$resultStr = print_r($result, true);
			Mage::log(
				sprintf("[ %s ] The unit failed validation: '%s'", __CLASS__, $resultStr),
				Zend_Log::WARN
			);
			$result = array();
		}
		return $result;
	}

	/**
	 * @param  DOMXPath   $xpath xpath to use when extracting the data
	 * @param  DOMElement $node  node to extract data from
	 * @return boolean    whether the unit is valid or not.
	 */
	public function getValue(DOMXPath $xpath, DOMElement $node)
	{
		$result = $this->extract($xpath, $node);
		return (boolean) $result;
	}

	public function __construct($args)
	{
		$mapping = array_shift($args);
		parent::__construct(array($mapping));
		$answer = array_shift($args);
		if (!is_array($answer) || array_diff_key($answer, $mapping)) {
			throw new Mage_Core_Exception(
				'[ ' . __CLASS__ . '] the 2nd element of the initializer array must be an array and the keys must match the keys of the mapping.'
			);
		}
		$this->_answer = $answer;
	}
}

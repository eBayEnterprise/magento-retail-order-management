<?php
abstract class TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	/**
	 * config model
	 * @var TrueAction_Eb2cCore_Model_Config_Interface
	 */
	protected $_config;

	/**
	 * extractor model used to extract the unit level operation type.
	 * @var TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
	 */
	protected $_operationExtractor;

	/**
	 * get the xpath used to split a feed document into processable units
	 * @return string xpath
	 */
	abstract public function getBaseXpath();

	/**
	 * @return Iterable list of extractor models
	 */
	abstract public function getExtractors();

	abstract public function getFeedLocalPath();

	abstract public function getFeedRemotePath();

	abstract public function getFeedFilePattern();

	abstract public function getFeedEventType();

	/**
	 * @return array callback to extract the operation type from the unit.
	 */
	public function getOperationExtractor()
	{
		return $this->_operationExtractor;
	}

	/**
	 * @param  TrueAction_Dom_Document $doc dom document
	 * @return DOMXPath                     xpath object configured to query data from $doc
	 */
	public function getNewXpath(TrueAction_Dom_Document $doc)
	{
		return new DOMXPath($doc);
	}

	/**
	 * perform transformations on the extracted data
	 * @param Varien_Object $dataObject object containing the data to be transformed
	 * @return self
	 */
	public function transformData(Varien_Object $dataObject)
	{
		return $this;
	}

	/**
	 * setup the some internals
	 */
	public function __construct()
	{
		$this->_config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_operationExtractor = Mage::getModel('eb2cproduct/feed_extractor_specialized_operationtype');
	}
}

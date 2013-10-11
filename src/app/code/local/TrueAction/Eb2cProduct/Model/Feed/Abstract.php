<?php
class TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	/**
	 * config model
	 * @var TrueAction_Eb2cCore_Model_Config_Interface
	 */
	protected $_config;

	/**
	 * extractor model used to extract the unit level operation type.
	 * @var TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
	 */
	protected $_operationExtractor;

	/**
	 * list of extractor models used extract data from each unit.
	 * @var array(TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface)
	 */
	protected $_extractors;

	/**
	 * necessary configuration data
	 * @var [type]
	 */
	protected $_baseXpath;
	protected $_feedLocalPath;
	protected $_feedRemotePath;
	protected $_feedFilePattern;
	protected $_feedEventType;

	/**
	 * get the xpath used to split a feed document into processable units
	 * @return string xpath
	 */
	public function getBaseXpath()
	{
		return $this->_baseXpath;
	}

	public function getFeedLocalPath()
	{
		return $this->_feedLocalPath;
	}

	public function getFeedRemotePath()
	{
		return $this->_feedRemotePath;
	}

	abstract public function getFeedFilePattern();
	{
		return $this->_feedFilePattern;
	}

	abstract public function getFeedEventType();
	{
		return $this->_feedEventType;
	}

	/**
	 * @return @see _extractors declaration
	 */
	public function getExtractors()
	{
		return $this->_extractors;
	}

	/**
	 * get the extractor model used to get the operation type from the unit.
	 * @return TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
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

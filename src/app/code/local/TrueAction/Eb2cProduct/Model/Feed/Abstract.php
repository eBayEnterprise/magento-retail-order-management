<?php
/**
 *
 */
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
	 * xpath used to split a feed document into processable units
	 * @var string
	 */
	protected $_baseXpath;

	/**
	 * xpath used to split a feed document into processable units
	 * @var string
	 */
	protected $_feedLocalPath;

	/**
	 * xpath used to split a feed document into processable units
	 * @var string
	 */
	protected $_feedRemotePath;

	/**
	 * xpath used to split a feed document into processable units
	 * @var string
	 */
	protected $_feedFilePattern;

	/**
	 * xpath used to split a feed document into processable units
	 * @var string
	 */
	protected $_feedEventType;

	/**
	 * validates a unit
	 */
	protected $_unitValidationExtractor;

	/**
	 * @return @see _baseXpath
	 * @codeCoverageIgnore
	 */
	public function getBaseXpath()
	{
		return $this->_baseXpath;
	}

	/**
	 * @return @see _feedLocalPath
	 * @codeCoverageIgnore
	 */
	public function getFeedLocalPath()
	{
		return $this->_feedLocalPath;
	}

	/**
	 * @return @see _feedRemotePath
	 * @codeCoverageIgnore
	 */
	public function getFeedRemotePath()
	{
		return $this->_feedRemotePath;
	}

	/**
	 * @return @see _feedFilePattern
	 * @codeCoverageIgnore
	 */
	public function getFeedFilePattern()
	{
		return $this->_feedFilePattern;
	}

	/**
	 * @return @see _feedEventType
	 * @codeCoverageIgnore
	 */
	public function getFeedEventType()
	{
		return $this->_feedEventType;
	}

	/**
	 * @return @see _extractors
	 * @codeCoverageIgnore
	 */
	public function getExtractors()
	{
		return $this->_extractors;
	}

	/**
	 * get an extractor that checks if the unit is valid or not
	 * @return
	 * @codeCoverageIgnore
	 */
	public function getUnitValidationExtractor()
	{
		return $this->_unitValidationExtractor;
	}

	/**
	 * get the extractor model used to get the operation type from the unit.
	 * @return TrueAction_Eb2cProduct_Model_Feed_Extractor_Specialized_Interface
	 * @codeCoverageIgnore
	 */
	public function getOperationExtractor()
	{
		return $this->_operationExtractor;
	}

	/**
	 * @param  TrueAction_Dom_Document $doc dom document
	 * @return DOMXPath                     xpath object configured to query data from $doc
	 * @codeCoverageIgnore
	 */
	public function getNewXpath(TrueAction_Dom_Document $doc)
	{
		return new DOMXPath($doc);
	}

	/**
	 * setup the some internals
	 */
	public function __construct()
	{
		$this->_config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_operationExtractor = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_operationtype'
		);
		$this->_unitValidationExtractor = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_unitvalidator',
			array(
				array(
					'catalog_id' => './@catalog_id',
					'gsi_client_id' => './@gsi_client_id',
				),
				array(
					'catalog_id' => $this->_config->catalogId,
					'gsi_client_id' => $this->_config->clientId,
				),
			)
		);
	}
}

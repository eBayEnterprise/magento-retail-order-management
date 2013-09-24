<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Abstract
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
{
	/**
	 * feed data broken out into individually processable chunks.
	 * @var array
	 */
	protected $_chunks = array();

	private $_doc = null;
	protected $_xpath = null;

	/**
	 * set the xpath used to break the dom into processable chuncks.
	 * @param $xpath
	 * @return self
	 */
	// public function setBaseXpath(string $xpath)

	/**
	 * set the mapping array used to extract data from each chunk.
	 * @return self
	 */
	// public function setMapping(array $mapping)

	public function getChunks(TrueAction_Dom_Document $doc=null)
	{
		if ((!$this->_chunks && $doc) || $doc) {
			$this->_doc = $doc;
			$this->_xpath = $this->getNewDomXpath($doc);
			$rawChunks = $this->_split($doc);
			$this->_processChunks($rawChunks);
			$this->_doc = null;
		}
		return $this->_chunks;
	}

	/**
	 * retuns a new instantiated DOMXPath object loaded with the DOMDocument
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return DOMXPath, the new DOMXPath object
	 */
	public function getNewDomXpath(DOMDocument $doc)
	{
		return new DOMXPath($doc);
	}

	protected function _split(TrueAction_Dom_Document $doc)
	{
		$xpath = $this->getNewDomXpath($doc);
		$nodeList = $xpath->query($this->getExtractionModel()->getBaseXpath());
		return $nodeList;
	}

	protected function _processChunks($rawChunks)
	{
		// $xpath = $this->getNewDomXpath(); --- not sure why you need this here
		$this->_mapping = $this->getExtractionModel()->getMapping();
		foreach ($rawChunks as $chunk) {
			$chunkData = array();
			foreach ($this->_mapping as $attribute => $valueSource) {
				try {
					$chunkData[$attribute] = $this->_extractAttribute($attribute, $valueSource, $chunk);
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				}
			}
			$this->_addChunkData($chunkData);
		}
	}

	protected function _addChunkData($chunkData)
	{
		$this->_chunks[] = new Varien_Object($chunkData);
	}

	protected function _extractAttribute($attribute, $valueSource, $chunk)
	{
		$value = null;
		$xpath = $this->_xpath;
		// if an xpath
		if (is_string($valueSource)) {
			try{
				$nodeList = $xpath->query($valueSource, $chunk);
				$node = $nodeList->item(0);
				if ($node) {
					$value = $node->nodeValue;
				}
			} catch(Exception $e) {
				Mage::log('[' . __CLASS__ . '] the following error while extract feed attributes: ' . $e->getMessage(), Zend_Log::DEBUG);
			}
		} elseif (is_array($valueSource)) {
			$args = array($attribute, $chunk, $this->_doc);
			$value = call_user_func_array($valueSource, $args);
		} else {
			$message = __CLASS__ . 'attribute "%s" mapped to an invalid value source [%s]';
			$message = sprintf($message, $attribute, (string) $valueSource);
			Mage::throwException($message);
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		return $value;
	}

	/**
	 * Implementing eb2ccore/feed_abstract method
	 *
	 * @return void
	 */
	public function processDom(TrueAction_Dom_Document $xmlDom)
	{
		return;
	}
}

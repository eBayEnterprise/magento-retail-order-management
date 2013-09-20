<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Abstract
	extends Mage_Core_Model_Abstract
{
	/**
	 * feed data broken out into individually processable chunks.
	 * @var array
	 */
	protected $_chunks = array();

	private $_doc = null;

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
			$this->setXpath($this->getNewXpath($doc));
			$rawChunks = $this->_split($doc);
			$this->_processChunks($rawChunks);
			$this->_doc = null;
		}
		return $this->_chunks;
	}

	protected function _split(TrueAction_Dom_Document $doc)
	{
		$xpath = $this->getXpath();
		$nodeList = $xpath->query($this->getExtractionModel()->getBaseXpath());
		return $nodeList;
	}

	protected function _processChunks($rawChunks)
	{
		$xpath = $this->getXpath();
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
		// if an xpath
		if (is_string($valueSource)) {
			$nodeList = $xpath->query($valueSource, $chunk);
			$node = $nodeList->item(0);
			if ($node) {
				$value = $node->nodeValue;
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
}

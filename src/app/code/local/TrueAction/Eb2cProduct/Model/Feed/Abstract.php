<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
abstract class TrueAction_Eb2cProduct_Model_Feed_Abstract
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
{
	/**
	 * feed data broken out into individually processable chunks.
	 * @var array
	 */
	protected $_chuncks = array();

	private $_doc = null;

	/**
	 * the signature of the callback function used to process a chunk
	 * override this if there's a lot of simple attributes that don't warrant their own callback
	 * @param  string $attribute name of the attribute (as per the mapping)
	 * @param  TrueAction_Dom_Element $chunkNode the current node being processed.
	 * @param  TrueAction_Dom_Document $doc       the dom document
	 * @return mixed standardized value for the attribute.
	 * @throws TrueAction_Eb2cProduct_Feed_Documenterror If an error is encountered that invalidates the document.
	 * @throws TrueAction_Eb2cProduct_Feed_Chunkerror If an error is encountered that skips the chunk.
	 */
	//public function chunkCallback($attribute, TrueAction_Dom_Element $chunkNode=null, TrueAction_Dom_Document $doc=null);

	/**
	 * @return string xpath to the node that represents a unit of processing for the feed
	 */
	abstract public function getBasePath();

	/**
	 * @return array mapping of product attribute to either an xpath string or a callback function
	 */
	abstract public function getMapping();

	/**
	 * get an xpath usable with $doc.
	 * @param  TrueAction_Dom_Document $doc
	 * @return DomXPath      xpath object configured to work with the document.
	 * @codeCoverageIgnore
	 */
	public function getNewXpath(TrueAction_Dom_Document $doc)
	{
		return new DomXpath($doc);
	}

	public function getChunks(TrueAction_Dom_Document $doc=null)
	{
		if ((!$this->_chunks && $doc) || $doc) {
			$this->_doc = $doc;
			$this->setXpath($this->getNewXpath());
			$rawChunks = $this->split($doc);
			$this->_processChunks($rawChunks);
			$this->_doc = null;
		}
		return $this->_chunks;
	}

	protected function _split($doc)
	{
		$xpath = $this->getXpath();
		$nodeList = $xpath->query($this->getBasePath());
		return $nodeList;
	}

	protected function _processChunks($rawChunks)
	{
		$xpath = $this->getXpath();
		$this->_mapping = $this->getMapping();
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

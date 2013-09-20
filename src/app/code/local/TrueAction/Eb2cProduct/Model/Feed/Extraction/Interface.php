<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
interface TrueAction_Eb2cProduct_Model_Feed_Extraction_Interface {
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
	public function getBaseXpath();

	/**
	 * @return array mapping of product attribute to either an xpath string or a callback function
	 */
	public function getMapping();
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Model_Sequence extends Mage_Core_Model_Abstract
{
	/**
	 * Get Dom instantiated object.
	 *
	 * @return TrueAction_Dom_Document
	 */
	protected function _getDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}

	/**
	 * extract the sequence number from xml feed file.
	 *
	 * @param string $file, an xml file
	 *
	 * @return string, the sequence number
	 */
	protected function _extractSequence($file)
	{
		$sequence = '';
		$domDocument = $this->_getDomDocument();
		// load feed files to dom object
		$domDocument->load($file);
		$feedXpath = new DOMXPath($domDocument);
		$correlationId = $feedXpath->query('//MessageHeader/MessageData/CorrelationId');
		if ($correlationId->length) {
			$sequence = (string) $correlationId->item(0)->nodeValue;
		}

		return $sequence;
	}

	/**
	 * get feeds sequence.
	 *
	 * @param array $feeds, a collection of feed files
	 *
	 * @return array, containing each feed file sequence number.
	 */
	public function buildSequence($feeds)
	{
		$results = array();

		if(!empty($feeds)){
			foreach ($feeds as $feed) {
				$results[] = array('sequence' => $this->_extractSequence($feed), 'feed' => $feed);
			}
		}

		return $results;
	}
}

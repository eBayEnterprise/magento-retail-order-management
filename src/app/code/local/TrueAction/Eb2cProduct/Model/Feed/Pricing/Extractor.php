<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Pricing_Extractor
{
	public function __construct()
	{
	}

	/**
	 * parse the $value into a boolean.
	 * return null on invalid input.
	 * @param  string $value
	 * @return bool
	 */
	protected function _asBool($value)
	{
		$result = null;
		switch(strtolower((string) $value)) {
			case 'false':
				$result = false;
				break;
			case 'true':
				$result = true;
				break;
			default:
				Mage::log("unable to convert {$value} to a boolean");
				break;
		}
		return $result;
	}

	/**
	 * extract the data for a pricing event.
	 * @param  TrueAction_Dom_Element $eventNode
	 * @return array
	 */
	protected function _extractEvent(TrueAction_Dom_Element $eventNode)
	{
		$result = array();
		$x = new DOMXPath($eventNode->ownerDocument);
		// get pricing event number
		$path = 'EventNumber/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['event_number'] = $node->nodeValue;
		}
		// get the amount
		$path = 'Price/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['price'] = (float) $node->nodeValue;
		}
		// get the msrp
		$path = 'MSRP/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['msrp'] = (float) $node->nodeValue;
		}
		// ignore this if the eventNumber is not present
		$path = 'AlternatePrice1/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['alternate_price'] = (float) $node->nodeValue;
		}
		$path = 'StartDate/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['start_date'] = $node->nodeValue;
		}
		$path = 'EndDate/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['end_date'] = $node->nodeValue;
		}

		$path = 'PriceVatInclusive/text()';
		$node = $x->query($path, $eventNode)->item(0);
		if ($node && $node->nodeValue) {
			$result['price_vat_inclusive'] = $this->_asBool($node->nodeValue);
		}
		return $result;
	}
}

<?php
/**
 * represents a tax quote from the response.
 * requires the following data to be passed to the constructor
 * @param  int                            type
 * @param  TrueAction_Dom_Element         node
 */
class TrueAction_Eb2cTax_Model_Response_Quote extends Mage_Core_Model_Abstract
{
	const MERCHANDISE = 0;
	const SHIPPING    = 1;
	const DUTY        = 2;

	protected function _construct()
	{
		$this->_init('eb2ctax/response_quote');
		$tax = $this->getNode();
		if ($tax) {
			$xpath = new DOMXPath($tax->ownerDocument);
			$xpath->registerNamespace('a', $tax->namespaceURI);
			$jurisdiction  = $xpath->evaluate('string(a:Jurisdiction)', $tax);
			$imposition    = $xpath->evaluate('string(a:Imposition)', $tax);
			$effectiveRate = $xpath->evaluate('string(a:EffectiveRate)', $tax);
			$code          = $jurisdiction && $imposition ?
				$jurisdiction . '-' . $imposition :
				$effectiveRate;
			$this->setCode($code);
			// get situs
			$this->setSitus($xpath->evaluate('string(a:Situs)', $tax));
			// get effective rate
			$this->setEffectiveRate((float) $effectiveRate);
			// get taxable amount
			$this->setTaxableAmount((float) $xpath->evaluate('string(a:TaxableAmount)', $tax));
			// calculatedtax
			$this->setCalculatedTax((float) $xpath->evaluate('string(a:CalculatedTax)', $tax));
		}
	}
}

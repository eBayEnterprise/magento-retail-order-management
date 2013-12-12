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
			$jurisdiction = $xpath->evaluate('string(a:Jurisdiction)', $tax);
			$imposition = $xpath->evaluate('string(a:Imposition)', $tax);
			$effectiveRate = $xpath->evaluate('number(a:EffectiveRate)', $tax);

			$code              = $jurisdiction && $imposition ?
				$jurisdiction . '-' . $imposition :
				$effectiveRate;

			$this->addData(array(
				'code'               => $code,
				'tax_type'           => $xpath->evaluate('string(@taxType)', $tax),
				'taxability'         => $xpath->evaluate('string(@taxability)', $tax),
				'jurisdiction'       => $jurisdiction,
				'jurisdiction_id'    => $xpath->evaluate('string(a:Jurisdiction/@jurisdictionId)', $tax),
				'jurisdiction_level' => $xpath->evaluate('string(a:Jurisdiction/@jurisdictionLevel)', $tax),
				'imposition'         => $imposition,
				'imposition_type'    => $xpath->evaluate('string(a:Imposition/@impositionType)', $tax),
				'situs'              => $xpath->evaluate('string(a:Situs)', $tax),
				'effective_rate'     => $effectiveRate,
				'taxable_amount'     => $xpath->evaluate('number(a:TaxableAmount)', $tax),
				'calculated_tax'     => $xpath->evaluate('number(a:CalculatedTax)', $tax),
			));
		}
	}
}

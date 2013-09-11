<?php
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2cTax_Model_Response_OrderItem extends Mage_Core_Model_Abstract
{
	/**
	 * orderitem node this instance represents
	 * @var TrueAction_Dom_Element
	 */
	protected $_xpath             = null;
	protected $_isValid           = true;
	protected $_taxQuotes         = array();
	protected $_taxQuoteDiscounts = array();

	/**
	 * @see self::$_isValid
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * @see self::$_taxQuotes
	 * @return array(TrueAction_Eb2cTax_Model_Response_Quote)
	 */
	public function getTaxQuotes()
	{
		return $this->_taxQuotes;
	}

	/**
	 * @see self::$_taxQuoteDiscounts
	 * @return array(TrueAction_Eb2cTax_Model_Response_Quote_Discount)
	 */
	public function getTaxQuoteDiscounts()
	{
		return $this->_taxQuoteDiscounts;
	}

	protected function _construct()
	{
		if ($this->getNode()) {
			$xpath = new DOMXPath($this->getNode()->ownerDocument);
			$xpath->registerNamespace('a', $this->getNode()->namespaceURI);
			$this->_xpath = $xpath;
			$this->_extractData();
		}
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractData()
	{
		$xpath = $this->_xpath;
		$itemNode = $this->getNode();
		$this->setSku($xpath->evaluate('string(./a:ItemId)', $itemNode));
		$this->setLineNumber($xpath->evaluate('string(./@lineNumber)', $itemNode));
		$this->setItemDesc($xpath->evaluate('string(./a:ItemDesc)', $itemNode));
		$this->setHtsCode($xpath->evaluate('string(./a:HTSCode)', $itemNode));
		$val = $xpath->evaluate('number(a:Pricing/a:Merchandise/a:Amount)', $itemNode);
		$this->setMerchandiseAmount($val === NAN ? null : $val);
		$val = $xpath->evaluate('number(a:Pricing/a:Merchandise/a:UnitPrice)', $itemNode);
		$this->setUnitPrice($val === NAN ? null : $val);
		$val = $xpath->evaluate('number(a:Pricing/a:Shipping/a:Amount)', $itemNode);
		$this->setShippingAmount($val === NAN ? null : $val);
		$val = $xpath->evaluate('number(a:Pricing/a:Duty/a:Amount)', $itemNode);
		$this->setDutyAmount($val === NAN ? null : $val);
		$this->_validate();
		// don't bother reading the tax data since the item is invalid
		if ($this->_isValid) {
			$this->_processTaxData($xpath, $itemNode);
			$this->_processDiscountTaxData($xpath, $itemNode);
		}
	}

	/**
	 * parse the discount nodes into objects.
	 * @param  DOMXPath $xpath
	 * @param  TrueAction_Dom_Element $itemNode
	 */
	protected function _processDiscountTaxData($xpath, $itemNode)
	{
		$type = TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE;
		$taxNodes = $xpath->query('a:Pricing/a:Merchandise/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			$this->_taxQuoteDiscounts[] = Mage::getModel(
				'eb2ctax/response_quote_discount',
				array(
					'type'           => $type,
					'node'           => $taxNode
				)
			);
		}
		$type = TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING;
		$taxNodes = $xpath->query('a:Pricing/a:Shipping/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			$this->_taxQuoteDiscounts[] = Mage::getModel(
				'eb2ctax/response_quote_discount',
				array(
					'type' => $type,
					'node' => $taxNode
				)
			);
		}
		$type = TrueAction_Eb2cTax_Model_Response_Quote::DUTY;
		$taxNodes = $xpath->query('a:Pricing/a:Duty/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			$this->_taxQuoteDiscounts[] = Mage::getModel(
				'eb2ctax/response_quote_discount',
				array(
					'type'           => $type,
					'node'           => $taxNode
				)
			);
		}
	}

	protected function _processTaxData($xpath, $itemNode)
	{
		$type = TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE;
		$taxNodes = $xpath->query('a:Pricing/a:Merchandise/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			$this->_taxQuotes[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type'           => $type,
					'node'           => $taxNode
				)
			);
		}
		$type = TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING;
		$taxNodes = $xpath->query('a:Pricing/a:Shipping/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			// foreach pricing/shipping/taxdata/
			$this->_taxQuotes[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type' => $type,
					'node' => $taxNode
				)
			);
		}
		$type = TrueAction_Eb2cTax_Model_Response_Quote::DUTY;
		$taxNodes = $xpath->query('a:Pricing/a:Duty/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			// foreach pricing/shipping/taxdata/
			$this->_taxQuotes[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type'           => $type,
					'node'           => $taxNode
				)
			);
		}
	}

	protected function _validate()
	{
		if (!$this->getSku()) {
			$this->_isValid = false;
			Mage::log('[' . __CLASS__ . '] TaxDutyResponse: OrderItem received with an empty sku.');
		}
		if (!$this->getLineNumber()) {
			Mage::log('[' . __CLASS__ . '] TaxDutyResponse: OrderItem received with an empty lineNumber attribute.');
		}
	}
}

<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2cTax_Model_Response extends Mage_Core_Model_Abstract
{
	/**
	 * the sales/quote_address object
	 * @var Mage_Sales_Quote_Address
	 */
	protected $_address = null;

	/**
	 * the dom document object for the response
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc = null;

	/**
	 * result objects parsed from the response
	 * @var array
	 */
	protected $_responseItems = array();

	/**
	 * discount amounts parsed from the response.
	 * @var array
	 */
	protected $_discounts     = array();

	/**
	 * skus of OrderItem elements that passed validation
	 * @var array(string)
	 */
	protected $_validSkus = array();

	/**
	 * is the response valid
	 * @var boolean
	 */
	protected $_isValid = false;

	/**
	 * namespace uri of the root element.
	 * @var string
	 */
	protected $_namespaceUri = '';

	protected function _construct()
	{
		$this->_doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_doc->preserveWhiteSpace = false;
		if ($this->hasXml()) {
			$this->_doc->loadXML($this->getXml());
			$this->_namespaceUri = $this->_doc->documentElement->namespaceURI;
			$this->_extractResults();
			// validate response
			$this->_isValid = $this->_validateDestinations();
		}
	}

	/**
	 * loading sales/quoate_address object
	 *
	 * @param int $addressId, the address id to load the object data.
	 *
	 * @return Mage_Sales_Quote_Address
	 */
	protected function _loadAddress($addressId)
	{
		if (!($this->_address && $addressId)) {
			$this->_address = Mage::getModel('sales/quote_address');
		}
		return $this->_address->load($addressId);
	}

	/**
	 * get the response for the specified sku and address id.
	 * return null if there is no valid response to retrieve.
	 * @param  string $sku
	 * @param  int    $addressId
	 * @return TrueAction_Eb2cTax_Model_Response_OrderItem
	 */
	public function getResponseForItem($item, $address) {
		// ensure the correct types to access the data
		$addressId = (int)$address->getId();
		$sku = (string)$item->getSku();
		$orderItem = isset($this->_responseItems[$addressId][$sku]) ?
			$this->_responseItems[$addressId][$sku] : null;
		return $orderItem;
	}

	/**
	 * get the result records of the request
	 * @return array(TrueAction_Eb2cTax_Model_Response_OrderItem)
	 */
	public function getResponseItems()
	{
		return $this->_responseItems;
	}

	/**
	 * @see _isValid property
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * get the address using the value from the ref attribute.
	 * @param  TrueAction_Dom_Element $shipGroup
	 * @return Mage_Sales_Model_Quote_Address
	 */
	protected function _getAddress(TrueAction_Dom_Element $shipGroup)
	{
		$xpath = new DOMXPath($this->_doc);
		$xpath->registerNamespace('a', $this->_namespaceUri);
		$idRef = $xpath->evaluate('string(./a:DestinationTarget/@ref)', $shipGroup);
		$id = null;
		$idRefArray = explode('_', $idRef);
		if (count($idRefArray) > 1) {
			list(, $id) = $idRefArray;
		}
		$address = $this->_loadAddress($id);
		if (!$address->getId()) {
			$this->_isValid = false;
			$message = "Address referenced by '$idRef' could not be loaded from the quote";
			Mage::log($message, Zend_Log::WARN);
			$address = null;
		}
		return $address;
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractResults()
	{
		$xpath = new DOMXPath($this->_doc);
		// namespace variable
		$xpath->registerNamespace('a', $this->_namespaceUri);
		$root = $this->_doc->documentElement;
		$mailingAddresses = $xpath->query(
			'/a:Shipping/a:Destinations/a:MailingAddress',
			$root
		);
		$shipGroups = $xpath->query(
			'a:Shipping/a:ShipGroups/a:ShipGroup',
			$root
		);
		foreach ($shipGroups as $shipGroup) {
			$address = $this->_getAddress($shipGroup);
			$responseSkus = array();
			// foreach item
			$items = $xpath->query('./a:Items/a:OrderItem', $shipGroup);
			if ($address) {
				// skip the shipgroup we can't get the address
				foreach ($items as $item) {
					$orderItem = Mage::getModel('eb2ctax/response_orderitem', array(
						'node' => $item,
						'namespace_uri' => $this->_namespaceUri
					));
					if ($orderItem->isValid()) {
						$itemKey = (string)$orderItem->getSku();
						$this->_responseItems[$address->getId()][$itemKey] = $orderItem;
					}
				}
			}
		}
		// foreach destination
		// verify data
	}

	/**
	 * validate the destination address and setup shortcuts to allow for
	 * easy access to the validated data.
	 *
	 * @return bool, true both destination response/request are the same, false not the same
	 */
	protected function _validateDestinations()
	{
		$valid = false;
		if ($this->getRequest()) {
			// if we have a request, assume it's valid and look for violations.
			$valid = true;
			$responseXpath = new DOMXPath($this->_doc);
			$responseXpath->registerNamespace('a', $this->_namespaceUri);

			$requestXpath = new DOMXPath($this->getRequest()->getDocument());
			$requestXpath->registerNamespace('a', $this->_namespaceUri);

			$mailingAddresses = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress');
			foreach ($mailingAddresses as $address) {
				$id = $address->getAttribute('id');
				$responseFirstName = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:PersonName/a:FirstName');
				$responseLastName = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:PersonName/a:LastName');
				$responseLineAddress = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:Line1');
				$responseCity = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:City');
				$responseMainDivision = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:MainDivision');
				$responseCountryCode = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:CountryCode');
				$responsePostalCode = $responseXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:PostalCode');

				$requestFirstName = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:PersonName/a:FirstName');
				$requestLastName = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:PersonName/a:LastName');
				$requestLineAddress = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:Line1');
				$requestCity = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:City');
				$requestMainDivision = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:MainDivision');
				$requestCountryCode = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:CountryCode');
				$requestPostalCode = $requestXpath->query('//a:Shipping/a:Destinations/a:MailingAddress[@id="' . $id . '"]/a:Address/a:PostalCode');

				if (!$this->isSameNodelistElement($responseFirstName, $requestFirstName)) {
					$valid = false;
					Mage::log(
						sprintf('%s: FirstName "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseFirstName->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responseLastName, $requestLastName)) {
					$valid = false;
					Mage::log(
						sprintf('%s: LastName "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseLastName->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responseLineAddress, $requestLineAddress)) {
					$valid = false;
					Mage::log(
						sprintf('%s: Address Line 1 "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseLineAddress->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responseCity, $requestCity)) {
					$valid = false;
					Mage::log(
						sprintf('%s: City "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseCity->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responseMainDivision, $requestMainDivision)) {
					$valid = false;
					Mage::log(
						sprintf('%s: Main Division "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseMainDivision->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responseCountryCode, $requestCountryCode)) {
					$valid = false;
					Mage::log(
						sprintf('%s: Country Code "%s" not match in the request.', 'TaxDutyQuoteResponse', $responseCountryCode->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}

				if (!$this->isSameNodelistElement($responsePostalCode, $requestPostalCode)) {
					$valid = false;
					Mage::log(
						sprintf('%s: Postal Code "%s" not match in the request.', 'TaxDutyQuoteResponse', $responsePostalCode->item(0)->nodeValue),
						Zend_Log::DEBUG
					);
				}
			}
		}
		return $valid;
	}

	/**
	 * compare two nodelist element
	 *
	 * @param NodeList $response, the response element nodelist to be compared
	 * @param NodeList $request, the request element nodelist to be compared
	 *
	 * @return boolean, true request and response nodelist element are the same, otherwise, not the same
	 */
	public function isSameNodelistElement($response, $request)
	{
		$isSame = true;
		if ($response->length < 1 || $request->length < 1) {
			$isSame = false;
		} elseif (strtoupper(trim($response->item(0)->nodeValue)) !== strtoupper(trim($request->item(0)->nodeValue))) {
			$isSame = false;
		}
		return $isSame;
	}
}

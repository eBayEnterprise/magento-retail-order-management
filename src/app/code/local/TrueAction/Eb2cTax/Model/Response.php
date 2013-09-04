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
	 * default length of the xml snippet to be reported with libxml errors.
	 *
	 */
	protected $_responseSnippetLength = 40;

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
			$xml = $this->getXml();
			$isDocOk = $this->_checkXml($xml);
			if ($isDocOk) {
				$this->_doc->loadXml($xml);
				$this->_namespaceUri = $this->_doc->documentElement->namespaceURI;
				// validate response
				$this->_isValid = $this->_validateDestinations();
				$this->_isValid = $this->_isValid && $this->_validateResponseItems($this->getRequest()->getDocument(), $this->_doc);
				if ($this->_isValid) {
					$this->_extractResults();
				}
			}
		}
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
		return $this->_isValid && $this->_isRequestValid();
	}

	/**
	 * return true if the request is valid.
	 * @return boolean [description]
	 */
	protected function _isRequestValid()
	{
		return $this->getRequest() && $this->getRequest()->isValid();
	}

	/**
	 * get and verify the address id for the shipgroup.
	 * @param  TrueAction_Dom_Element $shipGroup
	 * @return int
	 */
	protected function _getAddressId(TrueAction_Dom_Element $shipGroup)
	{
		$xpath = new DOMXPath($this->_doc);
		$xpath->registerNamespace('a', $this->_doc->documentElement->namespaceURI);
		$idRef = $xpath->evaluate('string(./a:DestinationTarget/@ref)', $shipGroup);
		$id = null;
		$idRefArray = explode('_', $idRef);
		if (count($idRefArray) > 1) {
			list(, $id) = $idRefArray;
			$id = is_numeric($id) ? (int)$id : null;
		}
		if (!$id) {
			$this->_isValid = false;
			$message = "Unable to parse the address ID from the ShipGroup '$idRef'";
			Mage::log($message, Zend_Log::WARN);
		}
		return $id;
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
			$addressId = $this->_getAddressId($shipGroup);
			$responseSkus = array();
			if ($addressId) {
				$items = $xpath->query('./a:Items/a:OrderItem', $shipGroup);
				// skip the shipgroup we can't get the address
				foreach ($items as $item) {
					$orderItem = Mage::getModel('eb2ctax/response_orderitem', array(
						'node' => $item,
						'namespace_uri' => $this->_namespaceUri
					));
					if ($orderItem->isValid()) {
						$itemKey = (string)$orderItem->getSku();
						$this->_responseItems[$addressId][$itemKey] = $orderItem;
					}
				}
			}
		}
		// foreach destination
		// verify data
	}

	/**
	 * compare an OrderItem element with the corresponding element in the request
	 * to make sure we got back what we sent.
	 * return true if all items match; false otherwise.
	 * @param  TrueAction_Dom_Element $itemNode OrderItem element from the request
	 * @return bool
	 */
	protected function _validateResponseItems($requestDoc, $responseDoc)
	{
		if (!($requestDoc && $requestDoc->documentElement && $responseDoc && $responseDoc->documentElement)) {
			$isValid = false;
			return $isValid;
		}
		$isValid = true;
		$requestXpath = new DOMXPath($requestDoc);
		$requestXpath->registerNamespace('a', $requestDoc->documentElement->namespaceURI);
		$responseXpath = new DOMXPath($responseDoc);
		$responseXpath->registerNamespace('a', $responseDoc->documentElement->namespaceURI);
		$heading = 'TaxDutyQuoteResponse';

		// foreach request shipgroup
		$requestShipgroups = $requestXpath->query('//a:ShipGroup');
		foreach ($requestShipgroups as $shipGroup) {
			if (!$isValid) {
				break;
			}
			// get the shipgroupid
			$shipGroupId = $requestXpath->evaluate('string(./@id)', $shipGroup);
			$sgPath = '//a:ShipGroup[@id="' . $shipGroupId . '"]';
			// create response shipgroup path
			// query the response shipgroup
			$result = $responseXpath->query($sgPath);
			// if nodelist is empty fail
			$isValid = $isValid && $result->length === 1;
			if ($isValid) {
				$responseShipgroup = $result->item(0);
				$orderItems = $requestXpath->query('./a:Items/a:OrderItem', $shipGroup);
				foreach ($orderItems as $orderItem) {
					if (!$isValid) {
						break;
					}
					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:ItemId)', $orderItem);
					$itemSku = $val;
					// constructpath to orderitem
					$resPath = $sgPath . '/a:Items/a:OrderItem/a:ItemId[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					$orderItemPath = $sgPath . '/a:Items/a:OrderItem/a:ItemId[.="' . $val . '"]/..';
					if (!$isValid) {
						Mage::log(
							sprintf('%s: sku "%s" not found in the response.', $heading, $val),
							Zend_Log::WARN
						);
						// don't bother checking any other fields since they will not be found
						break;
					}
					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./@lineNumber)', $orderItem);
					// constructpath to orderitem
					$resPath = $sgPath . '/a:Items/a:OrderItem[@lineNumber="' . $val . '"]/a:ItemId[.="' . $itemSku . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'lineNumber'),
							Zend_Log::WARN
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Quantity)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Quantity[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'Quantity'),
							Zend_Log::WARN
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Merchandise/a:UnitPrice)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Merchandise/a:UnitPrice[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'Pricing/Merchandise/UnitPrice'),
							Zend_Log::WARN
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Shipping/a:Amount)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Shipping/a:Amount[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'Pricing/a:Shipping/a:Amount'),
							Zend_Log::DEBUG
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:ItemDesc)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:ItemDesc[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'ItemDesc'),
							Zend_Log::DEBUG
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:HTSCode)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:HTSCode[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'HTSCode'),
							Zend_Log::DEBUG
						);
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Merchandise/a:Amount)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Merchandise/a:Amount[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						Mage::log(
							sprintf('%s: %s "%s" not found in response for %s.', $heading, $itemSku, $val, 'Pricing/a:Merchandise/a:Amount'),
							Zend_Log::WARN
						);
					}
				}
			}
		}
		return $isValid;
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

	/**
	 * attempt to load the response text into a domdocument.
	 * return true if the document is ok to process; false otherwise
	 */
	protected function _checkXml($xml)
	{
		$result = true;
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$message = '';
		try {
			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$doc->loadXML($xml);
			$errors = libxml_get_errors();
			if (!empty($errors)) {
				$message = $this->_getXmlErrorLogMessage($errors, $xml);
			} elseif ($doc->documentElement->nodeName === 'fault') {
				$message = $this->_getFaultLogMessage($doc);
			} elseif ($doc->documentElement && $doc->documentElement->nodeName !== 'TaxDutyQuoteResponse') {
				$message = 'document was not recognized to be either a TaxDutyQuoteResponse or a Fault message';
			}
			if ($message) {
				$result = false;
				Mage::log($message, Zend_Log::WARN);
			}
			libxml_clear_errors();
			libxml_use_internal_errors(false);
		} catch (Exception $e) {
			$result = false;
			$message = 'Unable to read the response: ' . $e->getMessage();
			Mage::log($message, Zend_Log::WARN);
		}
		return $result;
	}

	protected function _getFaultLogMessage($doc)
	{
		$x = new DOMXPath($doc);
		$ns = '';
		$desc    = $x->evaluate("/{$ns}fault/{$ns}faultstring/text()");
		$code    = $x->evaluate("/{$ns}fault/{$ns}detail/{$ns}errorcode/text()");
		$trace   = $x->evaluate("/{$ns}fault/{$ns}detail/{$ns}trace/text()");
		$desc    = $desc->length ? $desc->item(0)->nodeValue : '';
		$code    = $code->length ? $code->item(0)->nodeValue : '';
		$trace   = $trace->length ? $trace->item(0)->nodeValue : '';
		$message = "Eb2cTax: Fault Message received: " .
			"Code: ({$code}) Description: '{$desc}' Trace: '{$trace}'";
		return $message;
	}

	/**
	 * format libxml errors into a log message.
	 * @param  mixed $errors
	 * @param  string $xml
	 * @return string
	 */
	protected function _getXmlErrorLogMessage($errors, $xml)
	{
		$lines         = explode("\n", $xml);
		$snippetLength = $this->_responseSnippetLength;
		$message       = '';
		$newLine       = '';
		foreach ($errors as $error) {
			$snippet = trim($lines[$error->line - 1]);
			$offset  = max($error->column - (int)($snippetLength / 2), 0);
			$length  = min($snippetLength, strlen($snippet));
			$snippet = substr($snippet, $offset, $length);

			$message .= $newLine . 'XML Parser ';

		    $newLine = "\n"; // only add newlines to subsequent errors
			switch ($error->level) {
			    case LIBXML_ERR_WARNING:
			        $message .= "Warning $error->code: ";
			        break;
			     case LIBXML_ERR_ERROR:
			        $message .= "Error $error->code: ";
			        break;
			    case LIBXML_ERR_FATAL:
			        $message .= "Fatal Error $error->code: ";
			        break;
			}
			$message .= trim($error->message) .
			           " Line: $error->line" .
			           " Column: $error->column: ";
			$message .= "`{$snippet}`";
		}
		return $message;
	}
}

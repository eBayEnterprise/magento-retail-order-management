<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * reads the response from the TaxDutyRequest.
 */
class EbayEnterprise_Eb2cTax_Model_Response extends Varien_Object
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;
	/**
	 * the sales/quote_address object
	 * @var Mage_Sales_Model_Quote_Address
	 */
	protected $_address = null;

	/**
	 * the dom document object for the response
	 * @var EbayEnterprise_Dom_Document
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
	protected $_discounts = array();

	/**
	 * skus of OrderItem elements that passed validation
	 * @var array(string)
	 */
	protected $_validSkus = array();

	/**
	 * is the response valid
	 * @var bool
	 */
	protected $_isValid = false;

	/**
	 * default length of the xml snippet to be reported with libxml errors.
	 */
	protected $_responseSnippetLength = 40;

	/**
	 * namespace uri of the root element.
	 * @var string
	 */
	protected $_namespaceUri = '';

	/**
	 * When a response object is instantiated with the results of a tax request,
	 * constructor will be given:
	 * - xml: the xml response from the tax service
	 * - request: the request object
	 */
	protected function _construct()
	{
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_context = Mage::helper('ebayenterprise_magelog/context');
		$this->_doc = Mage::helper('eb2ccore')->getNewDomDocument();
		// Magic 'xml' data set when instantiated with the results of a tax response
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
			$this->storeResponseData();
		} else {
			$this->loadResponseData();
		}
	}

	/**
	 * get the response for the specified item and address.
	 * return null if there is no valid response to retrieve.
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param  Mage_Sales_Model_Quote_Address       $address
	 * @return EbayEnterprise_Eb2cTax_Model_Response_OrderItem
	 */
	public function getResponseForItem(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Mage_Sales_Model_Quote_Address $address
	)
	{
		// ensure the correct types to access the data
		$addressId = (int) $address->getId();
		$sku = (string) $item->getSku();
		$orderItem = isset($this->_responseItems[$addressId][$sku]) ?
			$this->_responseItems[$addressId][$sku] : null;
		return $orderItem;
	}

	/**
	 * Fetch the data in the session from an earlier tax response
	 */
	public function loadResponseData()
	{
		/** @var EbayEnterprise_Eb2cCore_Model_Session $session */
		$session = Mage::getSingleton('eb2ccore/session');
		foreach ((array) $session->getEb2cTaxResponseData() as $addressId => $addressItems) {
			foreach ($addressItems as $sku => $orderItemData) {
				/** @var EbayEnterprise_Eb2cTax_Model_Response_Orderitem $orderItem */
				$orderItem = Mage::getModel('eb2ctax/response_orderitem');
				$orderItem->setOrderItemData($orderItemData);
				$this->_responseItems[$addressId][$sku] = $orderItem;
			}
		}
		$this->_isValid = (bool) $this->_responseItems;
	}

	/**
	 * Stash the data from the tax response in the session for use later.
	 */
	public function storeResponseData()
	{
		$data = array();
		foreach ($this->_responseItems as $addressId => $addressItems) {
			$data[$addressId] = array();
			foreach ($addressItems as $sku => $orderItem) {
				$data[$addressId][$sku] = $orderItem->getOrderItemData();
			}
		}
		/** @var EbayEnterprise_Eb2cCore_Model_Session $session */
		$session = Mage::getSingleton('eb2ccore/session');
		$session->setEb2cTaxResponseData($data);
	}
	/**
	 * get the result records of the request
	 * @return array(EbayEnterprise_Eb2cTax_Model_Response_OrderItem)
	 */
	public function getResponseItems()
	{
		return $this->_responseItems;
	}

	/**
	 * Get tax response items by quote address id.
	 *
	 * @param int
	 * @return EbayEnterprise_Eb2cTax_Model_Response_OrderItem[]
	 */
	public function getResponseItemsByQuoteAddressId($quoteAddressId)
	{
		return isset($this->_responseItems[$quoteAddressId])
			? $this->_responseItems[$quoteAddressId]
			: [];
	}

	/**
	 * @return bool true if response has valid data; false otherwise.
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * return true if the request is valid.
	 * @return bool [description]
	 */
	protected function _isRequestValid()
	{
		return $this->getRequest() && $this->getRequest()->isValid();
	}

	/**
	 * get and verify the address id for the shipgroup.
	 * @param  EbayEnterprise_Dom_Element $shipGroup
	 * @return int
	 */
	protected function _getAddressId(EbayEnterprise_Dom_Element $shipGroup)
	{
		$xpath = new DOMXPath($this->_doc);
		$xpath->registerNamespace('a', $this->_doc->documentElement->namespaceURI);
		$idRef = $xpath->evaluate('string(./a:DestinationTarget/@ref)', $shipGroup);
		$id = null;
		$idRefArray = explode('_', $idRef);
		if (count($idRefArray) > 1) {
			list(, $id) = $idRefArray;
			$id = is_numeric($id) ? (int) $id : null;
		}
		if (!$id) {
			$this->_isValid = false;
			$logData = ['address_id' => $idRef];
			$logMessage = 'Unable to parse the address id from the shipgroup "{address_id}"';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
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
		$shipGroups = $xpath->query(
			'a:Shipping/a:ShipGroups/a:ShipGroup',
			$root
		);
		foreach ($shipGroups as $shipGroup) {
			$addressId = $this->_getAddressId($shipGroup);
			if ($addressId) {
				$items = $xpath->query('./a:Items/a:OrderItem', $shipGroup);
				// skip the shipgroup we can't get the address
				foreach ($items as $item) {
					$orderItem = Mage::getModel('eb2ctax/response_orderitem', array(
						'node' => $item,
						'namespace_uri' => $this->_namespaceUri,
					));
					if ($orderItem->isValid()) {
						$itemKey = (string) $orderItem->getSku();
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
	 * @param  EbayEnterprise_Dom_Document $requestDoc  The request document
	 * @param  EbayEnterprise_Dom_Document $responseDoc The response document
	 * @return bool
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
						$logData = ['sku' => $val];
						$logMessage = 'SKU "{sku}" not found in the response';
						$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
						// don't bother checking any other fields since they will not be found
						break;
					}
					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./@lineNumber)', $orderItem);
					// constructpath to orderitem
					$resPath = $sgPath . '/a:Items/a:OrderItem[@lineNumber="' . $val . '"]/a:ItemId[.="' . $itemSku . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						$this->_logMissingInResponse($itemSku, $val, 'lineNumber');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Quantity)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Quantity[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						$this->_logMissingInResponse($itemSku, $val, 'Quantity');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Merchandise/a:UnitPrice)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Merchandise/a:UnitPrice[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						$this->_logMissingInResponse($itemSku, $val, 'Pricing/Merchandise/UnitPrice');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Shipping/a:Amount)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Shipping/a:Amount[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						$this->_logMissingInResponse($itemSku, $val, 'Pricing/Shipping/Amount');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:ItemDesc)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:ItemDesc[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						$this->_logMissingInResponse($itemSku, $val, 'ItemDesc');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:HTSCode)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:HTSCode[.="' . $val . '"]';
					$isMatch = $responseXpath->query($resPath)->length === 1;
					if (!$isMatch) {
						$this->_logMissingInResponse($itemSku, $val, 'HTSCode');
					}

					// create paths for each value to check
					$val = $requestXpath->evaluate('string(./a:Pricing/a:Merchandise/a:Amount)', $orderItem);
					// constructpath to orderitem
					$resPath = $orderItemPath . '/a:Pricing/a:Merchandise/a:Amount[.="' . $val . '"]';
					$isValid = $isValid && $responseXpath->query($resPath)->length === 1;
					if (!$isValid) {
						$this->_logMissingInResponse($itemSku, $val, 'Pricing/Merchandise/Amount');
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
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
					$this->_logNoMatchInReq('FirstName', $responseFirstName->item(0)->nodeValue);
				}

				if (!$this->isSameNodelistElement($responseLastName, $requestLastName)) {
					$valid = false;
					$this->_logNoMatchInReq('LastName',  $responseLastName->item(0)->nodeValue);
				}

				if (!$this->isSameNodelistElement($responseLineAddress, $requestLineAddress)) {
					$valid = false;
					$this->_logNoMatchInReq('Address Line 1', $responseLineAddress->item(0)->nodeValue);
				}

				if (!$this->isSameNodelistElement($responseCity, $requestCity)) {
					$valid = false;
					$this->_logNoMatchInReq('City', $responseCity->item(0)->nodeValue);
				}

				if ($requestMainDivision->length > 0 && !$this->isSameNodelistElement($responseMainDivision, $requestMainDivision)) {
					$valid = false;
					$mainDivision = $responseMainDivision->length?$responseMainDivision->item(0)->nodeValue: '';
					$this->_logNoMatchInReq('Main Division', $mainDivision);
				}

				if (!$this->isSameNodelistElement($responseCountryCode, $requestCountryCode)) {
					$valid = false;
					$this->_logNoMatchInReq('Country Code', $responseCountryCode->item(0)->nodeValue);
				}

				if (!$this->isSameNodelistElement($responsePostalCode, $requestPostalCode)) {
					$valid = false;
					$this->_logNoMatchInReq('Postal Code', $responsePostalCode->item(0)->nodeValue);
				}
			}
		}
		return $valid;
	}

	/**
	 * compare two nodelist element
	 *
	 * @param DomNodeList $response the response element nodelist to be compared
	 * @param DomNodeList $request the request element nodelist to be compared
	 *
	 * @return bool, true request and response nodelist element are the same, otherwise, not the same
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
	 * return true if the document is ok to process; false otherwise.
	 * @param  string $xml
	 * @return bool
	 */
	protected function _checkXml($xml)
	{
		$result = true;
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$message = '';
		try {
			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$doc->loadXML($xml);
			$errors = libxml_get_errors();
			if (!empty($errors)) {
				$message = $this->_getXmlErrorLogMessage($errors, $xml);
			} elseif ($doc->documentElement && $doc->documentElement->nodeName === 'fault') {
				$message = $this->_getFaultLogMessage($doc);
			} elseif ($doc->documentElement && $doc->documentElement->nodeName !== 'TaxDutyQuoteResponse') {
				$message = 'document was not recognized to be either a TaxDutyQuoteResponse or a Fault message';
			}
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			if ($message) {
				throw Mage::exception('Mage_Core', $message);
			}
		} catch (Exception $e) {
			$result = false;
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
		}
		return $result;
	}

	/**
	 * get a formatted message suitable for logging from a fault message.
	 * @param  EbayEnterprise_Dom_Document $doc
	 * @return string
	 */
	protected function _getFaultLogMessage(EbayEnterprise_Dom_Document $doc)
	{
		$x = new DOMXPath($doc);
		$ns = '';
		$desc    = $x->evaluate("/{$ns}fault/{$ns}faultstring/text()");
		$code    = $x->evaluate("/{$ns}fault/{$ns}detail/{$ns}errorcode/text()");
		$trace   = $x->evaluate("/{$ns}fault/{$ns}detail/{$ns}trace/text()");
		$desc    = $desc->length ? $desc->item(0)->nodeValue : '';
		$code    = $code->length ? $code->item(0)->nodeValue : '';
		$trace   = $trace->length ? $trace->item(0)->nodeValue : '';
		$message = 'Eb2cTax: Fault Message received: ' .
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
			$offset  = max($error->column - (int) ($snippetLength / 2), 0);
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
				" Line: $error->line Column: $error->column: ";
			$message .= "`{$snippet}`";
		}
		return $message;
	}

	/**
	 * @param $itemSku
	 * @param $val
	 * @param $xpathExpr
	 */
	protected function _logMissingInResponse($itemSku, $val, $xpathExpr)
	{
		$logData = ['item_sku' => $itemSku, 'sku_value' => $val, 'xpath_expression' => $xpathExpr];
		$logMessage = '{item_sku} "{sku_value}" not found in the response for {$xpath_expression}';
		$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
	}

	/**
	 * @param $name
	 * @param $val
	 */
	protected function _logNoMatchInReq($name, $val)
	{
		$logData = ['name' => $name, 'value' => $val];
		$logMessage = '{name} "{value}" did not match in the request';
		$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
	}
}

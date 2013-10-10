<?php
/**
 * generate the xml for an EB2C tax and duty quote request.
 * @author mphang
 */
class TrueAction_Eb2cTax_Model_Request extends Mage_Core_Model_Abstract
{
	const EMAIL_MAX_LENGTH         = 70;
	const NUM_STREET_LINES         = 4;
	protected $_helper             = null;
	protected $_xml                = '';
	protected $_doc                = null;
	protected $_tdRequest          = null;
	protected $_namespaceUri       = '';
	protected $_billingInfoRef     = '';
	protected $_billingEmailRef    = '';
	protected $_hasChanges         = false;
	protected $_store              = null;
	protected $_isMultiShipping    = false;
	protected $_emailAddresses     = array();
	protected $_destinations       = array();
	protected $_orderItems         = array();
	protected $_shipGroups         = array();
	protected $_appliedDiscountIds = array();
	protected $_shipGroupIds       = array();
	protected $_addresses          = array();
	protected $_itemQuantities     = array();

	/**
	 * map skus to a quote item
	 * @var array('string' => Mage_Sales_Model_Quote_Item_Abstract)
	 */
	protected $_skuItemMap = array();

	/**
	 * generate the request DOMDocument on construction.
	 */
	protected function _construct()
	{
		$quote         = $this->getQuote();
		$this->_helper = Mage::helper('tax');
		$this->setIsMultiShipping(0);
		if ($this->_isQuoteUsable($quote)) {
			$this->_store = $quote->getStore();
			$this->setBillingAddress($quote->getBillingAddress());
			$this->setShippingAddress($quote->getShippingAddress());
			$this->_processQuote();
		}
	}

	/**
	 * @return Mage_Core_Model_Store the underlying quote's store model.
	 * @codeCoverageIgnore
	 */
	public function getStore()
	{
		return $this->_store;
	}

	/**
	 * compare the addresses to be sent in the request with the addresses
	 * in the specified quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 */
	public function checkAddresses(Mage_Sales_Model_Quote $quote=null)
	{
		$this->_hasChanges = $this->_hasChanges || !$this->_isQuoteUsable($quote);
		if (!$this->_hasChanges) {
			// check if the billing address has been switched to another address instance
			$this->_hasChanges = $this->_hasChanges || $this->_isMultiShipping !== (bool) $quote->getIsMultiShipping();
			$quoteBillingAddress = $quote->getBillingAddress();
			$quoteBillingDestId  = $this->_getDestinationId($quoteBillingAddress);
			$this->_hasChanges = $this->_hasChanges || $this->_billingInfoRef !== $quoteBillingDestId;
			// check all the addresses.
			foreach ($quote->getAllAddresses() as $address) {
				$this->_hasChanges = $this->_hasChanges || $this->_isAddressDifferent($address);
				$this->_hasChanges = $this->_hasChanges || $this->_isAddressItemsDifferent($address);
			}
		}
	}

	/**
	 * check the discounts for the item and invalidate the quote if there
	 * is a change.
	 * @param Mage_Sales_Model_Quote $quote
	 */
	public function checkDiscounts(Mage_Sales_Model_Quote $quote=null)
	{
		$this->_hasChanges = $this->_hasChanges || !$this->_isQuoteUsable($quote);
		if (!$this->_hasChanges) {
			foreach ($quote->getAllAddresses() as $address) {
				foreach ($this->_getItemsForAddress($address) as $item) {
					$destinationId = $this->_getDestinationId($address, $item->getProduct()->isVirtual());
					$orderItemId = $destinationId . '_' . $item->getSku();
					$orderItem = isset($this->_orderItems[$orderItemId]) ?
						$this->_orderItems[$orderItemId] : null;
					$this->_hasChanges = is_null($orderItem) ? true : $this->_hasChanges;
					$this->_hasChanges = $this->_hasChanges ||
						(isset($orderItem['merchandise_discount_amount'])? $orderItem['merchandise_discount_amount']: null) !== $item->getDiscountAmount();
					$this->_hasChanges = $this->_hasChanges ||
						(isset($orderItem['merchandise_coupon_code'])? $orderItem['merchandise_coupon_code']: null) !== $item->getDiscountAmount();
					$this->_hasChanges = $this->_hasChanges ||
						(isset($orderItem['shipping_discount_amount'])? $orderItem['shipping_discount_amount']: null) !== $item->getDiscountAmount();
					$this->_hasChanges = $this->_hasChanges ||
						(isset($orderItem['shipping_coupon_code'])? $orderItem['shipping_coupon_code']: null) !== $item->getDiscountAmount();
					if ($this->_hasChanges) {
						// stop as soon as something is found to be different.
						break;
					}
				}
			}
		}
	}

	/**
	 * Determine if the request object has enough data to work with.
	 * @return boolean
	 */
	public function isValid()
	{
		return !$this->_hasChanges &&
			$this->_isQuoteUsable($this->getQuote()) &&
			(int) $this->getQuote()->getItemsCount() === count($this->_itemQuantities);
	}

	/**
	 * get the DOMDocument for the request.
	 * @return TrueAction_Dom_Document
	 */
	public function getDocument()
	{
		if (!$this->_doc || !$this->_doc->documentElement) {
			$doc        = new TrueAction_Dom_Document('1.0', 'UTF-8');
			$this->_doc = $doc;
			$doc->preserveWhiteSpace = false;
			if ($this->isValid()) {
				$this->_buildTaxDutyRequest();
			}
		}
		// @codeCoverageIgnoreStart
		return $this->_doc;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Make this request invalid, which will force a new request to
	 * be generated and sent.
	 */
	public function invalidate()
	{
		$this->_hasChanges = true;
	}

	public function checkItemQty($quoteItem)
	{
		$sku = $quoteItem->getSku();

		$quantity = isset($this->_itemQuantities[$sku]) ?
			$this->_itemQuantities[$sku] :
			null;
		$this->_hasChanges = is_null($quantity) ||
			$this->_hasChanges ||
			(float) $quantity !== (float) $quoteItem->getTotalQty();
	}

	protected function _processQuote()
	{
		try {
			$quote = $this->getQuote();
			// track if this is a multishipping quote or not.
			$this->_isMultiShipping = (bool) $quote->getIsMultiShipping();
			// create the billing address destination node(s)
			$billAddress = $quote->getBillingAddress();
			$this->_billingInfoRef = $this->_getDestinationId($billAddress);
			try {
				$this->_destinations[$this->_billingInfoRef] = $this->_extractDestData(
					$billAddress
				);
			} catch (Mage_Core_Exception $e) {
				$message = 'Unable to extract billing address: ' . $e->getMessage();
				throw new Mage_Core_Exception($message);
			}
			foreach ($quote->getAllAddresses() as $address) {
				try {
					// keep a serialized copy of each address for use when looking for changes.
					$this->_addresses[$address->getId()] = serialize($this->_extractDestData($address));
				} catch (Mage_Core_Exception $e) {
					$message = 'Unable to extract shipping address: ' . $e->getMessage();
					throw new Mage_Core_Exception($message);
				}
				$items = $this->_getItemsForAddress($address);
				foreach ($items as $item) {
					if ($item->getHasChildren() && $item->isChildrenCalculated()) {
						foreach ($item->getChildren() as $child) {
							$isVirtual = $child->getProduct()->isVirtual();
							$this->_addToDestination($child, $address, $isVirtual);
							$sku = $child->getSku();
							if (!isset($this->_itemQuantities[$sku])) {
								$this->_itemQuantities[$sku] = 0;
							}
							$this->_itemQuantities[$sku] += $child->getTotalQty();
						}
					} else {
						$isVirtual = $item->getProduct()->isVirtual();
						$this->_addToDestination($item, $address, $isVirtual);
						$sku = $item->getSku();
						if (!isset($this->_itemQuantities[$sku])) {
							$this->_itemQuantities[$sku] = 0;
						}
						$this->_itemQuantities[$sku] += $item->getTotalQty();
					}
				}
			}
		}
		catch (Exception $e) {
			$message = sprintf(
				'[ %s ] Error gathering data for the tax request: %s',
				__CLASS__,
				$e->getMessage()
			);
			Mage::log($message, Zend_Log::WARN);
			$this->invalidate();
		}
	}

	protected function _isAddressDifferent(Mage_Sales_Model_Quote_Address $address)
	{
		$id = $address->getId();
		$result = isset($this->_addresses[$id]) ?
			$this->_addresses[$id] : false;
		if (is_string($result)) {
			$oldData = $result;
			$newData = $this->_extractDestData($address);
			$result  = $oldData !== serialize($newData);
		}
		return $result;
	}

	protected function _isAddressItemsDifferent(Mage_Sales_Model_Quote_Address $address)
	{
		$skuList = array();
		$destinationId = $this->_getDestinationId($address, false);
		$result = isset($this->_shipGroups[$destinationId]) ?
			$this->_shipGroups[$destinationId] : false;
		if ($result !== false) {
			$skuList = $result;
		}
		if ($address->getAddressType() === 'billing') {
			$destinationId = $this->_getDestinationId($address, true);
			$result = isset($this->_shipGroups[$destinationId]) ?
				$this->_shipGroups[$destinationId] : false;
			if ($result !== false) {
				$skuList = array_unique(array_merge($skuList, $result));
			}
		}
		$newSkus = array();
		foreach ($this->_getItemsForAddress($address) as $item) {
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$newSkus[] = $child->getSku();
				}
			} else {
				$newSkus[] = $item->getSku();
			}
		}
		$result = (bool) array_diff($newSkus, $skuList) || (bool) array_diff($skuList, $newSkus);
		return $result;
	}

	/**
	 * return true if the quote has enough information to be useful.
	 * @param  Mage_Sales_Model_Quote  $quote
	 * @return boolean
	 */
	protected function _isQuoteUsable(Mage_Sales_Model_Quote $quote=null)
	{
		return $quote &&
			$quote->getId() &&
			$quote->getBillingAddress() &&
			$quote->getBillingAddress()->getId() &&
			$quote->getItemsCount();
	}

	/**
	 * get a list of all items for $address
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @return array(Mage_Sales_Model_Quote_Item_Abstract)
	 */
	protected function _getItemsForAddress(Mage_Sales_Model_Quote_Address $address)
	{
		return $address->getAllNonNominalItems();
	}

	/**
	 * return a string to use as the address's destination id
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @param  boolean                        $isVirtual
	 * @return string
	 */
	protected function _getDestinationId(Mage_Sales_Model_Quote_Address $address, $isVirtual=false)
	{
		return ($isVirtual) ? $this->_getVirtualId($address) : '_' . $address->getId();
	}

	/**
	 * add the data extracted from $item to the request and map it to the destination
	 * data extracted from $address.
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param Mage_Sales_Model_Quote_Address       $address
	 * @param boolean                        $isVirtual
	 */
	protected function _addToDestination(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Mage_Sales_Model_Quote_Address $address,
		$isVirtual=false
	)
	{
		$destinationId = $this->_getDestinationId($address, $isVirtual);
		$id = $this->_addShipGroupId($address, $isVirtual);
		if (!isset($this->_shipGroups[$destinationId])) {
			$this->_shipGroups[$destinationId] = array();
		}
		if (!isset($this->_destinations[$destinationId])) {
			$this->_destinations[$destinationId] = $this->_extractDestData($address, $isVirtual);
		}
		$sku = (string) $item->getSku();
		if (array_search($sku, $this->_shipGroups[$destinationId]) === false) {
			$this->_shipGroups[$destinationId][] = $sku;
		}
		$this->_orderItems[$destinationId . '_' . $sku] = $this->_extractItemData($item, $address);
	}

	/**
	 * generate a shipgroup id and map a destination id to it.
	 */
	protected function _addShipGroupId($address, $isVirtual)
	{
		$rateKey = '';
		$addressKey = $this->_getDestinationId($address, $isVirtual);
		if (!($address->getAddressType() === 'billing' || $isVirtual)) {
			$groupedRates = $address->getGroupedAllShippingRates();
			if ($groupedRates) {
				foreach ($groupedRates as $rateKey => $shippingRate) {
					$shippingRate = (is_array($shippingRate)) ? $shippingRate[0] : $shippingRate;
					if ($address->getShippingMethod() === $shippingRate->getCode()) {
						$rateKey = strtoupper($shippingRate->getMethod());
					}
				}
			}
		}
		$id = $rateKey ? "shipGroup{$addressKey}_{$rateKey}" : "shipGroup{$addressKey}";
		if (!isset($this->_shipGroupIds[$addressKey])) {
			$this->_shipGroupIds[$addressKey] = array('group_id' => $id, 'method' => $rateKey, 'method_code' => $address->getShippingMethod());
		}
		return $id;
	}

	protected function _getVirtualId($address)
	{
		$id = '_' . $address->getId() . '_virtual';
		return $id;
	}

	protected function _extractDestData($address, $isVirtual=false)
	{
		$id = $this->_getDestinationId($address, $isVirtual);
		if ($address->getSameAsBilling() && !$this->_isMultiShipping) {
			$address = $this->getBillingAddress();
		}
		$data = array(
			'id' => $id,
			'is_virtual' => $isVirtual,
			'last_name'  => $this->_checkLength($address->getLastname(), 1, 64),
			'first_name' => $this->_checkLength($address->getFirstname(), 1, 64),
		);
		$honorific = $address->getPrefix();
		if ($honorific) {
			$data['honorific'] = $honorific;
		}
		$middleName = $address->getMiddlename();
		if ($middleName) {
			$data['middle_name'] = $middleName;
		}
		// if this is a virtual destination, then only extract the
		// email address
		if ($isVirtual) {
			$data['email_address'] = $this->_checkLength($address->getEmail(), 1, null, false);
		} else {
			$data['city'] = $this->_checkLength($address->getCity(), 1, 35);
			$data['main_division'] = $address->getRegionModel()->getCode();
			$data['country_code'] = $this->_checkLength($address->getCountryId(), 2, 2, false);
			$data['postal_code'] = $address->getPostcode();
			$data['line1'] = $this->_checkLength($address->getStreet1(), 1, 70);
			for ($i = 2; $i <= self::NUM_STREET_LINES; ++$i) {
				$data['line' . $i] = $address->getStreet($i);
			}
		}
		$this->_validateDestData($data, $isVirtual);
		return $data;
	}

	/**
	 * validate the data extracted from an address.
	 * @param  array   $destData   extracted data from an address
	 * @param  boolean $isVirtual  true if the destination is virtual; false otherwise
	 * @return self
	 */
	protected function _validateDestData($destData, $isVirtual)
	{
		if ($isVirtual) {
			$fields = array('email_address');
		} else {
			$fields = array(
				'last_name',
				'first_name',
				'city',
				'line1',
				'country_code',
			);
		}
		foreach ($fields as $field) {
			$value = isset($destData[$field]) ? $destData[$field] : null;
			if (is_null($value)) {
				$message = sprintf('field %s: value [%s] is invalid length', $field, $value);
				throw new Mage_Core_Exception($message);
			}
		}
		return $this;
	}

	protected function _extractItemData($item, $address)
	{
		$data = array(
			'id' => $item->getId(),
			'line_number' => $this->_getLineNumber($item),
			'item_id' => $item->getSku(),
			'item_desc' => $item->getName(),
			'hts_code' => $item->getHtsCode(),
			'quantity' => $item->getQty(),
			'merchandise_amount' => $item->getRowTotal(),
			'merchandise_unit_price' => $this->_getItemOriginalPrice($item),
			'merchandise_tax_class' => $this->_getItemTaxClass($item),
			'shipping_amount' => $address->getShippingAmount(),
			'shipping_tax_class' => $this->_getShippingTaxClass(),
			'AdminOrigin' => $this->_extractAdminData(),
			'ShippingOrigin' => $this->_extractShippingData($item),
		);
		$data = $this->_extractItemDiscountData($item, $address, $data);
		return $data;
	}

	/**
	 * Get the unit price for the item, taking into consideration the
	 * original_custom_price, custom_price, original_price and base_price
	 * @param  Mage_Sales_Model_Quote_item $item The quote item to get the price of.
	 * @return float       The original price of the item.
	 */
	protected function _getItemOriginalPrice($item)
	{
		if ($item->hasOriginalCustomPrice()) {
			return $item->getOriginalCustomPrice();
		} elseif ($item->hasCustomPrice()) {
			return $item->getCustomPrice();
		} elseif ($item->hasOriginalPrice()) {
			return $item->getOriginalPrice();
		} else {
			return $item->getBasePrice();
		}
	}

	/**
	 * get the tax class for the item's product.
	 * NOTE: the taxCode should be set by the ItemMaster feed.
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @return string
	 */
	protected function _getItemTaxClass($item)
	{
		$taxCode = '';
		if ($item->getProduct()->hasTaxCode()) {
			$taxCode = $item->getProduct()->getTaxCode();
		}
		return (string) $this->_checkLength($taxCode, 1, 40);
	}

	/**
	 * return the sku truncated down to 20 characters if too long or
	 * null if the sku doesn't meet minimum size requirements.
	 * @param  array $item
	 * @return string
	 */
	protected function _checkSku($item)
	{
		$newSku = $this->_checkLength($item['item_id'], 1, 20);
		if (is_null($newSku)){
			$this->invalidate();
			$message = sprintf(
				'TaxDutyQuoteRequest: Mage_Sales_Model_Quote_Item_Abstract id:%s has an invalid SKU:%s',
				$item['id'],
				$item['item_id']
			);
			// @codeCoverageIgnoreStart
			Mage::throwException($message);
		}
		// @codeCoverageIgnoreEnd
		if (strlen($newSku) < strlen($item['item_id'])) {
			$message = 'Item sku "' . $item['item_id'] . '" is too long and has been truncated';
			Mage::log('[' . __CLASS__ . '] ' . $message, Zend_Log::WARN);
		}
		return $newSku;
	}

	protected function _getShippingTaxClass()
	{
		return $this->_checkLength(
			Mage::getStoreConfig(
				Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
				$this->getStore()
			),
			1, 40
		);
	}

	protected function _buildTaxDutyRequest()
	{
		try {
			$this->_namespaceUri = $this->_helper->getNamespaceUri($this->getStore());
			$this->_doc->addElement('TaxDutyQuoteRequest', null, $this->_namespaceUri);
			$tdRequest          = $this->_doc->documentElement;
			$billingInformation = $tdRequest->addChild(
				'Currency',
				$this->getQuote()->getQuoteCurrencyCode()
			)
				->addChild('VATInclusivePricing', (int) $this->_helper->getVatInclusivePricingFlag($this->getStore()))
				->addChild(
					'CustomerTaxId',
					$this->_checkLength($this->getBillingAddress()->getTaxId(), 0, 40)
				)
				->createChild('BillingInformation');
			$billingInformation->setAttribute('ref', $this->_billingInfoRef);
			$shipping = $tdRequest->createChild('Shipping');
			$this->_tdRequest = $tdRequest;
			$shipGroups       = $shipping->createChild('ShipGroups');
			$destinations     = $shipping->createChild('Destinations');
			$this->_processAddresses($destinations, $shipGroups);
		} catch (Mage_Core_Exception $e) {
			Mage::log('[' . __CLASS__ . '] TaxDutyQuoteRequest Error: ' . $e->getMessage(), Zend_Log::WARN);
			$this->invalidate();
		}
	}

	/**
	 * generate the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses($destinationsNode, $shipGroupsNode)
	{
		foreach ($this->_destinations as $destination) {
			if ($destination['is_virtual']) {
				$this->_buildEmailNode($destinationsNode, $destination);
			} else {
				$this->_buildMailingAddressNode($destinationsNode, $destination);
			}
		}
		foreach ($this->_shipGroups as $destinationId => $itemList) {
			$shipGroupInfo = $this->_shipGroupIds[$destinationId];
			$shipGroupId   = $shipGroupInfo['group_id'];
			$chargeType    = $shipGroupInfo['method'];
			$shipGroup     = $shipGroupsNode->createChild('ShipGroup');
			$shipGroup->addAttribute('id', $shipGroupId, true)
				->addAttribute('chargeType', strtoupper($chargeType));
			$destinationTarget = $shipGroup->createChild('DestinationTarget');
			$destinationTarget->setAttribute('ref', $destinationId);

			$orderItemsFragment = $this->_doc->createDocumentFragment();
			$orderItems = $orderItemsFragment->appendChild(
				$this->_doc->createElement('Items', null, $this->_namespaceUri)
			);
			foreach($itemList as $orderItemSku) {
				$orderItemId = $destinationId . '_' . $orderItemSku;
				$orderItem = $this->_orderItems[$orderItemId];
				$this->_addOrderItem($orderItem, $orderItems);
			}
			$shipGroup->appendChild($orderItemsFragment);

		}
	}

	/**
	 * Populate $parent with nodes using data extracted from the specified address.
	 */
	protected function _buildAddressNode(TrueAction_Dom_Element $parent, $address)
	{
		// loop through to get all of the street lines.
		for ($i = 1; $i <= self::NUM_STREET_LINES; ++$i) {
			if ($address['line' . $i]) {
				$parent->createChild('Line' . $i, $address['line' . $i]);
			}
		}
		$parent->createChild('City', $address['city']);
		if ($address['main_division']) {
			$parent->createChild('MainDivision', $address['main_division']);
		}
		$parent->createChild('CountryCode', $address['country_code']);
		if ($address['postal_code']) {
			$parent->createChild('PostalCode', $address['postal_code']);
		}
	}

	/**
	 * Populate $parent with the nodes for a person's name extracted from the specified address.
	 */
	protected function _buildPersonName(TrueAction_Dom_Element $parent, $address)
	{
		$honorific  = isset($address['honorific']) ? $address['honorific'] : null;
		$middleName = isset($address['middle_name']) ? $address['middle_name'] : null;
		if ($honorific) {
			$parent->createChild('Honorific', $honorific);
		}
		$parent->createChild('LastName', $address['last_name']);
		if ($middleName) {
			$parent->createChild('MiddleName', $middleName);
		}
		$parent->createChild('FirstName', $address['first_name']);
	}

	/**
	 * build the MailingAddress node
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildMailingAddressNode(
		TrueAction_Dom_Element $parent,
		array $address
	)
	{
		$destinationId = $address['id'];
		$mailingAddress = $parent->createChild('MailingAddress');
		$mailingAddress->setAttribute('id', $destinationId);
		$mailingAddress->setIdAttribute('id', true);
		$personName = $mailingAddress->createChild('PersonName');
		$this->_buildPersonName($personName, $address);
		$addressNode = $mailingAddress->createChild('Address');
		$this->_buildAddressNode($addressNode, $address);
	}

	/**
	 * build an email address node for the destinations node.
	 * @param  TrueAction_Dom_Element         $parent
	 * @param  array $address
	 */
	protected function _buildEmailNode(TrueAction_Dom_Element $parent, array $address)
	{
		$destinationId = $address['id'];
		// do nothing if the email address doesn't meet size requirements.
		$emailStr = $this->_checkLength($address['email_address'], 1, self::EMAIL_MAX_LENGTH);
		if ($emailStr) {
			$email = $parent->createChild('Email')
				->addAttribute('id', $destinationId, true);
			$this->_buildPersonName($email->createChild('Customer'), $address);
			$email->createChild('EmailAddress', $emailStr);
		}
	}

	/**
	 * build a discount node as a child of $parent.
	 * @param  TrueAction_Dom_Element $parent
	 * @param  array                  $discount
	 * @param  boolean                $isMerchandise
	 */
	protected function _buildDiscountNode(TrueAction_Dom_Element $parent, array $discount, $isMerchandise=true)
	{
		$type = $isMerchandise ? 'merchandise' : 'shipping';
		$discountNode = $parent->createChild(
			'Discount',
			null,
			array(
				'id' => $discount["{$type}_discount_code"],
				'calculateDuty' => $discount["{$type}_discount_calc_duty"]
			)
		);
		$discountNode->createChild('Amount', $discount["{$type}_discount_amount"]);
	}

	/**
	 * check $string to see if it conforms to length requirements.
	 * if $truncate is true, truncate the string so that it is never longer than
	 * $maxLength characters.
	 * null is returned if $string does not meet the minimum length requirement
	 * or if $string does not meet the max length requirement and truncate is false.
	 * @param  string  $string
	 * @param  int  $minLength
	 * @param  int  $maxLength
	 * @param  boolean $truncate
	 * @return null|string
	 */
	protected function _checkLength($string, $minLength=null, $maxLength=null, $truncate=true)
	{
		$result = null;
		if (!is_null($string)) {
			$len = strlen($string);
			if (is_null($minLength) || $len >= $minLength) {
				$result = $string;
			}
			if ($result && !is_null($maxLength)) {
				if (($len > $maxLength)) {
					$result = ($truncate) ? substr($string, 0, $maxLength) : null;
				}
			}
		}
		return $result;
	}

	/**
	 * build and append an orderitem node to the parent node.
	 * @param array    $item
	 * @param TrueAction_Dom_Element         $parent
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _addOrderItem(array $item, TrueAction_Dom_Element $parent)
	{
		$sku = $this->_checkSku($item);
		$orderItem = $parent->createChild('OrderItem')
			->addAttribute('lineNumber', $item['line_number'])
			->addChild('ItemId', $sku)
			->addChild('ItemDesc', $this->_checkLength($item['item_desc'], 0, 12))
			->addChild('HTSCode', $this->_checkLength($item['hts_code'], 0, 12));

		$origins = $parent->createChild('Origins');
		$origins->appendChild($this->_buildAdminOriginNode($parent, $item['AdminOrigin']));
		$origins->appendChild($this->_buildShippingOriginNode($parent, $item['ShippingOrigin']));

		$orderItem->appendChild($origins);
		$orderItem->addChild('Quantity', $item['quantity']);

		$unitPriceNode = $orderItem->createChild('Pricing')
			->createChild('Merchandise')
			->addChild('Amount', $item['merchandise_amount'])
			->createChild('UnitPrice', $item['merchandise_unit_price']);

		$taxClass = $this->_checkLength($item['merchandise_tax_class'], 1, 40);
		if ($taxClass) {
			$taxClassNode = $parent->ownerDocument->createElementNs($parent->namespaceURI, 'TaxClass', $taxClass);
			$unitPriceNode->parentNode->insertBefore($taxClassNode, $unitPriceNode);
		}
	}

	/**
	 * get an item's position in the order
	 * @param array $item
	 * @return int
	 */
	protected function _getLineNumber($item)
	{
		return $item->getId();
	}

	/**
     * update the item data in $outData with discount information and return
     * the newly modified array.
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param  Mage_Sales_Model_Quote_Address       $address
	 * @param  array                                $data
	 */
	protected function _extractItemDiscountData(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Mage_Sales_Model_Quote_Address $address,
		array $outData
	)
	{
		$discountCode = $this->_getDiscountCode($address);
		$isDutyCalcNeeded = $this->_isDutyCalcNeeded($item, $address);
		if ($item->getDiscountAmount()) {
			$outData['merchandise_discount_code']      = $discountCode;
			$outData['merchandise_discount_amount']    = $item->getDiscountAmount();
			$outData['merchandise_discount_calc_duty'] = $isDutyCalcNeeded;
		}

		if ($address->getShippingDiscountAmount()){
			$isDutyCalcNeeded = $this->_isDutyCalcNeeded($item, $address);
			$outData['shipping_discount_code']      = $discountCode;
			$outData['shipping_discount_amount']    = $address->getShippingDiscountAmount();
			$outData['shipping_discount_calc_duty'] = $isDutyCalcNeeded;
		}
		return $outData;
	}

	/**
	 * generate the code to identify a discount
	 * @param  Mage_Sales_Model_Quote_Address $item
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getDiscountCode(Mage_Sales_Model_Quote_Address $address)
	{
		return '_' . $address->getCouponCode();
	}

	/**
	 * return false since we don't do any duty informaiton.
	 */
	protected function _isDutyCalcNeeded($item, $address)
	{
		return false;
	}

	/**
	 * extract admin origin data from the Magento store configuration
	 *
	 * @return array, the admin origin address data
	 */
	protected function _extractAdminData()
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ctax/config'));

		return array(
			'Lines' => array_map(function ($n) use ($cfg) {return $cfg->getConfig("admin_origin_line$n"); }, array(1, 2, 3, 4)),
			'City' => $cfg->adminOriginCity,
			'MainDivision' => $cfg->adminOriginMainDivision,
			'CountryCode' => $cfg->adminOriginCountryCode,
			'PostalCode' => $cfg->adminOriginPostalCode,
		);
	}

	/**
	 * extract shipping origin data from quote item originally setup by inventory details request to eb2c
	 *
	 * @param Mage_Sales_Model_Quote_Item $item, the quote item to get inventory detail information from
	 *
	 * @return array, the shipping origin address data
	 */
	protected function _extractShippingData(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		return array(
			'Line1' => (trim($item->getEb2cShipFromAddressLine1()) !== '') ? $item->getEb2cShipFromAddressLine1() : 'Line1',
			'City' => (trim($item->getEb2cShipFromAddressCity()) !== '') ? $item->getEb2cShipFromAddressCity() : 'city',
			'MainDivision' => (trim($item->getEb2cShipFromAddressMainDivision()) !== '') ? $item->getEb2cShipFromAddressMainDivision() : 'State',
			'CountryCode' => (trim($item->getEb2cShipFromAddressCountryCode()) !== '') ? $item->getEb2cShipFromAddressCountryCode() : 'US',
			'PostalCode' => (trim($item->getEb2cShipFromAddressPostalCode()) !== '') ? $item->getEb2cShipFromAddressPostalCode() : 'Zipcode',
		);
	}

	/**
	 * build the AdminOrigin node
	 *
	 * @param TrueAction_Dom_Element $parent, the dom element parent node
	 * @param array $adminOrigin, the admin origin address
	 *
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildAdminOriginNode(TrueAction_Dom_Element $parent, array $adminOrigin)
	{
		return $parent->createChild('AdminOrigin')
			->addChild('Line1', $adminOrigin['Lines'][0])
			->addChild('Line2', $adminOrigin['Lines'][1])
			->addChild('Line3', $adminOrigin['Lines'][2])
			->addChild('Line4', $adminOrigin['Lines'][3])
			->addChild('City', $adminOrigin['City'])
			->addChild('MainDivision', $adminOrigin['MainDivision'])
			->addChild('CountryCode', $adminOrigin['CountryCode'])
			->addChild('PostalCode', $adminOrigin['PostalCode']);
	}

	/**
	 * build the ShippingOrigin node
	 *
	 * @param TrueAction_Dom_Element $parent, the dom element parent node
	 * @param array $shippingOrigin, the shipping origin address
	 *
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildShippingOriginNode(TrueAction_Dom_Element $parent, array $shippingOrigin)
	{
		return $parent->createChild('ShippingOrigin')
			->addChild('Line1', $shippingOrigin['Line1'])
			->addChild('City', $shippingOrigin['City'])
			->addChild('MainDivision', $shippingOrigin['MainDivision'])
			->addChild('CountryCode', $shippingOrigin['CountryCode'])
			->addChild('PostalCode', $shippingOrigin['PostalCode']);
	}

	/**
	 * compare the shippingOrigin to be sent in the request with the shippingOrigin
	 * in the specified quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 */
	public function checkShippingOriginAddresses(Mage_Sales_Model_Quote $quote=null)
	{
		if (!($this->isValid() && $quote && $quote->getId())) {
			// skip it if the request is bad in the first place or if the quote
			// passed in is null.
			return;
		}

		if (is_array($this->_orderItems)) {
			foreach ($this->_orderItems as $key => $value) {
				if($item = $quote->getItemById($value['id'])) {
					$itemData = $this->_extractShippingData($item);
					$shippingOrigin = $value['ShippingOrigin'];
					$this->_hasChanges = (bool) array_diff_assoc($itemData, $shippingOrigin);
				}
			}
		}
	}

	/**
	 * compare the adminOrigin to be sent in the request with the adminOrigin
	 * in the specified quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 */
	public function checkAdminOriginAddresses()
	{
		if (!$this->isValid()) {
			// skip it if the request is bad in the first place or if the quote
			// passed in is null.
			return;
		}

		if (is_array($this->_orderItems)) {
			foreach ($this->_orderItems as $key => $value) {
				$adminData = $this->_extractAdminData();
				$adminOrigin = $value['AdminOrigin'];
				$this->_hasChanges = (bool) array_diff_assoc($adminData, $adminOrigin);
			}
		}
	}
}

<?php
/**
 * generate the xml for an EB2C tax and duty quote request.
 * @author Michael Phang <mphang@ebay.com>
 */
class TrueAction_Eb2cTax_Model_Request extends Varien_Object
{
	const EMAIL_MAX_LENGTH         = 70;
	const NUM_STREET_LINES         = 4;
	protected $_xml                = '';
	protected $_doc                = null;
	protected $_tdRequest          = null;
	protected $_namespaceUri       = '';
	protected $_billingInfoRef     = '';
	protected $_billingEmailRef    = '';
	protected $_storeId            = null;
	protected $_isMultiShipping    = false;
	protected $_emailAddresses     = array();
	protected $_destinations       = array();
	protected $_orderItems         = array();
	protected $_shipGroups         = array();
	protected $_appliedDiscountIds = array();
	protected $_shipGroupIds       = array();
	protected $_addresses          = array();

	/**
	 * true if the request is valid; false otherwise
	 * @var boolean
	 */
	protected $_isValid            = false;
	/**
	 * map skus to a quote item
	 * @var array('string' => Mage_Sales_Model_Quote_Item_Abstract)
	 */
	protected $_skuItemMap = array();

	/**
	 * @see _isValid
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * get the DOMDocument for the request.
	 * @return TrueAction_Dom_Document
	 */
	public function getDocument()
	{
		if (!$this->_doc || !$this->_doc->documentElement) {
			$doc        = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_doc = $doc;
			$doc->preserveWhiteSpace = false;
			if ($this->isValid()) {
				$this->_buildTaxDutyRequest();
			}
		}
		return $this->_doc;
	}

	/**
	 * add the billing destination data to the request
	 * @return void
	 */
	protected function _addBillingDestination($address)
	{
		$this->setBillingAddressTaxId($address->getTaxId());
		$this->_billingInfoRef = $this->_getDestinationId($address);
		try {
			$this->_destinations[$this->_billingInfoRef] = $this->_extractDestData(
				$address
			);
		} catch (Mage_Core_Exception $e) {
			$message = 'Unable to extract the billing address: ' . $e->getMessage();
			throw new Mage_Core_Exception($message);
		}
	}
	/**
	 * Process an item or, if the item has children that are calculated,
	 * the item's children. Processing an item really only consists of
	 * adding the item to a destination grouping based on the address the item
	 * ships to.
	 * @param  Mage_Sales_Model_Quote_Item $item The item to process
	 * @param  Mage_Sales_Model_Quote_Addres $address The address the item ships to
	 * @return self
	 */
	public function _processItem($item, $address)
	{
		if ($item->getHasChildren() && $item->isChildrenCalculated()) {
			foreach ($item->getChildren() as $child) {
				$this->_processItem($child, $address);
			}
		} else {
			$this->_addToDestination($item, $address, $item->getProduct()->isVirtual());
		}
		return $this;
	}

	public function processAddress(Mage_Sales_Model_Quote_Address $address=null)
	{
		try {
			$quote = $address ? $address->getQuote() : null;
			$isQuoteUsable = $this->_isQuoteUsable($quote);
			if (!$isQuoteUsable) {
				$this->_isValid = false;
				return $this;
			}

			$this->_storeId = $quote->getStore()->getId();
			$this->setQuoteCurrencyCode($quote->getQuoteCurrencyCode());

			// track if this is a multishipping quote or not.
			$this->_isMultiShipping = (bool) $quote->getIsMultiShipping();
			// create the billing address destination node(s)
			$billAddress = $quote->getBillingAddress();
			$this->_addBillingDestination($billAddress);

			$items = $this->_getItemsForAddress($address);
			foreach ($items as $item) {
				$this->_processItem($item, $address);
			}
			// Consider the request as being valid if nothing has thrown an exception
			// as any previous validation errors would have thrown an exception.
			$this->_isValid = true;
		}
		catch (Exception $e) {
			$message = sprintf(
				'[ %s ] Error gathering data for the tax request: %s',
				__CLASS__,
				$e->getMessage()
			);
			Mage::log($message, Zend_Log::WARN);
			$this->_isValid = false;
		}
		return $this;
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
			$this->_hasValidBillingAddress($quote->getBillingAddress()) &&
			$quote->getItemsCount();
	}

	/**
	 * return true if the address has enough information to be useful.
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return boolean
	 */
	protected function _hasValidBillingAddress(Mage_Sales_Model_Quote_Address $address)
	{
		return (trim($address->getLastname()) !== '') && (trim($address->getFirstname()) !== '');
	}

	/**
	 * Get a list of all items for $address
	 * As there's not way to combine the address objects non-nominal and visible
	 * item filtering, one of the two needs to be replicated here,
	 * which is currently the visibile items filter.
	 *
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @return array(Mage_Sales_Model_Quote_Item_Abstract)
	 */
	protected function _getItemsForAddress(Mage_Sales_Model_Quote_Address $address)
	{
		$items = array();
		foreach ($address->getAllNonNominalItems() as $item) {
			if (!$item->getParentItemId()) {
				$items[] = $item;
			}
		}
		return $items;
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
		$data = array(
			'id' => $id,
			'is_virtual' => $isVirtual,
			'last_name'  => $this->_checkLength($address->getLastname(), 1, 64),
			'first_name' => $this->_checkLength($address->getFirstname(), 1, 64),
		);
		if ($address->getSameAsBilling() && !$this->_isMultiShipping) {
			$data = array_merge($this->_destinations[$this->_billingInfoRef], $data);
		}
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
			$data['main_division'] = $address->getRegionCode();
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
	 * @throws Mage_Core_Exception If destination is missing any required data.
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

	/**
	 * extract data necessary to build an orderitem node
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param  Mage_Sales_Model_Quote_Address       $address
	 * @return array extracted item data
	 */
	protected function _extractItemData(Mage_Sales_Model_Quote_Item_Abstract $item, Mage_Sales_Model_Quote_Address $address)
	{
		$data = array(
			'id' => $item->getId(),
			'line_number' => count($this->_orderItems),
			'item_id' => $item->getSku(),
			'item_desc' => $item->getName(),
			'hts_code' => $item->getHtsCode(),
			'quantity' => $item->getQty(),
			// @todo this needs to be the right value when the item is a child
			'merchandise_amount' => Mage::app()->getStore()->roundPrice($item->getBaseRowTotal()),
			// @todo this needs to be the right value when the item is a child
			'merchandise_unit_price' => Mage::app()->getStore()->roundPrice($this->_getItemOriginalPrice($item)),
			'merchandise_tax_class' => $this->_getItemTaxClass($item),
			'shipping_amount' => Mage::app()->getStore()->roundPrice($address->getBaseShippingAmount()),
			'shipping_tax_class' => $this->_getShippingTaxClass(),
		);
		return array_merge(
			$data,
			$this->_extractShippingData($item),
			$this->_extractItemDiscountData($item, $address)
		);
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
				$this->_storeId
			),
			1, 40
		);
	}

	protected function _buildTaxDutyRequest()
	{
		$helper = Mage::helper('eb2ctax');
		try {
			$this->_namespaceUri = $helper->getNamespaceUri();
			$this->_doc->addElement('TaxDutyQuoteRequest', null, $this->_namespaceUri);
			$tdRequest          = $this->_doc->documentElement;
			$billingInformation = $tdRequest
				->addChild('Currency', $this->getQuoteCurrencyCode())
				->addChild('VATInclusivePricing', (int) $helper->getVatInclusivePricingFlag($this->_storeId))
				->addChild('CustomerTaxId', $this->_checkLength($this->getBillingAddressTaxId(), 0, 40))
				->createChild('BillingInformation');
			$billingInformation->setAttribute('ref', $this->_billingInfoRef);
			$shipping = $tdRequest->createChild('Shipping');
			$this->_tdRequest = $tdRequest;
			$shipGroups       = $shipping->createChild('ShipGroups');
			$destinations     = $shipping->createChild('Destinations');
			$this->_processAddresses($destinations, $shipGroups);
		} catch (Mage_Core_Exception $e) {
			Mage::log('[' . __CLASS__ . '] TaxDutyQuoteRequest Error: ' . $e->getMessage(), Zend_Log::WARN);
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
				$this->_buildOrderItem($orderItem, $orderItems);
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
	protected function _buildMailingAddressNode(TrueAction_Dom_Element $parent, array $address)
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
		$discountNode->createChild('Amount', Mage::app()->getStore()->roundPrice($discount["{$type}_discount_amount"]));
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
	protected function _buildOrderItem(array $item, TrueAction_Dom_Element $parent)
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
			->addChild('Amount', Mage::app()->getStore()->roundPrice($item['merchandise_amount'] - $item['merchandise_discount_amount']))
			->createChild('UnitPrice', Mage::app()->getStore()->roundPrice($item['merchandise_unit_price']));

		$taxClass = $this->_checkLength($item['merchandise_tax_class'], 1, 40);
		if ($taxClass) {
			$taxClassNode = $parent->ownerDocument->createElementNs($parent->namespaceURI, 'TaxClass', $taxClass);
			$unitPriceNode->parentNode->insertBefore($taxClassNode, $unitPriceNode);
		}
	}

	/**
	 * update the item data in $outData with discount information and return
	 * the newly modified array.
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param  Mage_Sales_Model_Quote_Address       $address
	 * @return  array
	 */
	protected function _extractItemDiscountData(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Mage_Sales_Model_Quote_Address $address
	)
	{
		$discountCode = $this->_getDiscountCode($address);
		// since we're sending prices with discounts already calculated in,
		// there's no need to set the flag to anything other than "false".
		$isDutyCalcNeeded = false;
		return array(
			'merchandise_discount_code'      => $discountCode,
			'merchandise_discount_amount'    => $item->getBaseDiscountAmount(),
			'merchandise_discount_calc_duty' => $isDutyCalcNeeded,
			'shipping_discount_code'         => $discountCode,
			'shipping_discount_amount'       => $address->getBaseShippingDiscountAmount(),
			'shipping_discount_calc_duty'    => $isDutyCalcNeeded,
		);
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
	 * Extract and collect shipping origin data. Includes the 'AdminOrigin' and
	 * 'ShippingOrigin' for the item.
	 * @param Mage_Sales_Model_Quote_Item $item, the quote item to get inventory detail information from
	 * @return array, the shipping origin address data
	 */
	protected function _extractShippingData(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$item = $this->_getQuoteItem($item);
		$data = array(
			'AdminOrigin' => $this->_extractAdminData(),
			'ShippingOrigin' => array(
				'Lines'        => array_map(function ($n) use ($item) { $m = "getEb2cShipFromAddressLine$n"; return trim($item->$m()); }, array(1, 2, 3, 4)),
				'City'         => trim($item->getEb2cShipFromAddressCity()),
				'MainDivision' => trim($item->getEb2cShipFromAddressMainDivision()),
				'CountryCode'  => trim($item->getEb2cShipFromAddressCountryCode()),
				'PostalCode'   => trim($item->getEb2cShipFromAddressPostalCode()),
			)
		);
		// Virtual items are considered to "ship" from the admin origin.
		// Also, use admin origin for shipping origin as a "close enough" shipping
		// origin for tax estimations when the actual shipping origin is missing or incomplete.
		if ($item->getProduct()->isVirtual() || !$this->_validateShipFromData($data['ShippingOrigin'])) {
			$data['ShippingOrigin'] = $data['AdminOrigin'];
		}
		return $data;
	}

	/**
	 * Validate the ship from address - ensure the address has: at least one
	 * street line, a city and country code. Return true if address meets these
	 * criteria, false otherwise.
	 * @param  array   $data address data
	 * @return boolean
	 */
	protected function _validateShipFromData($data)
	{
		if ($data['Lines'][0] === '') {
			return false;
		}
		foreach (array('City', 'CountryCode') as $key) {
			if ($data[$key] === '') {
				return false;
			}
		}
		return true;
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
		$adminOriginEl = $parent->createChild('AdminOrigin')
			->addChild('Line1', $adminOrigin['Lines'][0]);
		if (trim($adminOrigin['Lines'][1]) !== '') {
			$adminOriginEl->addChild('Line2', $adminOrigin['Lines'][1]);
		}
		if (trim($adminOrigin['Lines'][2]) !== '') {
			$adminOriginEl->addChild('Line3', $adminOrigin['Lines'][2]);
		}
		if (trim($adminOrigin['Lines'][3]) !== '') {
			$adminOriginEl->addChild('Line4', $adminOrigin['Lines'][3]);
		}
		$adminOriginEl->addChild('City', $adminOrigin['City'])
			->addChild('MainDivision', $adminOrigin['MainDivision'])
			->addChild('CountryCode', $adminOrigin['CountryCode'])
			->addChild('PostalCode', $adminOrigin['PostalCode']);

		return $adminOriginEl;
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
			->addChild('Line1', $shippingOrigin['Lines'][0])
			->addChild('City', $shippingOrigin['City'])
			->addChild('MainDivision', $shippingOrigin['MainDivision'])
			->addChild('CountryCode', $shippingOrigin['CountryCode'])
			->addChild('PostalCode', $shippingOrigin['PostalCode']);
	}

	/**
	 * if given a quote_item, the quote_item is returned.
	 * if given an address_item, the associated quote_item is returned.
	 * @param  Mage_Sales_Model_Quote_Item_Abstract $item either a quote_item or an address_item
	 * @return Mage_Sales_Model_Quote_Item
	 */
	protected function _getQuoteItem(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
			$item = $item->getQuoteItem();
		}
		return $item;
	}
}

<?php
class TrueAction_Eb2cInventory_Model_Quantity extends TrueAction_Eb2cInventory_Model_Abstract
{
	/**
	 * Get the stock value for a product added to the cart from eb2c.
	 * @param int $qty the customer requested quantity
	 * @param int $itemId quote itemId in the shopping cart
	 * @param string $sku product sku for the added item
	 * @return int the eb2c available stock for the item; null if the item should be added unimpeded.
	 */
	public function requestQuantity($qty, $itemId, $sku)
	{
		$availableQty = null; // this is to simulate out of stock response from eb2c
		if ($qty > 0) {
			$responseMessage = '';
			// build request
			$requestDoc = $this->buildQuantityRequestMessage(array(array('id' => $itemId, 'sku' => $sku)));
			Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);
			try {
				$api = Mage::getModel('eb2ccore/api');
				// make request to eb2c for quantity
				$responseMessage = $api
					->setUri(Mage::helper('eb2cinventory')->getOperationUri('check_quantity'))
					->setXsd(Mage::helper('eb2cinventory')->getConfigModel()->xsdFileQuantity)
					->request($requestDoc);
				if ($responseMessage === '') {
					// if we got an empty response back something went wrong
					// so check the http status to see if we should block or not
					if (is_null($api->getStatus())) {
						throw new TrueAction_Eb2cInventory_Exception_Cart(
							'inventory quantity request received an empty response with no status code.'
						);
					} elseif ($this->_isBlockingStatus($api->getStatus())) {
						throw new TrueAction_Eb2cInventory_Exception_Cart_Interrupt(
							'disruptive status [' . $api->getStatus() . '] returned from inventory quantity request.'
						);
					} else {
						throw new TrueAction_Eb2cInventory_Exception_Cart(
							'status [' . $api->getStatus() . '] returned from inventory quantity request.'
						);
					}
				} else {
					// get available stock from response XML
					$availableStock = $this->getAvailableStockFromResponse($responseMessage);
					$availableQty = isset($availableStock[$sku]) ? $availableStock[$sku] : 0;
				}
			} catch(Zend_Http_Client_Adapter_Exception $e) {
				throw new TrueAction_Eb2cInventory_Exception_Cart(
					'inventory quantity request client error: ' . $e->getMessage()
				);
			} catch(Zend_Http_Client_Exception $e) {
				throw new TrueAction_Eb2cInventory_Exception_Cart_Interrupt(
					'inventory quantity request client error: ' . $e->getMessage()
				);
			}
		}
		return $availableQty;
	}

	/**
	 * determine if the status should prevent adding an item to the cart.
	 * @param  int  $status    http status code from the api response.
	 * @return boolean         true if the item should not be added to the cart; false otherwise.
	 */
	protected function _isBlockingStatus($status)
	{
		$status = (int) $status;
		return $status && ($status >= 400 && $status < 408) ||
			($status >= 409 && $status < 500) ||
			$status === 505;
	}

	/**
	 * Build quantity request.
	 * @param array $items The array containing quote item id and product sku
	 * @return DOMDocument The XML document, to be sent as request to eb2c
	 */
	public function buildQuantityRequestMessage(array $items)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		foreach ($items as $item) {
			if (isset($item['id']) && isset($item['sku'])) {
				$quantityRequestMessage->createChild('QuantityRequest', null, array('lineId' => 'item' . $item['id'], 'itemId' => $item['sku']));
			}
		}
		return $domDocument;
	}

	/**
	 * Parse through XML response to get eb2c available stock for an item.
	 * @param string $quantityResponseMessage the XML response from eb2c
	 * @return array The available stock from eb2c for each item, keyed by itemId
	 */
	public function getAvailableStockFromResponse($quantityResponseMessage)
	{
		$availableStock = array();
		if (trim($quantityResponseMessage) !== '') {
			$coreHlpr = Mage::helper('eb2ccore');
			$doc = $coreHlpr ->getNewDomDocument();
			// load response string xml from eb2c
			$doc->loadXML($quantityResponseMessage);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('a', Mage::helper('eb2cinventory')->getXmlNs());
			$quantities = $xpath->query('//a:QuantityResponse');
			foreach ($quantities as $quantity) {
				$availableStock[$quantity->getAttribute('itemId')] = (int) $coreHlpr->extractNodeVal($xpath->query('a:Quantity', $quantity));
			}
		}
		return $availableStock;
	}
}

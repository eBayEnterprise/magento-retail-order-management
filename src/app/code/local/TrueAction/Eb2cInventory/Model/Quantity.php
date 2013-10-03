<?php
class TrueAction_Eb2cInventory_Model_Quantity extends TrueAction_Eb2cInventory_Model_Abstract
{
	/**
	 * Get the stock value for a product added to the cart from eb2c.
	 * @param int $qty the customer requested quantity
	 * @param int $itemId quote itemId in the shopping cart
	 * @param string $sku product sku for the added item
	 * @return int the eb2c available stock for the item.
	 */
	public function requestQuantity($qty, $itemId, $sku)
	{
		$isReserved = 0; // this is to simulate out of stock response from eb2c
		if ($qty > 0) {
			try {
				// build request
				$quantityRequestMessage = $this->buildQuantityRequestMessage(array(array('id' => $itemId, 'sku' => $sku)));

				// make request to eb2c for quantity
				$quantityResponseMessage = Mage::getModel('eb2ccore/api')
					->setUri(Mage::helper('eb2cinventory')->getOperationUri('check_quantity'))
					->setXsd(Mage::helper('eb2cinventory')->getConfigModel()->xsdFileQuantity)
					->request($quantityRequestMessage);

				// get available stock from response XML
				$availableStock = $this->getAvailableStockFromResponse($quantityResponseMessage);
				$isReserved = isset($availableStock[$sku]) ? $availableStock[$sku] : 0;
			} catch(Exception $e) {
				Mage::logException($e);
			}
		}
		return $isReserved;
	}

	/**
	 * Build quantity request.
	 * @param array $items The array containing quote item id and product sku
	 * @return DOMDocument The XML document, to be sent as request to eb2c
	 */
	public function buildQuantityRequestMessage($items)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		if ($items) {
			foreach ($items as $item) {
				if (isset($item['id']) && isset($item['sku'])) {
					$quantityRequestMessage->createChild(
						'QuantityRequest',
						null,
						array('lineId' => $item['id'], 'itemId' => $item['sku'])
					);
				}
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
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			// load response string XML from eb2c
			$doc->loadXML($quantityResponseMessage);
			$quantityResponse = $doc->getElementsByTagName('QuantityResponse');
			foreach($quantityResponse as $response) {
				foreach ($response->childNodes as $node) {
					if ($node->nodeName === 'Quantity') {
						$availableStock[$response->getAttribute('itemId')] = (int) $node->nodeValue;
					}
				}
			}
		}
		return $availableStock;
	}
}

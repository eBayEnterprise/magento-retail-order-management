<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Quantity extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->setHelper(Mage::helper('eb2cinventory'));
	}

	/**
	 * Get the stock value for a product added to the cart from eb2c.
	 *
	 * @param int $qty the customer requested quantity
	 * @param int $itemId quote itemId in the shopping cart
	 * @param string $sku product sku for the added item
	 *
	 * @return int the eb2c available stock for the item.
	 */
	public function requestQuantity($qty=0, $itemId, $sku)
	{
		$isReserved = 0; // this is to simulate out of stock response from eb2c
		if ($qty > 0) {
			try{
				// build request
				$quantityRequestMessage = $this->buildQuantityRequestMessage(array(array('id' => $itemId, 'sku' => $sku)));

				// make request to eb2c for quantity
				$quantityResponseMessage = $this->getHelper()->getApiModel()
					->setUri($this->getHelper()->getOperationUri('check_quantity'))
					->request($quantityRequestMessage);

				// get available stock from response XML
				$isReserved = $this->getAvailableStockFromResponse($quantityResponseMessage);
			}catch(Exception $e){
				Mage::logException($e);
			}
		}
		return $isReserved;
	}

	/**
	 * Build quantity request.
	 *
	 * @param array $items The array containing quote item id and product sku
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildQuantityRequestMessage($items)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, $this->getHelper()->getXmlNs())->firstChild;
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
	 * parse through XML response to get eb2c available stock for an item.
	 *
	 * @param string $quantityResponseMessage the XML response from eb2c
	 *
	 * @return int The available stock from eb2c.
	 */
	public function getAvailableStockFromResponse($quantityResponseMessage)
	{
		$availableStock = 0;
		if (trim($quantityResponseMessage) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();

			// load response string XML from eb2c
			$doc->loadXML($quantityResponseMessage);
			$i = 0;
			$quantityResponse = $doc->getElementsByTagName('QuantityResponse');
			foreach($quantityResponse as $response) {
				$availableStock = (int) $quantityResponse->item($i)->nodeValue;
				$i++;
			}
		}
		return $availableStock;
	}
}

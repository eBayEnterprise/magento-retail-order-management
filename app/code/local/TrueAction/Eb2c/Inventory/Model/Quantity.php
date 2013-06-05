<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Quantity extends Mage_Core_Model_Abstract
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2c_inventory');
		}
		return $this->_helper;
	}

	/**
	 * Get the stock value for a product added to the cart from eb2c.
	 *
	 * @param int $qty the customer requested quantity
	 * @param int $itemId quote itemId in the shopping cart
	 * @param string $sku product sku for the added item
	 *
	 * @return int $isReserved, the eb2c available stock for the item.
	 */
	public function requestQuantity($qty=0, $itemId, $sku)
	{
		$isReserved = 0; // this is to simulate out of stock reponse from eb2c
		if ($qty > 0) {
			// build request
			$quantityRequestMessage = $this->buildQuantityRequestMessage(array('id' => $itemId, 'sku' => $sku));

			// make request to eb2c for quantity
			$quantityResponseMessage = $this->_getHelper()->getCoreHelper()->apiCall(
				$quantityRequestMessage,
				$this->_getHelper()->getQuantityUri()
			);

			// get available stock from reponse xml
			$isReserved = $this->getAvailableStockFromResponse($quantityResponseMessage);
		}
		return $isReserved;
	}

	/**
	 * Build quantity request.
	 *
	 * @param array $items The array containing quote item id and product sku
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildQuantityRequestMessage($items)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		if ($items) {
			foreach ($items as $item) {
				try{
					$quantityRequestMessage->createChild(
						'QuantityRequest',
						null,
						array('lineId' => $item['id'], 'itemId' => $item['sku'])
					);
				}catch(Exception $e){
					Mage::logException($e);
				}
			}
		}
		return $domDocument;
	}

	/**
	 * parse through xml reponse to get eb2c available stock for an item.
	 *
	 * @param string $quantityResponseMessage the xml reponse from eb2c
	 *
	 * @return int $availableStock The available stock from eb2c.
	 */
	public function getAvailableStockFromResponse($quantityResponseMessage)
	{
		$availableStock = 0;
		if (trim($quantityResponseMessage) !== '') {
			if($response = simplexml_load_string($quantityResponseMessage)){
				$availableStock = (int) $response->QuantityResponseMessage->QuantityResponse->Quantity;
			}
		}
		return $availableStock;
	}
}

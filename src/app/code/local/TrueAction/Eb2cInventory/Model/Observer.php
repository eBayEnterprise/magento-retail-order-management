<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Observer
{
	/**
	 * Retrieve shopping cart model object
	 *
	 * @return Mage_Checkout_Model_Cart
	 */
	protected function _getCart()
	{
		return Mage::getSingleton('checkout/cart');
	}

	/**
	 * Check e2bc quantity, triggering sales_quote_item_qty_set_after event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function checkEb2cInventoryQuantity($observer)
	{
		$quoteItem = $observer->getEvent()->getItem();
		$itemId = (int) $quoteItem->getId();

		$requestedQty = $quoteItem->getQty();
		$productId = $quoteItem->getProductId();
		$productSku = $quoteItem->getSku();

		// get quote from quote item
		$quote = $quoteItem->getQuote();

		// check allocation and rollback on the cart quote item adding/editing event
		if (Mage::getModel('eb2cinventory/allocation')->hasAllocation($quote)) {
			// this cart quote has allocation data, therefore, rollback eb2c inventory allocation
			Mage::getModel('eb2cinventory/allocation')->rollbackAllocation($quote);
		}

		if ($productId) {
			// we only do quantity check for product with manage stock only
			if (Mage::getModel('eb2cinventory/allocation')->filterInventoriedItems($quoteItem)) {
				// We have a valid product, let's check Eb2c Quantity
				$availableStock = Mage::getModel('eb2cinventory/quantity')->requestQuantity($requestedQty, $itemId, $productSku);
				if ($availableStock < $requestedQty && $availableStock > 0) {
					// Inventory Quantity is less in eb2c than what user requested from magento front-end
					// then, remove item from cart, and then alert customers of the available stock number of this inventory
					// set cart item to eb2c available qty
					$quoteItem->setQty($availableStock);

					// re-calculate totals
					$quote->collectTotals();

					// save the quote
					$quote->save();

					$this->_getCart()->getCheckoutSession()->addNotice(
						'Sorry for the inconvenience, however, the requested quantity ' .
						$requestedQty . ' is greater than what we currently have in stock ' .
						$availableStock . '.'
					);

				} elseif ($availableStock <= 0) {
					// Inventory Quantity is out of stock in eb2c
					// then, remove item from cart, and then alert customer the inventory is out of stock.
					$quoteItem->getQuote()->deleteItem($quoteItem);
					$this->_getCart()->getCheckoutSession()->addNotice(
						'Sorry for the inconvenience, however, this product is out of stock.'
					);
					// throwing an error to prevent the successful add to cart message
					Mage::throwException('Cannot add the item to shopping cart.');
					// @codeCoverageIgnoreStart
				}
				// @codeCoverageIgnoreEnd
			}
		}
	}

	/**
	 * Rollback allocation if cart has reservation data, triggering sales_quote_remove_item event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function rollbackOnRemoveItemInReservedCart($observer)
	{
		$quoteItem = $observer->getEvent()->getQuoteItem();

		// get quote from quote item
		$quote = $quoteItem->getQuote();

		if (Mage::getModel('eb2cinventory/allocation')->hasAllocation($quote)) {
			// this cart quote has allocation data, therefore, rollback eb2c inventory allocation
			Mage::getModel('eb2cinventory/allocation')->rollbackAllocation($quote);
		}
	}

	/**
	 * Check eb2c inventoryDetails, triggering checkout_controller_onepage_save_shipping_method event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function processInventoryDetails($observer)
	{
		// get the quote from the event observer
		$quote = $observer->getEvent()->getQuote();

		// generate request and send request to eb2c inventory details
		$inventoryDetailsResponseMessage = Mage::getModel('eb2cinventory/details')->getInventoryDetails($quote);
		if ($inventoryDetailsResponseMessage) {
			// parse inventory detail response
			$inventoryData = Mage::getModel('eb2cinventory/details')->parseResponse($inventoryDetailsResponseMessage);

			// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
			Mage::getModel('eb2cinventory/details')->processInventoryDetails($quote, $inventoryData);
		}
	}

	/**
	 * Processing e2bc allocation, triggering eb2c_allocation_onepage_save_order_action_before event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function processEb2cAllocation($observer)
	{
		// get the quote from the event observer
		$quote = $observer->getEvent()->getQuote();

		// get the event response object
		$response = $observer->getEvent()->getResponse();

		// only allow allocation only when, there's no previous allocation or the previous allocation expired
		if (!Mage::getModel('eb2cinventory/allocation')->hasAllocation($quote) || Mage::getModel('eb2cinventory/allocation')->isExpired($quote)) {
			// flag for failure or success allocation
			$isAllocated = true;

			// generate request and send request to eb2c allocation
			$allocationResponseMessage = Mage::getModel('eb2cinventory/allocation')->allocateQuoteItems($quote);
			if ($allocationResponseMessage) {
				// parse allocation response
				$allocationData = Mage::getModel('eb2cinventory/allocation')->parseResponse($allocationResponseMessage);

				// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
				$allocatedErr = Mage::getModel('eb2cinventory/allocation')->processAllocation($quote, $allocationData);

				// Got an allocation failure
				if (!empty($allocatedErr)) {
					$isAllocated = false;
					foreach ($allocatedErr as $error) {
						Mage::getSingleton('checkout/session')->addError($error);
					}
				}
			}

			if (!$isAllocated) {
				throw new TrueAction_Eb2cInventory_Model_Allocation_Exception('Inventory allocation Error.');
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
		}

		// if we get to this point therefore, allocation was successful, then let dispatch an event for stored value gift card processing
		Mage::dispatchEvent('eb2c_event_dispatch_after_inventory_allocation', array('quote' => $quote));
	}
}

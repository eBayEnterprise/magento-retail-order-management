<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Overrides_Model_Cart extends Mage_Checkout_Model_Cart
{
	const TRUEACTION_EB2CINVENTORY_PRODUCT_NOT_EXIST = 'Trueaction_Eb2cinventory_Product_Not_Exist';
	const TRUEACTION_EB2CINVENTORY_QUOTE_ITEM_NOT_EXIST = 'Trueaction_Eb2cinventory_Quote_Item_Not_Exist';

	/**
	 * Overriding Add product to shopping cart (quote)
	 *
	 * @param   int|Mage_Catalog_Model_Product $productInfo
	 * @param   mixed $requestInfo
	 * @return  Mage_Checkout_Model_Cart
	 */
	public function addProduct($productInfo, $requestInfo=null)
	{
		$product = $this->_getProduct($productInfo);
		$request = $this->_getProductRequest($requestInfo);

		$productId = $product->getId();

		// Removing Magento built-in inventory check, to prevent clashing with eb2c quantity check event.
		if ((int) $productId > 0) {
			try {
				$result = $this->getQuote()->addProduct($product, $request);
			} catch (Mage_Core_Exception $e) {
				$this->getCheckoutSession()->setUseNotice(false);
				$result = $e->getMessage();
			}
			/**
			 * String we can get if prepare process has error
			 */

			if (is_string($result)) {
				$redirectUrl = ($product->hasOptionsValidationFail())
					? $product->getUrlModel()->getUrl(
						$product,
						array('_query' => array('startcustomization' => 1))
					)
					: $product->getProductUrl();
				$this->getCheckoutSession()->setRedirectUrl($redirectUrl);
				if ($this->getCheckoutSession()->getUseNotice() === null || trim($this->getCheckoutSession()->getUseNotice()) === '') {
					$this->getCheckoutSession()->setUseNotice(true);
				}
				Mage::throwException($result);
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
		} else {
			Mage::throwException(
				Mage::helper('checkout')->__(self::TRUEACTION_EB2CINVENTORY_PRODUCT_NOT_EXIST)
			);
		}

		Mage::dispatchEvent('checkout_cart_product_add_after', array('quote_item' => $result, 'product' => $product));
		$this->getCheckoutSession()->setLastAddedProductId($productId);
		return $this;
	}

	/**
	 * Overriding Update item in shopping cart (quote)
	 * $requestInfo - either qty (int) or buyRequest in form of array or Varien_Object
	 * $updatingParams - information on how to perform update, passed to Quote->updateItem() method
	 *
	 * @param int $itemId
	 * @param int|array|Varien_Object $requestInfo
	 * @param null|array|Varien_Object $updatingParams
	 * @return Mage_Sales_Model_Quote_Item|string
	 *
	 * @see Mage_Sales_Model_Quote::updateItem()
	 */
	public function updateItem($itemId, $requestInfo=null, $updatingParams=null)
	{
		try {
			$item = $this->getQuote()->getItemById($itemId);
			if (!$item) {
				Mage::throwException(
					Mage::helper('checkout')->__(self::TRUEACTION_EB2CINVENTORY_QUOTE_ITEM_NOT_EXIST)
				);
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
			$productId = $item->getProduct()->getId();
			$product = $this->_getProduct($productId);
			$request = $this->_getProductRequest($requestInfo);

			// removing Magento built-in inventory check, to prevent clashing with eb2c quantity check event.
			$result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
		} catch (Mage_Core_Exception $e) {
			$this->getCheckoutSession()->setUseNotice(false);
			$result = $e->getMessage();
		}

		/**
		 * We can get string if updating process had some errors
		 */
		if (is_string($result)) {
			if ($this->getCheckoutSession()->getUseNotice() === null || trim($this->getCheckoutSession()->getUseNotice()) === '') {
				$this->getCheckoutSession()->setUseNotice(true);
			}
			Mage::throwException($result);
			// @codeCoverageIgnoreStart
		}
		// @codeCoverageIgnoreEnd
		Mage::dispatchEvent('checkout_cart_product_update_after', array(
			'quote_item' => $result,
			'product' => $product
		));
		$this->getCheckoutSession()->setLastAddedProductId($productId);
		return $result;
	}
}

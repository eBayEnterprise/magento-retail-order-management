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


class EbayEnterprise_Eb2cInventory_Test_Helper_QuoteTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function testRollbackAllocation()
	{
		$allocation = $this->getModelMockBuilder('eb2cinventory/allocation')
			->disableOriginalConstructor()
			->setMethods(array('hasAllocation', 'rollbackAllocation'))
			->getMock();
		$this->replaceByMock('model', 'eb2cinventory/allocation', $allocation);
		$quote = $this->getModelMock('sales/quote');

		$allocation
			->expects($this->exactly(2))
			->method('hasAllocation')
			->with($this->identicalTo($quote))
			->will($this->onConsecutiveCalls(array(true, false)));
		$allocation
			->expects($this->once())
			->method('rollbackAllocation')
			->with($this->identicalTo($quote))
			->will($this->returnValue('<AllocationRollbackResponse />'));

		$helper = Mage::helper('eb2cinventory/quote');
		// first invocation scripted to have an allocation, should trigger the rollback request
		$this->assertSame(
			$helper,
			$helper->rollbackAllocation($quote)
		);
		// second invocation scripted to no thave an allocation, rollback shouldn't be triggered again
		$this->assertSame(
			$helper,
			$helper->rollbackAllocation($quote)
		);
	}
	public function testGetNewDomXPath()
	{
		$helper = $this->getHelperMock('eb2cinventory/quote', null);
		$method = $this->_reflectMethod($helper, '_getNewDomXPath');
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->assertInstanceOf('DOMXPath', $method->invoke($helper, $doc));
	}
	/**
	 * Test creating a DOMXPath object for use in querying a response message.
	 * To do so, the method should create a EbayEnterprise_Dom_Document with the
	 * given response message loaded. This DOM document should then be used to
	 * create a DOMXpath object, via the _getNewDomXpath method, which should
	 * then have the namespace used by inventory responses registered.
	 */
	public function testXPathForMessage()
	{
		$responseMessage = '<MockDetailsResponse/>';
		$xmlNs = 'http://test.example.com/xml.ns';
		$doc = $this->getMock('EbayEnterprise_Dom_Document', array('loadXML'));
		$xpath = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(array('registerNamespace'))
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomDocument'));
		$invHelper = $this->getHelperMock('eb2cinventory/data', array('getXmlNs'));
		$quoteHelper = $this->getHelperMock('eb2cinventory/quote', array('_getNewDomXPath'));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		$this->replaceByMock('helper', 'eb2cinventory', $invHelper);

		$coreHelper
			->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($doc));
		$doc
			->expects($this->once())
			->method('loadXML')
			->with($this->identicalTo($responseMessage))
			->will($this->returnValue(true));
		$quoteHelper
			->expects($this->once())
			->method('_getNewDomXPath')
			->with($this->identicalTo($doc))
			->will($this->returnValue($xpath));
		$invHelper
			->expects($this->once())
			->method('getXmlNs')
			->will($this->returnValue($xmlNs));
		$xpath
			->expects($this->once())
			->method('registerNamespace')
			->with($this->identicalTo('a'), $this->identicalTo($xmlNs))
			->will($this->returnValue(true));

		$this->assertSame($xpath, $quoteHelper->getXPathForMessage($responseMessage));
	}

	/**
	 * @see self::testAddNotice test. This test will be testing the scenario where the current store is admin
	 */
	public function testAddNoticeAdminStore()
	{
		$message = 'message';
		$code = 1;
		$errorType = EbayEnterprise_Eb2cInventory_Helper_Quote::ERROR_TYPE;
		$errorOrigin = EbayEnterprise_Eb2cInventory_Helper_Quote::ERROR_ORIGIN;

		$isAdmin = true;
		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('isAdmin'))
			->getMock();
		$storeMock->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue($isAdmin));

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getCurrentStore'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getCurrentStore')
			->will($this->returnValue($storeMock));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$quote = $this->getModelMock('sales/quote', array('addErrorInfo'));
		$session = $this->getModelMockBuilder('adminhtml/session_quote')
			->disableOriginalConstructor()
			->setMethods(array('addNotice'))
			->getMock();
		$this->replaceByMock('singleton', 'adminhtml/session_quote', $session);

		$quote
			->expects($this->once())
			->method('addErrorInfo')
			->with(
				$this->identicalTo($errorType),
				$this->identicalTo($errorOrigin),
				$this->identicalTo($code),
				$this->identicalTo($message)
			)
			->will($this->returnSelf());
		$session
			->expects($this->once())
			->method('addNotice')
			->with($this->identicalTo($message))
			->will($this->returnSelf());
		$quoteHelper = Mage::helper('eb2cinventory/quote');
		$this->assertSame($quoteHelper, $quoteHelper->addCartNotice($quote, $message, $code));
	}
	/**
	 * Test removing an item from a quote. Method should delete the item from the quote
	 * and add a user notice. Notice may contain the item name and sku so both should be
	 * passed to the method adding the notice to the session.
	 */
	public function testRemoveItemFromQuote()
	{
		$oosCode = EbayEnterprise_Eb2cInventory_Helper_Quote::CODE_OOS_ITEM;
		$message = EbayEnterprise_Eb2cInventory_Helper_Quote::QUANTITY_OUT_OF_STOCK_MESSAGE;
		$translated = 'Was ist los?';
		$itemName = 'Item Name';
		$itemSku = 'sku-item-123';

		$quote = $this->getModelMock('sales/quote', array('deleteItem', 'addErrorInfo'));
		$item = $this->getModelMock('sales/quote_item', array('getName', 'getSku'));
		$quoteHelper = $this->getHelperMock('eb2cinventory/quote', array('_getCartMessage', 'addCartNotice'));
		$helper = $this->getHelperMock('eb2cinventory/data', array('__'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);

		$item
			->expects($this->any())
			->method('getName')
			->will($this->returnValue($itemName));
		$item
			->expects($this->any())
			->method('getSku')
			->will($this->returnValue($itemSku));
		$helper
			->expects($this->once())
			->method('__')
			->with($this->identicalTo($message), $this->identicalTo($itemName), $this->identicalTo($itemSku))
			->will($this->returnValue($translated));
		$quoteHelper
			->expects($this->once())
			->method('addCartNotice')
			->with($this->identicalTo($quote), $this->identicalTo($translated), $this->identicalTo($oosCode))
			->will($this->returnSelf());
		$quote
			->expects($this->once())
			->method('deleteItem')
			->with($this->identicalTo($item))
			->will($this->returnSelf());
		$this->assertSame($quoteHelper, $quoteHelper->removeItemFromQuote($quote, $item));
	}
	public function testUpdateQuoteItemQuantity()
	{
		$code = EbayEnterprise_Eb2cInventory_Helper_Quote::CODE_LIMITED_STOCK_ITEM;
		$message = EbayEnterprise_Eb2cInventory_Helper_Quote::QUANTITY_REQUEST_GREATER_MESSAGE;
		$translated = 'Ach mein Gott!!';
		$itemName = 'Item Name';
		$itemSku = 'sku-item-123';
		$itemQty = 4;
		$availQty = 1;

		$quote = $this->getModelMock('sales/quote');
		$item = $this->getModelMock('sales/quote_item', array('setQty', 'getName', 'getSku', 'getQty'));
		$quoteHelper = $this->getHelperMock('eb2cinventory/quote', array('addCartNotice'));
		$helper = $this->getHelperMock('eb2cinventory/data', array('__'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);

		$item
			->expects($this->any())
			->method('getName')
			->will($this->returnValue($itemName));
		$item
			->expects($this->any())
			->method('getSku')
			->will($this->returnValue($itemSku));
		$item
			->expects($this->any())
			->method('getQty')
			->will($this->returnValue($itemQty));
		$item
			->expects($this->once())
			->method('setQty')
			->with($this->identicalTo($availQty))
			->will($this->returnSelf());
		$helper
			->expects($this->once())
			->method('__')
			->with(
				$this->identicalTo($message),
				$this->identicalTo($itemName),
				$this->identicalTo($itemSku),
				$this->identicalTo($itemQty),
				$this->identicalTo($availQty)
			)
			->will($this->returnValue($translated));
		$quoteHelper
			->expects($this->once())
			->method('addCartNotice')
			->with($this->identicalTo($quote), $this->identicalTo($translated), $this->identicalTo($code))
			->will($this->returnSelf());
		$this->assertSame($quoteHelper, $quoteHelper->updateQuoteItemQuantity($quote, $item, $availQty));
	}
	/**
	 * If attempting to update the quote item quantity to zero, the item should instead
	 * be removed from the cart - via removeItemFromQuote
	 */
	public function testUpdateQuoteITemQuantityToZeroDeletesItem()
	{
		$quote = $this->getModelMock('sales/quote');
		$item = $this->getModelMock('sales/quote_item');
		$helper = $this->getHelperMock('eb2cinventory/quote', array('removeItemFromQuote'));
		$helper->expects($this->once())
			->method('removeItemFromQuote')
			->with($this->identicalTo($quote), $this->identicalTo($item))
			->will($this->returnSelf());

		$this->assertSame($helper, $helper->updateQuoteItemQuantity($quote, $item, 0));
	}
}

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

class EbayEnterprise_Inventory_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var Varien_Event */
    protected $_event;
    /** @var Varien_Event_Observer */
    protected $_eventObserver;
    /** @var EbayEnterprise_Inventory_Model_Quantity_Service */
    protected $_quantityService;
    /** @var EbayEnterprise_Inventory_Model_Observer */
    protected $_inventoryObserver;

    public function setUp()
    {
        // Mock the log context to prevent session hits while getting log context.
        $logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->setMethods(['getMetaData'])
            ->getMock();
        $logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        // Create the event observer and event objects
        // to feed into the observer methods. The event
        // observer will return the event so event data can
        // be set within tests just by adding it to the event object.
        $this->_event = new Varien_Event();
        $this->_eventObserver = new Varien_Event_Observer(['event' => $this->_event]);

        // Create a mock quantity service model - dependency
        // of the observer model. Constructor disabled to prevent
        // needing to inject dependencies.
        $this->_quantityService = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_service')
            ->disableOriginalConstructor()
            ->setMethods(['checkQuoteItemInventory'])
            ->getMock();
        ;

        // Create an instance of the observer to test, injecting
        // any test mocks for use throughout the tests.
        $this->_inventoryObserver = Mage::getModel(
            'ebayenterprise_inventory/observer',
            ['quantity_service' => $this->_quantityService, 'log_context' => $logContext]
        );
    }

    /**
     * When handling the after set qty event,
     * quote item quantities should be checked by checking
     * quote inventory via the quantity service model.
     */
    public function testHandleAfterSetItemQty()
    {
        $quoteItem = Mage::getModel('sales/quote_item');
        $this->_event->setItem($quoteItem);

        // Side-effect test: just need to make sure quote inventory
        // is checked via the quantity service model.
        $this->_quantityService->expects($this->once())
            ->method('checkQuoteItemInventory')
            ->with($this->identicalTo($quoteItem))
            ->will($this->returnSelf());

        $this->_inventoryObserver->handleAfterSetItemQty($this->_eventObserver);
    }
}

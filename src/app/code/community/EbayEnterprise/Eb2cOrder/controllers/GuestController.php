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

require_once 'Mage/Sales/controllers/GuestController.php';

class EbayEnterprise_Eb2cOrder_GuestController extends Mage_Sales_GuestController
{
	/**
	 * load the order with the order id from the request and
	 * bypass loading a shipment instance from the db.
	 */
	public function printOrderShipmentAction()
	{
		if ($this->_loadValidOrder() &&
				$this->_canViewOrder(Mage::registry('current_order')) &&
				$this->getRequest()->getParam('shipment_id')
		) {
			$this->loadLayout('print');
			$this->renderLayout();
		} else {
		  $this->_redirect('sales/guest/form');
		}
	}
}
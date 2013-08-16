<?php

class TrueAction_Eb2cAddress_Model_Observer
{

	/**
	 * Observe address validation events and perform EB2C address validation.
	 * Event data is expected to include the address object to be validated.
	 * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
	 */
	public function validateAddress($observer)
	{
		$address = $observer->getEvent()->getAddress();
		$validationError = Mage::getModel('eb2caddress/validator')->validateAddress($address);
		if ($validationError) {
			$address->addError($validationError);
		}
	}

	/**
	 * Render the suggestions block to be added into the response.
	 * @param Mage_Core_Controller_Varien_Action $controller
	 * @return string - rendered block
	 */
	protected function _getAddressBlockHtml(Mage_Core_Controller_Varien_Action $controller)
	{
		$layout = $controller->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_address_suggestions');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		return $output;
	}

	/**
	 * When address validation suggestions are present, add a re-rendered
	 * address block to the response which will include the address suggestions.
	 * @param Varien_Event_Observer $observer "Observer" object with access to the OPC controller.
	 */
	public function addSuggestionsToResponse($observer)
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$controller = $observer->getEvent()->getControllerAction();
		$body = Mage::helper('core')->jsonDecode($controller->getResponse()->getBody());
		if (isset($body['error']) && $validator->hasSuggestions()) {
			$body['suggestions'] = $this->_getAddressBlockHtml($controller);
			$controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($body));
		}
	}

}

<?php
class TrueAction_Eb2cAddress_Model_Observer
{
	protected function _isEnabled()
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2caddress/config'))
			->isValidationEnabled;
	}

	/**
	 * For frontend area: Observe address validation events and perform EB2C address validation.
	 * Event data is expected to include the address object to be validated.
	 * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
	 */
	public function validateAddress($observer)
	{
		$this->_validateAddress($observer);
	}

	/**
	 * For Adminhtml area: Observe address validation events and perform EB2C address validation.
	 * Event data is expected to include the address object to be validated.
	 * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
	 */
	public function validateAddressAdminhtml($observer)
	{
		$this->_validateAddress($observer, Mage_Core_Model_App_Area::AREA_ADMINHTML);
	}

	/**
	 * The function to call the actual validation process if we are enabled to perform it.
	 * @param Varien_Event_Observer $observer "Observer" object with access to the address to validate
	 * @param optional Mage_Core_Model_App::AREA_xxx designation
	 */
	protected function _validateAddress($observer, $area=null)
	{
		if (!$this->_isEnabled()) {
			return;
		}
		$address = $observer->getEvent()->getAddress();
		$validationError = Mage::getModel('eb2caddress/validator')->validateAddress($address, $area);
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
		if (!$this->_isEnabled()) {
			return;
		}
		$validator = Mage::getModel('eb2caddress/validator');
		$controller = $observer->getEvent()->getControllerAction();
		$body = Mage::helper('core')->jsonDecode($controller->getResponse()->getBody());
		if (isset($body['error']) && !$validator->isValid()) {
			$body['suggestions'] = $this->_getAddressBlockHtml($controller);
			$controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($body));
		}
		$validator->getAddressCollection()->setHasFreshSuggestions(false);
	}
}

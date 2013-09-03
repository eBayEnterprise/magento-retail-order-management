<?php

class TrueAction_Eb2cAddress_Block_Suggestions extends Mage_Core_Block_Template
{

	const SUGGESTION_INPUT_NAME = 'validation_option';
	const DEFAULT_ADDRESS_FORMAT_CONFIG = 'address_format_full';
	const NEW_ADDRESS_SELECTION_VALUE = 'new_address';

	protected $_template = 'eb2caddress_frontend/customer/address/suggestions.phtml';

	/**
	 * mapping of messages used by this block
	 * @var array
	 */
	protected $_messages = array(
		'suggested_address' => 'TrueAction_Eb2cAddress_Suggestions_Label',
		'suggestion_label' => 'TrueAction_Eb2cAddress_Suggested_Address_Label',
		'original_label' => 'TrueAction_Eb2cAddress_Original_Address_Label',
		'new_label' => 'TrueAction_Eb2cAddress_New_Address_Label',
	);

	/**
	 * config registry model, if populated, should be expected to have had
	 * the necessary config models populated.
	 * @var TrueAction_Eb2cCore_Model_Config_Registry
	 */
	protected $_config = null;

	/**
	 * An address validation validator model which will be used to look up
	 * any necessary addresses/data related to address validation.
	 * @var TrueAction_Eb2cAddress_Model_Validator
	 */
	protected $_validator = null;

	/**
	 * Flag indicating if address suggestions should be shown.
	 * Ensures that the block only ever asks the validator once as after
	 * the block starts pulling address data from the validator, this would change
	 * as the suggestions would no longer be "fresh".
	 * @var boolean
	 */
	protected $_shouldShowSuggestions = null;

	protected function _construct()
	{
		$this->_config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2caddress/config'));
		$this->_validator = Mage::getModel('eb2caddress/validator');
	}

	/**
	 * Determines if there are suggestions to display to the user.
	 * @return boolean
	 */
	public function shouldShowSuggestions()
	{
		if (is_null($this->_shouldShowSuggestions)) {
			$this->_shouldShowSuggestions = $this->_validator->hasFreshSuggestions()
				&& ($this->_validator->hasSuggestions() || !$this->_validator->isValid());
		}
		return $this->_shouldShowSuggestions;
	}

	/**
	 * Return an array of suggested addresses.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getSuggestedAddresses()
	{
		return $this->_validator->getSuggestedAddresses();
	}

	/**
	 * Get the address object for the original address submitted to the service.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		return $this->_validator->getOriginalAddress();
	}

	/**
	 * Return the formatted addresses, using the EB2C AddressFrontend address template.
	 */
	public function getRenderedAddress(Mage_Customer_Model_Address_Abstract $address)
	{
		return Mage::helper('customer/address')
			->getRenderer('eb2caddress/address_renderer')
			->initType($this->_config->getConfig(($this->getAddressFormat() ?: self::DEFAULT_ADDRESS_FORMAT_CONFIG)))
			->render($address);
	}

	/**
	 * Get a JSON representation of the address data.
	 * @param Mage_Customer_Model_Address_Abstract $address
	 * @return string
	 */
	public function getAddressJSONData(Mage_Customer_Model_Address_Abstract $address)
	{
		$address->explodeStreetAddress();
		return $address->toJson(array('street1', 'street2', 'street3', 'street4', 'city', 'region_id', 'country_id', 'postcode'));
	}

	/**
	 * The name attribute of the address suggestion radio inputs.
	 * @return string
	 */
	public function getSuggestionInputName()
	{
		return self::SUGGESTION_INPUT_NAME;
	}

	/**
	 * The value of the input for chosing to enter a new address.
	 * @return string
	 */
	public function getNewAddressSelectionValue()
	{
		return self::NEW_ADDRESS_SELECTION_VALUE;
	}

	/**
	 * Get the user facing messages, ensuring they are all run through the
	 * __() translation method.
	 * @return string
	 */
	protected function _getMessage($name)
	{
		return Mage::helper('eb2caddress')->__($this->_messages[$name]);
	}

	/**
	 * Get the message to show above suggested addresses.
	 * @return string
	 */
	public function getSuggestedAddressMessage()
	{
		return $this->_getMessage('suggested_address');
	}

	/**
	 * Get the message to show next to the suggestion radio button.
	 * @return string
	 */
	public function getSuggestionLabel()
	{
		return $this->_getMessage('suggestion_label');
	}

	/**
	 * Get the message to display with the selection to choose the original address.
	 * @return string
	 */
	public function getOriginalAddressLabel()
	{
		return $this->_getMessage('original_label');
	}

	/**
	 * Get the message to show with the selection to supply a new address.
	 * @return string
	 */
	public function getNewAddressLabel()
	{
		return $this->_getMessage('new_label');
	}

}

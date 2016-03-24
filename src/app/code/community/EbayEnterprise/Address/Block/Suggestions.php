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

/**
 * Renders a group of corrected addresses for the consumer to choose from
 */
class EbayEnterprise_Address_Block_Suggestions extends Mage_Core_Block_Template
{
    // Name of the input field
    const SUGGESTION_INPUT_NAME = 'validation_option';
    /**
     * Where to find the format template in the config registry
     * @see EbayEnterprise_Address_Model_Config::$_configPaths
     */
    const DEFAULT_ADDRESS_FORMAT_CONFIG = 'address_format_full';
    // When selecting from a list of addresses, this value means enter an entirely new address
    const NEW_ADDRESS_SELECTION_VALUE = 'new_address';
    /**
     * @see parent::$_template
     * @var string Path to the template value in theme
     */
    protected $_template = 'ebayenterprise_address/customer/address/suggestions.phtml';
    /** @var array Mapping of messages used by this block */
    protected $_messages = [
        'new_label'         => 'EbayEnterprise_Address_New_Address_Label',
        'original_label'    => 'EbayEnterprise_Address_Original_Address_Label',
        'suggested_address' => 'EbayEnterprise_Address_Suggestions_Label',
        'suggestion_label'  => 'EbayEnterprise_Address_Suggested_Address_Label',
    ];
    /**
     * An address validation validator model which will be used to look up
     * any necessary addresses/data related to address validation.
     *
     * @var EbayEnterprise_Address_Model_Validator
     */
    protected $_validator = null;
    /**
     * Flag indicating if address suggestions should be shown.
     * Ensures that the block only ever asks the validator once as after
     * the block starts pulling address data from the validator, this would change
     * as the suggestions would no longer be "fresh".
     * @var bool
     */
    protected $_shouldShowSuggestions = null;

    /**
     * Set default validator. Deliberately bypass Mage_Core_Block_Template::_construct overhead
     *
     * @see Mage_Core_Block_Abstract::_construct
     */
    protected function _construct()
    {
        $this->_validator = Mage::getModel('ebayenterprise_address/validator');
    }

    /**
     * Determine and cache if there are suggestions to display to the user.
     *
     * @return bool
     */
    public function shouldShowSuggestions()
    {
        if (is_null($this->_shouldShowSuggestions)) {
            $this->_shouldShowSuggestions = $this->_validator->hasFreshSuggestions() &&
                ($this->_validator->hasSuggestions() || !$this->_validator->isValid());
        }
        return $this->_shouldShowSuggestions;
    }

    /**
     * Return an array of suggested addresses.
     *
     * @return Mage_Customer_Model_Address_Abstract[]
     */
    public function getSuggestedAddresses()
    {
        return $this->_validator->getSuggestedAddresses();
    }

    /**
     * Get the address object for the original address submitted to the service.
     *
     * @return Mage_Customer_Model_Address_Abstract[]
     */
    public function getOriginalAddress()
    {
        return $this->_validator->getOriginalAddress();
    }

    /**
     * Return the formatted addresses, using the address template.
     *
     * @see Mage_Customer_Block_Address_Renderer_Default::render
     * @param Mage_Customer_Model_Address_Abstract $address the address to format
     * @return string
     */
    public function getRenderedAddress(Mage_Customer_Model_Address_Abstract $address)
    {
        $cfg = Mage::helper('ebayenterprise_address')->getConfigModel();
        return Mage::helper('customer/address')
            ->getRenderer('ebayenterprise_address/address_renderer')
            ->initType($cfg->getConfig(($this->getAddressFormat() ?: self::DEFAULT_ADDRESS_FORMAT_CONFIG)))
            ->render($address);
    }

    /**
     * Get a JSON representation of the address data.
     *
     * @param Mage_Customer_Model_Address_Abstract $address the address to serialize
     * @return string
     */
    public function getAddressJsonData(Mage_Customer_Model_Address_Abstract $address)
    {
        $address->explodeStreetAddress();
        return $address->toJson([
            'street1',
            'street2',
            'street3',
            'street4',
            'city',
            'region_id',
            'country_id',
            'postcode',
        ]);
    }

    /**
     * The name attribute of the address suggestion radio inputs.
     *
     * @return string
     */
    public function getSuggestionInputName()
    {
        return self::SUGGESTION_INPUT_NAME;
    }

    /**
     * The value of the input for choosing to enter a new address.
     *
     * @return string
     */
    public function getNewAddressSelectionValue()
    {
        return self::NEW_ADDRESS_SELECTION_VALUE;
    }

    /**
     * Get the user facing messages, ensuring they are all run through the
     * __() translation method.
     *
     * @param string $name the name of the message to translate
     * @return string
     */
    protected function _getMessage($name)
    {
        return Mage::helper('ebayenterprise_address')->__($this->_messages[$name]);
    }

    /**
     * Get the message to show above suggested addresses.
     *
     * @return string
     */
    public function getSuggestedAddressMessage()
    {
        return $this->_getMessage('suggested_address');
    }

    /**
     * Get the message to show next to the suggestion radio button.
     *
     * @return string
     */
    public function getSuggestionLabel()
    {
        return $this->_getMessage('suggestion_label');
    }

    /**
     * Get the message to display with the selection to choose the original address.
     *
     * @return string
     */
    public function getOriginalAddressLabel()
    {
        return $this->_getMessage('original_label');
    }

    /**
     * Get the message to show with the selection to supply a new address.
     *
     * @return string
     */
    public function getNewAddressLabel()
    {
        return $this->_getMessage('new_label');
    }
}

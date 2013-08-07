<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Model to calculate shipping tax
 *
 * @category    Mage
 * @package     Mage_Tax
 * @author      Magento Core Team
 */
class TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Shipping extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->setCode('shipping');
        $this->_calculator  = Mage::getSingleton('tax/calculation');
        $this->_config      = Mage::getSingleton('tax/config');
    }
}

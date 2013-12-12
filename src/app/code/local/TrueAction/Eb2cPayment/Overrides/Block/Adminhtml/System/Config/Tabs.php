<?php
/**
 * Overridden system configuration tabs block
 */
class TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Tabs extends Mage_Adminhtml_Block_System_Config_Tabs
{
    /**
     * determine if the config section should have its tab displayed.
     * @param  string   $code
     * @return boolean  true if the tab for the config section should be displayed; false otherwise
     */
    public function checkSectionPermissions($code=null)
    {
        $suppression = Mage::getModel('eb2cpayment/suppression');
        return !$suppression->isConfigSuppressed((string) $code) and
            parent::checkSectionPermissions($code);
    }

}

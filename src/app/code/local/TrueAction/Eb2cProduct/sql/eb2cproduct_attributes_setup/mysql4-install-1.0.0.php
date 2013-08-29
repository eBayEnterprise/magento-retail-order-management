<?php
$installer = $this;
$installer->startSetup();

$defaultAttributes = Mage::getModel('eb2cproduct/attributes');
$setup = new TrueAction_Eb2cProduct_Model_Resource_Eav_Entity_Setup('core_setup');
$setup->applyToAllSets($defaultAttributes);

$installer->endSetup();

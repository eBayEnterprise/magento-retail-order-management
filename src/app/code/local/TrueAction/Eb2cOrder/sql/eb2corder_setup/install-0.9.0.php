<?php
Mage::log(sprintf('[ %s ] Installing Eb2cOrder 0.9.0', get_class($this)), Zend_Log::DEBUG);

$installer = $this;
$installer->startSetup();
$permissions = Mage::getModel('eb2corder/suppression_permissions');
$permissions->apply();
$installer->endSetup();

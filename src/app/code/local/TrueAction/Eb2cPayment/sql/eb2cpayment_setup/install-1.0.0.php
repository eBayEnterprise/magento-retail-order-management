<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
$installer = new Enterprise_GiftCardAccount_Model_Mysql4_Setup('core_setup');
$installer->startSetup();
try{

	$installer->getConnection()->addColumn($this->getTable('enterprise_giftcardaccount'), 'eb2c_pan', 'varchar(255) DEFAULT NULL');
	$installer->getConnection()->addColumn($this->getTable('enterprise_giftcardaccount'), 'eb2c_pin', 'varchar(255) DEFAULT NULL');

} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
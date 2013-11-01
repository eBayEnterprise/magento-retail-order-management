<?php
class TrueAction_Eb2cCore_Overrides_Helper_Sales extends Mage_Core_Helper_Data
{
	private static $_overLoadedMethods = array(
		'canSendNewOrderConfirmationEmail' => true,
		'canSendNewOrderEmail' => true,
		'canSendOrderCommentEmail' => true,
		'canSendNewShipmentEmail' => true,
		'canSendShipmentCommentEmail' => true,
		'canSendNewInvoiceEmail' => true,
		'canSendInvoiceCommentEmail' => true,
		'canSendNewCreditmemoEmail' => true,
		'canSendCreditmemoCommentEmail' => true,
	);

	private $_config;
	private $_helper;

	public function __construct()
	{
		$this->_config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$this->_helper = new Mage_Sales_Helper_Data();
	}

	public function __call($name, $args)
	{
		if (isset(self::$_overLoadedMethods[$name])) {
			if ($this->_config->isSalesEmailsSuppressed) {
				return false;
			} else {
				return call_user_func_array(array($this->_helper, $name), $args);
			}
		} else {
			return call_user_func_array(array($this->_helper, $name), $args);
		}
	}
}

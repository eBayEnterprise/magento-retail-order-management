<?php
class TrueAction_Eb2cOrder_Overrides_Helper_Sales extends Mage_Core_Helper_Data
{
	const MAXIMUM_AVAILABLE_NUMBER = Mage_Sales_Helper_Data::MAXIMUM_AVAILABLE_NUMBER;

	protected $_moduleName = 'Mage_Sales';

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
		$this->_config = Mage::helper('eb2corder')->getConfig();
		$this->_helper = new Mage_Sales_Helper_Data();
	}

	public function __call($name, $args)
	{
		if (isset(self::$_overLoadedMethods[$name])) {
			if ($this->_config->transactionalEmailer === 'eb2c') {
				Mage::log("Suppressing email triggered by [{$name}]");
				return false;
			} else {
				return call_user_func_array(array($this->_helper, $name), $args);
			}
		} else {
			return call_user_func_array(array($this->_helper, $name), $args);
		}
	}
}

<?php
/**
 * Provide helper functions for Sales including email suppression features.
 * If EB2C is handling email, all the 'canSend*Email' methods should return
 * `false` to prevent Magento from sending transactional emails.
 */
class TrueAction_Eb2cOrder_Overrides_Helper_Sales extends Mage_Sales_Helper_Data
{
	protected $_moduleName = 'Mage_Sales';
	protected $_useLocalMail;
	public function __construct()
	{
		$this->_useLocalMail = (Mage::helper('eb2corder')->getConfig()->transactionalEmailer !== 'eb2c');
	}
	public function canSendNewOrderConfirmationEmail($s=null) { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendNewOrderEmail($s=null)             { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendOrderCommentEmail($s=null)         { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendNewShipmentEmail($s=null)          { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendShipmentCommentEmail($s=null)      { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendNewInvoiceEmail($s=null)           { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendInvoiceCommentEmail($s=null)       { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendNewCreditmemoEmail($s=null)        { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
	public function canSendCreditmemoCommentEmail($s=null)    { $f = __FUNCTION__; return $this->_useLocalMail && parent::$f($s); }
}


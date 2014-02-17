<?php
/**
 * This exception should get noticed.
 */
class TrueAction_Eb2cCore_Exception_Critical extends TrueAction_Eb2cCore_Exception
{
	/**
	 * @link http://www.php.net/manual/en/class.exception.php
	 */
	public function __construct($message="", $code=0, Exception $previous=null)
	{
		Mage::helper('trueaction_magelog')->logCrit('%s', array($this));
		parent::__construct($message, $code, $previous);
	}
}


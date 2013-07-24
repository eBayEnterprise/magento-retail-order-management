<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_AjaxController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Checks the SV if it exists and returns the bankname or an error message
	 *
	 * @return void
	 */
	public function checkPanAction()
	{
		$result = array();
		$pan = $this->getRequest()->getPost('pan');
		$pan = Mage::helper('eb2cstoredvalue')->sanitizeData($pan);
		$result['pan'] = $pan;
		$result['valid'] = true;
		$result['msg'] = $this->__('');

		if (!is_numeric($pan) || strlen($pan) > 30) {
			$result['valid'] = false;
			$result['msg'] = $this->__('Invalid Payment Account Numbers.');
		}

		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

	/**
	 * void redeem card payment in eb2c
	 *
	 * @return void
	 */
	public function voidCardAction()
	{
		$result = array();
		$result['success'] = false;
		$action = $this->getRequest()->getPost('action');

		if (strtoupper(trim($action)) === 'VOID CARD') {
			$pan = $this->getRequest()->getPost('pan');
			$pin = $this->getRequest()->getPost('pin');

			// TODO: make void call to eb2c


			$result['success'] = true;
		}

		$this->getResponse()->setBody(Zend_Json::encode($result));
	}
}

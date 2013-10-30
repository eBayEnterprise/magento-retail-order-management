<?php
require_once('Mage/Checkout/controllers/OnepageController.php');
class TrueAction_Eb2cInventory_Override_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
	const TRUEACTION_EB2CINVENTORY_TERMS_AGREEMENT_ERROR = 'TrueAction_Eb2cInventory_Terms_Agreement_Error';
	const TRUEACTION_EB2CINVENTORY_ERROR_PROCESSING_ORDER = 'TrueAction_Eb2cInventory_Error_Processing_Order';

	/**
	 * Create order action - overriding for eb2c allocation
	 */
	public function saveOrderAction()
	{
		if (!$this->_validateFormKey()) {
			$this->_redirect('*/*');
			return;
		}

		if ($this->_expireAjax()) {
			return;
		}

		$result = array();
		try {
			$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
			if ($requiredAgreements) {
				$postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
				$diff = array_diff($requiredAgreements, $postedAgreements);
				if ($diff) {
					$result['success'] = false;
					$result['error'] = true;
					$result['error_messages'] = $this->__(self::TRUEACTION_EB2CINVENTORY_TERMS_AGREEMENT_ERROR);
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
					return;
				}
			}

			// Begin eb2c inventory allocation Event
			Mage::dispatchEvent(
				'eb2c_allocation_onepage_save_order_action_before',
				array('quote' => $this->getOnepage()->getQuote(), 'result' => $result, 'response' => $this->getResponse())
			);
			// end eb2c inventory allocation Event

			$data = $this->getRequest()->getPost('payment', array());
			if ($data) {
				$data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT |
					Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY |
					Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY |
					Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX |
					Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
				$this->getOnepage()->getQuote()->getPayment()->importData($data);
			}
			// Begin eb2c pre order create:
			Mage::dispatchEvent(
				'eb2c_onepage_save_order_before',
				array('quote' => $this->getOnepage()->getQuote(), 'request' => $this->getRequest())
			);
			// End eb2c pre order create;

			$this->getOnepage()->saveOrder();

			$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
			$result['success'] = true;
			$result['error']   = false;
		} catch (TrueAction_Eb2cInventory_Model_Allocation_Exception $e) {
			$message = $e->getMessage();
			if (!empty($message)) {
				$result['error_messages'] = $message;
			}

			$result['success'] = false;
			$result['error'] = true;
			$result['redirect'] = Mage::getUrl('checkout/cart');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
			return;

		} catch (Mage_Payment_Model_Info_Exception $e) {
			$message = $e->getMessage();
			if (!empty($message)) {
				$result['error_messages'] = $message;
			}
			$result['goto_section'] = 'payment';
			$result['update_section'] = array(
				'name' => 'payment-method',
				'html' => $this->_getPaymentMethodsHtml()
			);
		} catch (Mage_Core_Exception $e) {
			Mage::logException($e);
			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
			$result['success'] = false;
			$result['error'] = true;
			$result['error_messages'] = $e->getMessage();

			$gotoSection = $this->getOnepage()->getCheckout()->getGotoSection();
			if ($gotoSection) {
				$result['goto_section'] = $gotoSection;
				$this->getOnepage()->getCheckout()->unsGotoSection();
			}
			$updateSection = $this->getOnepage()->getCheckout()->getUpdateSection();
			if ($updateSection) {
				if (isset($this->_sectionUpdateFunctions[$updateSection])) {
					$updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
					$result['update_section'] = array(
						'name' => $updateSection,
						'html' => $this->$updateSectionFunction()
					);
				}
				$this->getOnepage()->getCheckout()->unsUpdateSection(null);
			}
		} catch (Exception $e) {
			Mage::logException($e);
			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
			$result['success']  = false;
			$result['error']    = true;
			$result['error_messages'] = $this->__(self::TRUEACTION_EB2CINVENTORY_ERROR_PROCESSING_ORDER);
		}
		$this->getOnepage()->getQuote()->save();
		/**
		 * when there is redirect to third party, we don't want to save order yet.
		 * we will save the order in return action.
		 */
		if (isset($redirectUrl)) {
			$result['redirect'] = $redirectUrl;
		}

		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}
}

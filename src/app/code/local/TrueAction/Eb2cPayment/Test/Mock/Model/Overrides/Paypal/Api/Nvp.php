<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Overrides_Paypal_Api_Nvp extends EcomDev_PHPUnit_Test_Case
{
	public function buildQuoteMock()
	{
		$paymentMock = $this->getMock(
			'Mage_Sales_Model_Quote_Payment',
			array(
				'getEb2cPaypalToken', 'getEb2cPaypalPayerId', 'getEb2cPaypalTransactionID',
				'setEb2cPaypalToken', 'setEb2cPaypalPayerId', 'setEb2cPaypalTransactionID', 'save'
			)
		);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('1234')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalTransactionID')
			->will($this->returnValue('O-5SM75867VD734394E')
			);

		$paymentMock->expects($this->any())
			->method('setEb2cPaypalToken')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalPayerId')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalTransactionID')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getAllItems', 'getPayment', 'getId')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getName', 'getQty', 'getPrice')
		);
		$itemMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Product A')
			);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getPrice')
			->will($this->returnValue(25.00)
			);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('getPayment')
			->will($this->returnValue($paymentMock)
			);

		return $quoteMock;
	}

	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 *
	 * @return void
	 */
	public function replaceByMockCheckoutSessionModel()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addSuccess', 'addError', 'addException', 'getQuoteId'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('addSuccess')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('addException')
			->will($this->returnSelf());
		$sessionMock->expects($this->any())
			->method('getQuoteId')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_set_express_checkout class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalSetExpressCheckoutModel()
	{
		$paypalSetExpressCheckoutMock = $this->getModelMockBuilder('eb2cpayment/paypal_set_express_checkout')
			->disableOriginalConstructor()
			->setMethods(array('setExpressCheckout', 'parseResponse'))
			->getMock();
		$paypalSetExpressCheckoutMock->expects($this->any())
			->method('setExpressCheckout')
			->will($this->returnValue('<foo></foo>'));
		$paypalSetExpressCheckoutMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(new Varien_Object(array('response_code' => 'success', 'token' => '1234455'))));
		$this->replaceByMock('model', 'eb2cpayment/paypal_set_express_checkout', $paypalSetExpressCheckoutMock);
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_get_express_checkout class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalGetExpressCheckoutModel()
	{
		$paypalGetExpressCheckoutMock = $this->getModelMockBuilder('eb2cpayment/paypal_get_express_checkout')
			->disableOriginalConstructor()
			->setMethods(array('getExpressCheckout', 'parseResponse'))
			->getMock();
		$paypalGetExpressCheckoutMock->expects($this->any())
			->method('getExpressCheckout')
			->will($this->returnValue('<foo></foo>'));
		$paypalGetExpressCheckoutMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
						'payer_email' => 'someone@somewhere.com',
						'payer_id' => '134',
						'payer_status' => 'valid',
						'payer_name_first_name' => 'John',
						'payer_name_last_name' => 'Doe',
						'payer_country' => 'US',
						'shipping_address_line1' => '1111 Some Street',
						'shipping_address_city' => 'Some City',
						'shipping_address_main_division' => 'Some State',
						'shipping_address_postal_code' => '12335',
						'shipping_address_country_code' => 'US',
						'payer_phone' => '555-555-5555',
						'shipping_address_status' => 'valid',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_get_express_checkout', $paypalGetExpressCheckoutMock);
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_do_express_checkout class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoExpressCheckoutModel()
	{
		$paypalDoExpressCheckoutMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_express_checkout')
			->disableOriginalConstructor()
			->setMethods(array('doExpressCheckout', 'parseResponse'))
			->getMock();
		$paypalDoExpressCheckoutMock->expects($this->any())
			->method('doExpressCheckout')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoExpressCheckoutMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
						'transaction_id' => '324533453453',
						'payer_id' => '134',
						'payment_status' => 'pending',
						'pending_reason' => 'Waiting fund verification',
						'reason_code' => 'na',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_express_checkout', $paypalDoExpressCheckoutMock);
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_do_authorization class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoAuthorizationModel()
	{
		$paypalDoAuthorizationMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_authorization')
			->disableOriginalConstructor()
			->setMethods(array('doAuthorization', 'parseResponse'))
			->getMock();
		$paypalDoAuthorizationMock->expects($this->any())
			->method('doAuthorization')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoAuthorizationMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
						'payment_status' => 'pending',
						'pending_reason' => 'Waiting fund verification',
						'reason_code' => 'na',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_authorization', $paypalDoAuthorizationMock);
	}

	/**
	 * replacing by mock of the eb2cpayment/paypal_do_void class
	 *
	 * @return void
	 */
	public function replaceByMockPaypalDoVoidModel()
	{
		$paypalDoVoidMock = $this->getModelMockBuilder('eb2cpayment/paypal_do_void')
			->disableOriginalConstructor()
			->setMethods(array('doVoid', 'parseResponse'))
			->getMock();
		$paypalDoVoidMock->expects($this->any())
			->method('doVoid')
			->will($this->returnValue('<foo></foo>'));
		$paypalDoVoidMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(
				new Varien_Object(
					array(
						'response_code' => 'success',
					)
				)
			));
		$this->replaceByMock('model', 'eb2cpayment/paypal_do_void', $paypalDoVoidMock);
	}
}

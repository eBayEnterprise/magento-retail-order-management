<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Payload;

class EbayEnterprise_PayPal_Test_Model_Express_ApiTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const BIDIRECTIONAL_API = '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi';
	const SETEXPRESS_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutRequest';
	const SETEXPRESS_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutReply';
	const GETEXPRESS_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutRequest';
	const GETEXPRESS_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutReply';
	const DOEXPRESS_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoExpressCheckoutRequest';
	const DOEXPRESS_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoExpressCheckoutReply';
	const DOAUTH_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoAuthorizationRequest';
	const DOAUTH_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoAuthorizationReply';
	const DOVOID_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoVoidRequest';
	const DOVOID_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoVoidReply';
	const LINE_ITEM_ITERABLE = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\LineItemIterable';
	const LINE_ITEM = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItem';

	protected $_sdk;
	protected $_coreHelper;
	protected $_helper;
	protected $_checkoutSession;
	protected $_getSdkApiMap;
	protected $_cancelUrl = 'cancel url';
	protected $_returnUrl = 'return url';
	protected $_token = 'token';
	protected $_orderId = 'order id';
	protected $_currencyCode = 'USD';
	protected $_lineItemStub;
	protected $_lineItemIterableStub;

	public function setUp()
	{
		parent::setUp();
		// disable _construct to prevent excessive stubs
		$this->_coreUrl = $this->getModelMock(
			'core/url', array('_construct', 'getUrl')
		);
		$this->_coreUrl->expects($this->any())
			->method('getUrl')->will(
				$this->returnValueMap(
					array(
						array('ebayenterprise_paypal_express/checkout/return',
						      array(), 'the.return.url'),
						array('ebayenterprise_paypal_express/checkout/cancel',
						      array(), 'the.cancel.url'),
					)
				)
			);
		// stub sdk
		$this->_sdk = $this->getMock(self::BIDIRECTIONAL_API);
		$this->_sdk->expects($this->any())
			->method('setRequestBody')
			->will($this->returnSelf());
		$this->_sdk->expects($this->any())
			->method('send')->will($this->returnSelf());
		$this->_getSdkApiMap = array(
			array('payments', 'paypal/setExpress', array(), $this->_sdk),
			array('payments', 'paypal/getExpress', array(), $this->_sdk),
			array('payments', 'paypal/doExpress', array(), $this->_sdk),
			array('payments', 'paypal/doAuth', array(), $this->_sdk),
			array('payments', 'paypal/void', array(), $this->_sdk),
		);
		$this->_lineItemStub = $this->getMock(self::LINE_ITEM);
		$this->_stubAcceptStrReturnSelf(
			array('setName', 'setCurrencyCode'),
			$this->_lineItemStub
		);
		$this->_lineItemStub->expects($this->any())
			->method('setUnitAmount')
			->with($this->_isNumeric())
			->will($this->returnSelf());
		// sequence number can be set to any scalar
		$this->_lineItemStub->expects($this->any())
			->method('setSequenceNumber')
			->with($this->_isScalar())
			->will($this->returnSelf());
		$this->_lineItemStub->expects($this->any())
			->method('setQuantity')
			->with($this->isType('int'))
			->will($this->returnSelf());

		$this->_lineItemIterableStub = $this->getMockBuilder(self::LINE_ITEM_ITERABLE)
			->setMethods(array('getEmptyLineItem'))
			->disableOriginalConstructor()
			->getMock();
		$this->_lineItemIterableStub->expects($this->any())
			->method('getEmptyLineItem')
			->will($this->returnValue($this->_lineItemStub));
		// stub helpers
		$this->_coreHelper = $this->getHelperMock(
			'eb2ccore/data', array('getSdkApi')
		);
		$this->_coreHelper->expects($this->any())
			->method('getSdkApi')->will(
				$this->returnValueMap($this->_getSdkApiMap)
			);
		$this->_helper = $this->getHelperMock(
			'ebayenterprise_paypal/data', array('getConfigModel', '__')
		);
		$this->_helper->expects($this->any())->method('__')->will(
			$this->returnArgument(0)
		);
		// stub a quote
		$strMethods = array('getCity', 'getRegionCode', 'getCountryId', 'getPostCode');
		$this->_quoteShipAddress = $this->getModelMock(
			'sales/quote_address',
			$strMethods + array('getStreet', 'getId')
		);
		foreach ($strMethods as $setter) {
			$this->_quoteShipAddress->expects($this->any())
				->method($setter)
				->will($this->returnValue('a string value'));
		}
		$this->_quoteShipAddress->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(array('line 1', 'line 2')));
		$this->_quoteShipAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$this->_quote = $this->getModelMock(
			'sales/quote', array('reserveOrderId', 'getReservedOrderId',
			                     'getAllItems', 'getTotals', 'getShippingAddress')
		);
		$this->_quote->expects($this->any())
			->method('reserveOrderId')->will($this->returnSelf());
		$this->_quote->expects($this->any())
			->method('getTotals')->will(
				$this->returnValue(
					array(
						'grand_total' => new Varien_Object(
							array('value' => 100)
						),
						'shipping'    => new Varien_Object(
							array('value' => 5.95)
						),
						'tax'         => new Varien_Object(
							array('value' => 2.50)
						),
						'discount' => new Varien_Object(
							array('value' => 100)
						),
					)
				)
			);
		$this->_quote->expects($this->any())
			->method('getReservedOrderId')->will($this->returnValue('orderid'));
		$this->_quote->expects($this->any())
			->method('getAllItems')->will(
				$this->returnValue($this->_stubQuoteItems())
			);
		$this->_quote->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($this->_quoteShipAddress));
		$this->_quote->setData(
			array(
				'quote_currency_code' => 'USD'
			)
		);
	}

	/**
	 * provide sdk exceptions to throw
	 * @return string[]
	 */
	public function provideSdkExceptions()
	{
		return array(
			array('\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError'),
			array('\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload'),
		);
	}

	/**
	 * inject the given config into the core helper
	 * @param  array  $config
	 */
	protected function _injectConfig(array $config)
	{
		$config = $this->buildCoreConfigRegistry(array_merge(array('transferLines' => true), $config));
		$this->_helper->expects($this->any())
			->method('getConfigModel')->will($this->returnValue($config));
	}

	/**
	 * mock the request and reply for the setExpressCheckout
	 * @param  bool  $successful true if the reply should report success
	 * @return IPayload[] (stub)
	 */
	protected function _mockSetExpressPayloads($successful)
	{
		$request = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
		$this->_stubAcceptStrReturnSelf(
			array('setOrderId', 'setReturnUrl', 'setCancelUrl', 'setLocaleCode', 'setCurrencyCode'),
			$request
		);
		$request->expects($this->once())
			->method('setAmount')
			->with($this->_isNumeric())
			->will($this->returnSelf());
		$request->expects($this->exactly(2))
			->method('getLineItems')
			->will($this->returnValue($this->_lineItemIterableStub));

		$reply = $this->getMock(self::SETEXPRESS_REPLY_PAYLOAD);
		$reply->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue($successful));
		$reply->expects($this->any())
			->method('getToken')
			->will($this->returnValue('token'));

		return array($request, $reply);
	}

	/**
	 * verify
	 * - a request payload is acquired and sent to get a response body.
	 * - the expected array structure is returned
	 * - for a failure response to a successful request,
	 *   throw EbayEnterprise_PayPal_Exception with a translated message.
	 * @loadExpectation messageReplies
	 * @dataProvider provideTrueFalse
	 */
	public function testSetExpressCheckout($isSuccessful)
	{
		$this->_injectConfig(array(
			'apiOperationSetExpressCheckout' => 'paypal/setExpress',
			'apiService'                     => 'payments',
		));
		list($request, $reply) = $this->_mockSetExpressPayloads($isSuccessful);
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		// test setExpressCheckout
		$api = $this->getModelMock(
			'ebayenterprise_paypal/express_api', array('_sendRequest')
		);
		$api->expects($this->any())
			->method('_sendRequest')
			->will($this->returnValue($reply));
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		if (!$isSuccessful) {
			$message
				= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
			$this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
		}
		$result = $api->setExpressCheckout($this->_cancelUrl, $this->_returnUrl, $this->_quote);
		$this->assertEquals($this->expected('setExpress')->getData(), $result);
	}

	/**
	 * setup payloads for getExpressCheckout
	 * @param  bool   $success
	 */
	protected function _mockGetExpressPayoads($success)
	{
		// mock the request
		$request = $this->getMock(self::GETEXPRESS_REQUEST_PAYLOAD);
		$this->_stubAcceptStrReturnSelf(
			array('setOrderId', 'setToken', 'setCurrencyCode'),
			$request
		);
		$request->expects($this->any())
			->method('getBillingAddressStatus')
			->will($this->returnValue(null));
		$request->expects($this->any())
			->method('getShipToAddressStatus')
			->will($this->returnValue(null));

		// mock the reply
		$reply = $this->getMock(self::GETEXPRESS_REPLY_PAYLOAD);
		$reply->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue($success));
		return array($request, $reply);
	}

	/**
	 * verify
	 * - get express payload is filled out
	 * - the expected array structure is returned
	 * - when the request was unsuccessful throw EbayEnterprise_PayPal_Exception
	 * @loadExpectation messageReplies
	 * @dataProvider provideTrueFalse
	 */
	public function testGetExpressCheckout($isSuccessful)
	{
		$this->_injectConfig(
			array(
				'apiOperationGetExpressCheckout' => 'paypal/getExpress',
				'apiService'                     => 'payments',
			)
		);
		list($request, $reply) = $this->_mockGetExpressPayoads($isSuccessful);
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		// test setExpressCheckout
		$api = $this->getModelMock('ebayenterprise_paypal/express_api', array('_sendRequest'));
		$api->expects($this->any())
			->method('_sendRequest')
			->will($this->returnValue($reply));
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		if (!$isSuccessful) {
			$message
				= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
			$this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
		}
		$result = $api->getExpressCheckout($this->_orderId, $this->_token, $this->_currencyCode);
		$this->assertEquals($this->expected('getExpress')->getData(), $result);
	}

	/**
	 * verify
	 * - the given sdk object is used to send the request
	 * - verify the reply is returned
	 */
	public function testSendRequest()
	{
		$requestPayload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IPayload');
		$requestPayload->expects($this->any())
			->method('serialize')
			->will($this->returnValue('serialized request'));
		$replyPayload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IPayload');
		$replyPayload->expects($this->any())
			->method('serialize')
			->will($this->returnValue('serialized reply'));
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($requestPayload));
		$this->_sdk->expects($this->any())
			->method('getResponseBody')
			->will($this->returnValue($replyPayload));
		$api = Mage::getModel('ebayenterprise_paypal/express_api');
		$this->assertSame(
			$replyPayload,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$api, '_sendRequest', array($this->_sdk)
			)
		);
	}

	/**
	 * verify
	 * - a single exception is thrown for any sdk exception
	 * @dataProvider provideSdkExceptions
	 * @expectedException EbayEnterprise_PayPal_Exception
	 */
	public function testSendRequestWithSdkException($sdkException)
	{
		$requestPayload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IPayload');
		$requestPayload->expects($this->any())
			->method('serialize')
			->will($this->returnValue('serialized request'));
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($requestPayload));
		$this->_sdk->expects($this->any())
			->method('send')
			->will($this->throwException(new $sdkException));
		$api = Mage::getModel('ebayenterprise_paypal/express_api');
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$api, '_sendRequest', array($this->_sdk)
		);
	}

	/**
	 * mock the request and reply for the doExpressCheckout
	 * @param  bool  $successful true if the reply should report success
	 * @return IPayload[] (stub)
	 */
	protected function _mockDoExpressPayloads($successful)
	{
		$request = $this->getMock(self::DOEXPRESS_REQUEST_PAYLOAD);
		$this->_stubAcceptStrReturnSelf(
			array('setRequestId', 'setOrderId', 'setToken', 'setPayerId', 'setCurrencyCode'),
			$request
		);
		$request->expects($this->exactly(2))
			->method('getLineItems')
			->will($this->returnValue($this->_lineItemIterableStub));
		$request->expects($this->once())
			->method('setAmount')
			->with($this->_isNumeric())
			->will($this->returnSelf());

		$reply = $this->getMock(self::DOEXPRESS_REPLY_PAYLOAD);
		$reply->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue($successful));
		return array($request, $reply);
	}

	protected $_payerId = 'payer id';
	protected $_pickUpStoreId = 'pick up stroe id';

	/**
	 * provide inputs for the case where:
	 * - the request was succesful and does or does not have a pickup id
	 * - the request was not successful
	 * @return array
	 */
	public function provideForDoExpressCheckout()
	{
		return array(
			array(true, 'pickup id'),
			array(true, null),
			array(false, 'pickup id'),
		);
	}

	/**
	 * verify
	 *  - the expected array structure is returned
	 * @param  bool $isSuccessful
	 * @dataProvider provideForDoExpressCheckout
	 * @loadExpectation messageReplies
	 */
	public function testDoExpressCheckout($isSuccessful, $pickUpStoreId)
	{
		$this->_injectConfig(
			array(
				'apiOperationDoExpressCheckout' => 'paypal/doExpress',
				'apiService'                    => 'payments',
			)
		);
		list($request, $reply) = $this->_mockDoExpressPayloads($isSuccessful);
		if ($pickUpStoreId) {
			$request->expects($this->once())
				->method('setPickUpStoreId')
				->with($this->isType('string'))
				->will($this->returnSelf());
		}
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		// test setExpressCheckout
		$api = $this->getModelMock('ebayenterprise_paypal/express_api', array('_sendRequest'));
		$api->expects($this->any())
			->method('_sendRequest')
			->will($this->returnValue($reply));
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		if (!$isSuccessful) {
			$message
				= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
			$this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
		}
		$result = $api->doExpressCheckout($this->_quote, $this->_token, $this->_payerId, $pickUpStoreId);
		$this->assertEquals($this->expected('doExpress')->getData(), $result);
	}

	/**
	 * stub items for the tests.
	 * @return Mage_Sale_Model_Quote_Item[]
	 */
	protected function _stubQuoteItems()
	{
		$methods = array(
			'getHasChildren', 'isChildrenCalculated', 'getChildren', 'getProduct',
			'getId', 'getQuoteItem', 'getQty', 'getPrice', 'getName',
		);
		// item mocks
		$item = $this->getModelMock('sales/quote_item', $methods);
		$parent = $this->getModelMock('sales/quote_item', $methods);
		$child = $this->getModelMock('sales/quote_item', $methods);

		$parent->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue(true));
		$parent->expects($this->any())
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$parent->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($child)));

		$stubs = array($item, $child, $parent);
		foreach ($stubs as $stub) {
			$stub->expects($this->any())
				->method('getProduct')
				->will($this->returnSelf());
			$stub->expects($this->any())
				->method('getId')
				->will($this->returnValue(key($stubs)));
			$stub->expects($this->any())
				->method('getQuoteItem')
				->will($this->returnSelf());
			$stub->expects($this->any())
				->method('getQty')
				->will($this->returnValue(1));
			$stub->expects($this->any())
				->method('getPrice')
				->will($this->returnValue(1));
		}

		$item->expects($this->any())
			->method('getName')
			->will($this->returnValue('item'));
		$parent->expects($this->any())
			->method('getName')
			->will($this->returnValue('parent'));
		$child->expects($this->any())
			->method('getName')
			->will($this->returnValue('child'));

		return array($item, $parent);
	}

	/**
	 * creat a constraint to assert an argument has a numeric value
	 * @return PHPUnit_Framework_Constraint
	 */
	protected function _isNumeric()
	{
		return $this->callback(
			function($val)
			{
				return is_numeric($val);
			}
		);
	}

	/**
	 * mock the request and reply for the doAuthorization message
	 * @param  bool  $successful true if the reply should report success
	 * @return IPayload[] (stub)
	 */
	protected function _mockDoAuthorizationPayloads($successful)
	{
		$request = $this->getMock(self::DOAUTH_REQUEST_PAYLOAD);
		$this->_stubAcceptStrReturnSelf(
			array('setRequestId', 'setOrderId', 'setCurrencyCode'), $request
		);
		$request->expects($this->once())
			->method('setAmount')
			->with($this->_isNumeric())
			->will($this->returnSelf());

		$reply = $this->getMock(self::DOAUTH_REPLY_PAYLOAD);
		$reply->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue($successful));
		return array($request, $reply);
	}

	/**
	 * verify
	 * - the request paylad is setup with the correct type of data
	 * - the resulting array is in the expected structure
	 * - the is_authorized field is true if the message succeeded; false otherwise
	 * - throws an EbayEnterprise_PayPal_Exception when the message fails
	 * @loadExpectation messageReplies
	 * @dataProvider provideTrueFalse
	 */
	public function testDoAuthorization($isSuccessful)
	{
		$this->_injectConfig(
			array(
				'apiOperationDoAuthorization' => 'paypal/doAuth',
				'apiService'                  => 'payments',
			)
		);
		list($request, $reply) = $this->_mockDoAuthorizationPayloads($isSuccessful);
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		// test setExpressCheckout
		$api = $this->getModelMock('ebayenterprise_paypal/express_api', array('_sendRequest'));
		$api->expects($this->any())
			->method('_sendRequest')
			->will($this->returnValue($reply));
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		if (!$isSuccessful) {
			$message
				= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
			$this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
		}
		$result = $api->doAuthorization($this->_quote);
		$expectation = 'doAuth/' . ($isSuccessful ? 'success' : 'failure');
		$this->assertEquals($this->expected($expectation)->getData(), $result);
	}

	/**
	 * mock the request and reply for the doVoid message
	 * @param  bool  $successful true if the reply should report success
	 * @return IPayload[] (stub)
	 */
	protected function _mockDoVoidPayloads($successful)
	{
		$request = $this->getMock(self::DOVOID_REQUEST_PAYLOAD);
		$request->expects($this->once())
			->method('setRequestId')
			->with($this->isType('string'))
			->will($this->returnSelf());
		$request->expects($this->once())
			->method('setOrderId')
			->with($this->isType('string'))
			->will($this->returnSelf());

		$reply = $this->getMock(self::DOVOID_REPLY_PAYLOAD);
		$reply->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue($successful));
		return array($request, $reply);
	}

	/**
	 * verify
	 * - the request paylad is setup with the correct type of data
	 * - the resulting array is in the expected structure
	 * - the is_voided field is true if the message succeeded; false otherwise
	 * - throws an EbayEnterprise_PayPal_Exception when the message fails
	 * @loadExpectation messageReplies
	 * @dataProvider provideTrueFalse
	 */
	public function testDoVoid($isSuccessful)
	{
		$order = $this->getModelMock('sales/order', array('getIncrementId'));
		$order->expects($this->any())
			->method('getIncrementId')
			->will($this->returnValue($this->_orderId));

		$this->_injectConfig(
			array(
				'apiOperationDoVoid' => 'paypal/void',
				'apiService'         => 'payments',
			)
		);
		list($request, $reply) = $this->_mockDoVoidPayloads($isSuccessful);
		$this->_sdk->expects($this->any())
			->method('getRequestBody')
			->will($this->returnValue($request));
		// test setExpressCheckout
		$api = $this->getModelMock('ebayenterprise_paypal/express_api', array('_sendRequest'));
		$api->expects($this->any())
			->method('_sendRequest')
			->will($this->returnValue($reply));
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(
			$api, array(
				'_helper' => $this->_helper, '_coreHelper' => $this->_coreHelper
			)
		);
		if (!$isSuccessful) {
			$message
				= EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
			$this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
		}
		$result = $api->doVoid($order);
		$expectation = 'void/' . ($isSuccessful ? 'success' : 'failure');
		$this->assertEquals($this->expected($expectation)->getData(), $result);
	}

	/**
	 * use to assert an argument is a scalar
	 * @return PHPUnit_Framework_Constraint
	 */
	protected function _isScalar()
	{
		return $this->callback(
			function($val)
			{
				return is_scalar($val);
			}
		);
	}

	/**
	 * stub the specified methods on the mock to expect a
	 * string argument and return itself
	 * @param  array  $methods
	 * @param  object $stub
	 * @return object
	 */
	protected function _stubAcceptStrReturnSelf(array $methods, $stub)
	{
		foreach ($methods as $method) {
			$stub->expects($this->any())
				->method($method)
				->with($this->isType('string'))
				->will($this->returnSelf());
		}
		return $stub;
	}
}

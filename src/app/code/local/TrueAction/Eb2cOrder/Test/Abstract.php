<?php
abstract class TrueAction_Eb2cOrder_Test_Abstract extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Mocks a Sales Order
	 */
	public function getMockSalesOrder()
	{
		$reallyBigJavascriptData = array(
			'TF1;015;;;;;;;;;;;;;;;;;;;;;;Mozilla;Netscape;5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%',
			'20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;20030107;undefined;true;;true;MacIntel;undefined;Mozilla/5.0%',
			'20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20S',
			'afari/536.30.1;en-us;iso-8859-1;;undefined;undefined;undefined;undefined;true;true;1376075038705;-5;June%207%2C%202005%209%3A33',
			'%3A44%20PM%20EDT;1920;1080;;11.8;7.7.1;;;;2;300;240;August%209%2C%202013%203%3A03%3A58%20PM%20EDT;24;1920;1054;0;22;;;;;;Shockw',
			'ave%20Flash%7CShockwave%20Flash%2011.8%20r800;;;;QuickTime%20Plug-in%207.7.1%7CThe%20QuickTime%20Plugin%20allows%20you%20to%20v',
			'iew%20a%20wide%20variety%20of%20multimedia%20content%20in%20web%20pages.%20For%20more%20information%2C%20visit%20the%20%3CA%20H',
			'REF%3Dhttp%3A//www.apple.com/quicktime%3EQuickTime%3C/A%3E%20Web%20site.;;;;;Silverlight%20Plug-In%7C5.1.20125.0;;;;18;',
		);

		return $this->_getFullMocker(
			'sales/order',
			array(
				'getAllItems'           => array($this->_getMockSalesOrderItem()),
				'getAllPayments'        => array($this->_getMockSalesOrderPayment()),
				'getBillingAddress'     => $this->_getMockSalesOrderAddress(),
				'getCreatedAt'          => '2013-08-09',
				'getCustomerDob'        => '1890-10-02',
				'getCustomerEmail'      => 'groucho@westwideweb.com',
				'getCustomerFirstname'  => 'Hugo',
				'getCustomerGender'     => 'M',
				'getCustomerId'         => '77',
				'getCustomerLastname'   => 'Hackenbush',
				'getCustomerMiddlename' => 'Z.',
				'getCustomerPrefix'     => 'Dr.',
				'getCustomerSuffix'     => 'MD',
				'getCustomerTaxvat'     => '--',
				'getEb2cHostName'       => 'mwest.mage-tandev.net',
				'getEb2cIpAddress'      => '208.247.73.130',
				'getEb2cJavascriptData' => implode($reallyBigJavascriptData),
				'getEb2cReferer'        => 'https://example.com/',
				'getEb2cSessionId'      => '5nqm2sczfncsggzdqylueb2h',
				'getEb2cUserAgent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36',
				'getEntityId'           => '711',
				'getGrandTotal'         => '1776',
				'getGrandTotal'         => '1776',
				'getId'                 => '666',
				'getIncrementId'        => '8675309',
				'getOrderCurrencyCode'  => 'USD',
				'getShippingAddress'    => $this->_getMockSalesOrderAddress(),
				'setState'              => 'self',
				'save'                  => 'self',
			)
		);
	}

	/**
	 * Mocks a Sales Order - mock payment method that return paypal express
	 */
	public function getMockSalesOrder2()
	{
		$reallyBigJavascriptData = array(
			'TF1;015;;;;;;;;;;;;;;;;;;;;;;Mozilla;Netscape;5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%',
			'20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;20030107;undefined;true;;true;MacIntel;undefined;Mozilla/5.0%',
			'20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20S',
			'afari/536.30.1;en-us;iso-8859-1;;undefined;undefined;undefined;undefined;true;true;1376075038705;-5;June%207%2C%202005%209%3A33',
			'%3A44%20PM%20EDT;1920;1080;;11.8;7.7.1;;;;2;300;240;August%209%2C%202013%203%3A03%3A58%20PM%20EDT;24;1920;1054;0;22;;;;;;Shockw',
			'ave%20Flash%7CShockwave%20Flash%2011.8%20r800;;;;QuickTime%20Plug-in%207.7.1%7CThe%20QuickTime%20Plugin%20allows%20you%20to%20v',
			'iew%20a%20wide%20variety%20of%20multimedia%20content%20in%20web%20pages.%20For%20more%20information%2C%20visit%20the%20%3CA%20H',
			'REF%3Dhttp%3A//www.apple.com/quicktime%3EQuickTime%3C/A%3E%20Web%20site.;;;;;Silverlight%20Plug-In%7C5.1.20125.0;;;;18;',
		);

		return $this->_getFullMocker(
			'sales/order',
			array(
				'getAllItems'           => array($this->_getMockSalesOrderItem()),
				'getAllPayments'        => array($this->_getMockSalesOrderPayment2()),
				'getBillingAddress'     => $this->_getMockSalesOrderAddress(),
				'getCreatedAt'          => '2013-08-09',
				'getCustomerDob'        => '1890-10-02',
				'getCustomerEmail'      => 'groucho@westwideweb.com',
				'getCustomerFirstname'  => 'Hugo',
				'getCustomerGender'     => 'M',
				'getCustomerId'         => '77',
				'getCustomerLastname'   => 'Hackenbush',
				'getCustomerMiddlename' => 'Z.',
				'getCustomerPrefix'     => 'Dr.',
				'getCustomerSuffix'     => 'MD',
				'getCustomerTaxvat'     => '--',
				'getEb2cHostName'       => 'mwest.mage-tandev.net',
				'getEb2cIpAddress'      => '208.247.73.130',
				'getEb2cJavascriptData' => implode($reallyBigJavascriptData),
				'getEb2cReferer'        => 'https://www.google.com/',
				'getEb2cSessionId'      => '5nqm2sczfncsggzdqylueb2h',
				'getEb2cUserAgent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36',
				'getEntityId'           => '711',
				'getGrandTotal'         => '1776',
				'getGrandTotal'         => '1776',
				'getId'                 => '666',
				'getIncrementId'        => '8675309',
				'getOrderCurrencyCode'  => 'USD',
				'getShippingAddress'    => $this->_getMockSalesOrderAddress(),
				'setState'              => 'self',
				'save'                  => 'self',
			)
		);
	}

	/**
	 * Mocks the Mage_Sales_Model_Order_Address
	 *
	 */
	private function _getMockSalesOrderAddress()
	{
		return $this->_getFullMocker(
			'sales/order_address',
			array (
				'getCity'       => 'Williamstown',
				'getCountryId'  => 'US',
				'getFirstname'  => 'Rufus',
				'getLastname'   => 'Firefly',
				'getMiddlename' => 'T.',
				'getPostalCode' => '90210',
				'getPrefix'     => 'Prof.',
				'getRegion'     => 'NJ',
				'getStreet'     => array('1313 Mockingbird Ln', 'Suite 13'),
				'getSuffix'     => '5th Earl of Shroudshire',
				'getTelephone'  => '800-666-1313',
			)
		);
	}

	/**
	 * Mocks the Mage_Sales_Model_Order_Item
	 *
	 */
	private function _getMockSalesOrderItem()
	{
		return $this->_getFullMocker(
			'sales/order_item',
			array (
				'getId'                     => '48',
				'getDiscountAmount'         => '0',
				'getEb2cDeliveryWindowFrom' => '2013-08-09',
				'getEb2cDeliveryWindowTo'   => '2013-08-13',
				'getEb2cMessageType'        => 'MessageType',
				'getEb2cReservationId'      => '0123456789',
				'getEb2cShippingWindowFrom' => '2013-08-09',
				'getEb2cShippingWindowTo'   => '2013-08-13',
				'getName'                   => 'An Item Name',
				'getPrice'                  => '1776',
				'getQtyOrdered'             => '1',
				'getSku'                    => 'SKU123456',
				'getTaxAmount'              => '0',
				'getTaxPercent'             => '0',
				'getOrder'                  => $this->_getFullMocker('sales/order', array ('getQuoteId' => 1)),
			)
		);
	}

	/**
 	 * Let us mock a Mage_Sales_Model_Order_Payment
	 *
	 */
	private function _getMockSalesOrderPayment()
	{
		return $this->_getFullMocker(
			'sales/order_payment',
			array (
				'getAmountAuthorized' => '1776',
				'getCcApproval'       => 'APP123456',
				'getCcAvsStatus'      => 'Z',
				'getCcCidStatus'      => 'Y',
				'getCcExpMonth'       => '12',
				'getCcExpYear'        => '2015',
				'getCcStatus'         => true,
				'getMethod'           => 'Pbridge_eb2cpayment_cc',
				'getId'               => 1,
				'getCreatedAt'        => '2013-10-25 17:06:28',
			)
		);
	}

	/**
 	 * Let us mock a Mage_Sales_Model_Order_Payment
	 *
	 */
	private function _getMockSalesOrderPayment2()
	{
		return $this->_getFullMocker(
			'sales/order_payment',
			array (
				'getAmountAuthorized' => '1776',
				'getCcApproval'       => 'APP123456',
				'getCcAvsStatus'      => 'Z',
				'getCcCidStatus'      => 'Y',
				'getCcExpMonth'       => '12',
				'getCcExpYear'        => '2015',
				'getCcStatus'         => true,
				'getMethod'           => 'Paypal_express',
				'getId'               => 1,
				'getCreatedAt'        => '2013-10-25 17:06:28',
			)
		);
	}

	/**
	 * Replaces the Magento eb2ccore/config_registry model. I.e., this is your config for Eb2cOrder Testing.
	 *
	 * @param array ('statusFeedLocalPath' => 'a/path')	A testable path containing Status Feed XML - prefer that this be vfs
	 */
	public function replaceCoreConfigRegistry($userConfigValuePairs=array())
	{
		$configValuePairs = array (
			// Core Values:
			'clientId'                => 'TAN-OS-CLI',
			'feedDestinationType'     => 'MAILBOX',
			'apiXsdPath'              => '/home/mwest/projects/magento-enterprise/src/app/code/local/TrueAction/Eb2cCore/xsd',

			// Eb2cOrder-specific Values:
			'eb2cPaymentsEnabled'     => true,
			'statusFeedLocalPath'     => 'some_local_path_for_files',
			'statusFeedRemotePath'    => 'doesnt_matter_just_some_path',
			'statusFeedEventType'     => 'OrderStatus',
			'statusFeedHeaderVersion' => '2.3.4',
			'xsdFileCreate'           => 'Order-Service-Create-1.0.xsd',
			'xsdFileCancel'           => 'Order-Service-Cancel-1.0.xsd',
		);

		// Replace and/ or add to the default configValuePairs if the user has supplied some config values
		foreach( $userConfigValuePairs as $configPath => $configValue ) {
			$configValuePairs[$configPath] = $configValue;
		}

		// Build the array in the format returnValueMap wants
		$valueMap = array();
		foreach( $configValuePairs as $configPath => $configValue ) {
			$valueMap[] = array($configPath, $configValue);
		}

		$mockConfig = $this->getModelMock('eb2ccore/config_registry', array('__get'));
		$mockConfig->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($valueMap));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $mockConfig);
	}

	/**
	 * Returns a mocked object
	 *
	 * @param a Magento Class Alias
	 * @param array of key / value pairs; key is the method name, value is value returned by that method
	 * @return mocked-object
	 */
	protected function _getFullMocker($classAlias, $mockedMethodSet, $disableConstructor=true)
	{
		$mockMethodNames = array_keys($mockedMethodSet);
		if( $disableConstructor ) {
			$mock = $this->getModelMockBuilder($classAlias)
				->disableOriginalConstructor()
				->setMethods($mockMethodNames)
				->getMock();
		} else {
			$mock = $this->getModelMockBuilder($classAlias)
				->setMethods($mockMethodNames)
				->getMock();
		}
		foreach($mockedMethodSet as $method => $returnSet ) {
			if ($returnSet === 'self') {
				$mock->expects($this->any())
					->method($method)
					->will($this->returnSelf());
			} else {
				$mock->expects($this->any())
					->method($method)
					->will($this->returnValue($returnSet));
			}

		}
		return $mock;
	}

	/**
	 * Returns a mocked object, original model constructor disabled - you get only the methods you mocked.
	 *
	 * @param a Magento Class Alias
	 * @param array of key / value pairs; key is the method name, value is value returned by that method
	 * @param disableOriginalConstructor	true or false, defaults to true
	 *
	 * @return mocked-object
	 */
	public function replaceModel($classAlias, $mockedMethodSet, $disableOriginalConstructor=true)
	{
		$mock = $this->_getFullMocker($classAlias, $mockedMethodSet, $disableOriginalConstructor);
		$this->replaceByMock('model', $classAlias, $mock);
		return $mock;
	}
}

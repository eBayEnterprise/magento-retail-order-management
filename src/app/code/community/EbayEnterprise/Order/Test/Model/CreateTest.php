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

use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

/**
 * Test Order Create
 */
class EbayEnterprise_Order_Test_Model_CreateTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCreateRequest */
	protected $_request;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest */
	protected $_requestStub;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateReply */
	protected $_replyStub;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelperStub;
	/** @var EbayEnterprise_Order_Model_Observer */
	protected $_observerStub;
	/** @var Mage_Customer_Model_Customer */
	protected $_customer;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;
	/** @var IBidirectionalApi */
	protected $_httpApi;
	/** @var EbayEnterprise_Order_Helper_Item_Selection */
	protected $_itemSelection;

	/** @var string */
	protected $_expectedLevelOfService =
		EbayEnterprise_Order_Model_Create::LEVEL_OF_SERVICE_REGULAR;

	/** @var string */
	protected $_expectedOrderType =
		EbayEnterprise_Order_Model_Create::ORDER_TYPE_SALES;

	/** @var string */
	protected $_expectedRequestIdPrefix = 'OCR-';

	/**
	 * prepare stubs
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_httpApi = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\HttpApi')
			->disableOriginalConstructor() // prevent need to mock IHttpConfig
			->getMock();
		$this->_observerStub = $this->getModelMock('ebayenterprise_order/observer');
		$this->_itemSelection = $this->getHelperMock('ebayenterprise_order/item_selection', ['selectFrom']);
		$this->_payloadFactory = new PayloadFactory;
		$this->_request = $this->_payloadFactory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCreateRequest');
		$this->_requestStub = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest');
		$this->_replyStub = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateReply');
		$this->_coreHelperStub = $this->getHelperMock('eb2ccore/data', ['generateRequestId', 'getConfigModel']);
		$coreConfig = $this->buildCoreConfigRegistry(['clientCustomerIdPrefix' => '12345', 'language_code' => 'en-us']);
		$this->_coreHelperStub->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($coreConfig));
		$this->_config = $this->buildCoreConfigRegistry([
			'levelOfService' => $this->_expectedLevelOfService,
			'orderType' => $this->_expectedOrderType,
			'requestIdPrefix' => $this->_expectedRequestIdPrefix,
			'apiCreateOperation' => 'create',
			'apiService' => 'orders',
			'genderMap' => [
				'Female' => 'F',
				'Male' => 'M',
				'SomeOtherGender' => 'Invalid'
			],
		]);
		$this->_customer = Mage::getModel('customer/customer', ['increment_id' => '12345123456789']);
		$this->_order = Mage::getModel('sales/order', [
			'created_at' => '2014-07-28 16:22:46',
			'customer' => $this->_customer,
			'customer_dob' => '2014-07-28 16:22:46',
			'customer_email' => 'test@example.com',
			'customer_firstname' => 'fname',
			'customer_lastname' => 'lname',
			'customer_middlename' => 'mname',
			'customer_prefix' => 'mr',
			'customer_id' => '123456789',
			'customer_taxvat' => 'taxid',
			'increment_id' => '12345123456789',
		]);
		$this->_item1 = $this->getModelMock('sales/order_item', []);
		$this->_item2 = $this->getModelMock('sales/order_item', []);
		$this->_billAddress = Mage::getModel('sales/order_address', ['address_type' => Mage_Customer_Model_Address_Abstract::TYPE_BILLING]);
		$this->_shipAddress = Mage::getModel('sales/order_address', ['address_type' => Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING]);
		// prevent magento events from actually triggering
		Mage::app()->disableEvents();
	}

	public function tearDown()
	{
		// allow normal event handling
		Mage::app()->enableEvents();
	}

	/**
	 * Provide possible order address types, used to determine how to filter
	 * order items.
	 *
	 * @return array
	 */
	public function provideAddressTypes()
	{
		return [
			['billing'],
			['shipping'],
		];
	}

	/**
	 * Test filtering items by address type. Billing address should be processed
	 * with any virtual items, shipping address with all other items.
	 *
	 * @param string
	 * @dataProvider provideAddressTypes
	 */
	public function testGetItemsForAddress($addressType)
	{
		$address = ($addressType === 'billing') ? $this->_billAddress : $this->_shipAddress;
		$virtualItem = Mage::getModel('sales/order_item', ['is_virtual' => '1']);
		$simpleItem = Mage::getModel('sales/order_item', ['is_virtual' => '0']);
		$this->_order->addItem($virtualItem)->addItem($simpleItem);
		$address->setAddressType($addressType);
		// Stubs
		$api = $this->_httpApi;
		$config = $this->getModelMock('eb2ccore/config_registry');
		$payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest');
		$this->_itemSelection->expects($this->any())
			->method('selectFrom')
			->will($this->returnArgument(0));
		$constructorArgs = [
			'api' => $api,
			'config' => $config,
			'order' => $this->_order,
			'payload' => $payload,
			'item_selection' => $this->_itemSelection,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);

		$itemsForAddress = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_getItemsForAddress',
			[$address, $this->_order->getItemsCollection()]
		);
		$this->assertCount(1, $itemsForAddress);
		if ($addressType === 'billing') {
			$this->assertSame($virtualItem, $itemsForAddress[0]);
		} else {
			$this->assertSame($simpleItem, $itemsForAddress[0]);
		}
	}

	/**
	 * When getting items for an address, only items that should be included
	 * in the order create request should be returned.
	 */
	public function testGetItemsForAddressFiltering()
	{
		// Create an item that is exptected to be excluded from the OCR and
		// an items that should be included.
		$excludedItem = Mage::getModel('sales/order_item', ['is_virtual' => '0']);
		$includedItem = Mage::getModel('sales/order_item', ['is_virtual' => '0']);
		$this->_order->addItem($excludedItem)->addItem($includedItem);
		// Stubs
		$api = $this->_httpApi;
		$config = $this->getModelMock('eb2ccore/config_registry');
		$payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest');
		$constructorArgs = [
			'api' => $api,
			'config' => $config,
			'order' => $this->_order,
			'payload' => $payload,
			'item_selection' => $this->_itemSelection,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);

		// Mock the item selection helper such that if given the array of items
		// that belong to the address - excluded and included items - that only
		// the items that should be included are returned.
		$this->_itemSelection->expects($this->any())
			->method('selectFrom')
			->with($this->identicalTo([$excludedItem, $includedItem]))
			->will($this->returnValue([$includedItem]));
		$itemsForAddress = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_getItemsForAddress',
			[$this->_shipAddress, $this->_order->getItemsCollection()]
		);
		$this->assertCount(1, $itemsForAddress);
		$this->assertSame($includedItem, $itemsForAddress[0]);
	}

	public function provideAddressData()
	{
		$address = [
			'firstname' => 'First',
			'lastname' => 'Last',
			'middlename' => 'Middle',
			'prefix' => 'Mrs.',
			'lines' => '123 Main St',
			'city' => 'King of Prussia',
			'main_division' => 'PA',
			'country_code' => 'US',
			'postal_code' => '19406',
			'phone' => '555-555-5555',
			'email' => 'test@example.com',
		];

		return [
			[array_merge($address, ['address_type' => 'billing'])],
			[array_merge($address, ['address_type' => 'shipping'])],
		];
	}

	/**
	 * @param string
	 * @dataProvider provideAddressData
	 */
	public function testBuildDefaultDestination($addressData)
	{
		$address = Mage::getModel('sales/order_address', $addressData);
		$constructorArgs = [
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);
		$destination = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_buildDefaultDestination',
			[$address, $this->_request->getDestinations()]
		);
		$expectedType = $address->getAddressType() === 'billing' ?
			'\eBayEnterprise\RetailOrderManagement\Payload\Order\IEmailAddressDestination' :
			'\eBayEnterprise\RetailOrderManagement\Payload\Order\IMailingAddress';
		$this->assertInstanceOf($expectedType, $destination);
	}

	/**
	 * To build a ship group, an event should be dispatched for the address and
	 * ship group. The ship group returned must have a destination and shipping
	 * charge type set.
	 */
	public function testBuildShipGroupForAddressEventDispatch()
	{
		$items = [];
		$shipGroups = $this->_request->getShipGroups();
		$destinations = $this->_request->getDestinations();
		$orderItems = $this->_request->getOrderItems();
		$constructorArgs = [
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);
		$shipGroup = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_buildShipGroupForAddress',
			[$this->_shipAddress, $items, $this->_order, $shipGroups, $destinations, $orderItems]
		);
		$this->assertEventDispatchedExactly('ebayenterprise_order_create_ship_group', 1);
		$this->assertInstanceOf(
			'\eBayEnterprise\RetailOrderManagement\Payload\Order\IDestination',
			$shipGroup->getDestination()
		);
	}

	/**
	 * Ensure that when no event handlers add a destination to the ship group that
	 * a default destination is still added. Other tests will validate that the
	 * proper type of destination is added so this test only needs to ensure
	 * that a destination has been added.
	 */
	public function testBuildShipGroupForAddressDefaultHandling()
	{
		$items = [];
		$shipGroups = $this->_request->getShipGroups();
		$destinations = $this->_request->getDestinations();
		$orderItems = $this->_request->getOrderItems();
		$constructorArgs = [
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);
		$shipGroup = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_buildShipGroupForAddress',
			[$this->_shipAddress, $items, $this->_order, $shipGroups, $destinations, $orderItems]
		);
		// Ensure there is a destination set.
		$this->assertInstanceOf(
			'\eBayEnterprise\RetailOrderManagement\Payload\Order\IDestination',
			$shipGroup->getDestination()
		);
	}

	/**
	 * For each item given, a new order item payload should be created,
	 * dispatched in an event with the Magento order item, and added to an
	 * item reference payload in the order item reference container.
	 */
	public function testAddOrderItemReferences()
	{
		// stub the event observer so the orderitem handler doesn't interfere with the test
		$this->replaceByMock('model', 'ebayenterprise_order/observer', $this->_observerStub);
		$itemRefContainer = $this->_request->getShipGroups()->getEmptyShipGroup();
		$items = [Mage::getModel('sales/order_item'), Mage::getModel('sales/order_item')];
		$orderItems = $this->_request->getOrderItems();
		$constructorArgs = [
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
			->disableOriginalConstructor()
			->getMock();
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_defaultItemHandler', $handler);
		$references = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create,
			'_addOrderItemReferences',
			[$itemRefContainer, $items, $orderItems, $this->_shipAddress, $this->_order]
		);
		// Should be one item reference and one event dispatched for each item.
		$expectedCount = count($items);
		$this->assertCount($expectedCount, $references->getItemReferences());
		$this->assertEventDispatchedExactly('ebayenterprise_order_create_item', $expectedCount);
	}

	public function provideCustomerGenderValues()
	{
		return [
			[1, 'M'],
			[2, 'F'],
			[3, null],
			[4, null],
		];
	}

	/**
	 * verify
	 * - customer gender is mapped to one of the values ROM expects
	 * - customer gender is null if mapped to an invalid value or is not set
	 * @param  int    $customerGender
	 * @param  string $expected
	 * @dataProvider provideCustomerGenderValues
	 */
	public function testGetCustomerGender($customerGender, $expected)
	{
		$customerResource = $this->getResourceModelMock('customer/customer', ['getAttribute', 'getSource', 'getAllOptions']);
		$this->replaceByMock('resource_model', 'customer/customer', $customerResource);
		$customerResource->expects($this->any())
			->method('getAttribute')
			->will($this->returnSelf());
		$customerResource->expects($this->any())
			->method('getSource')
			->will($this->returnSelf());
		$customerResource->expects($this->any())
			->method('getAllOptions')
			->will($this->returnValue([
				['value' => 1, 'label' => 'Male'],
				['value' => 2, 'label' => 'Female'],
				['value' => 3, 'label' => 'SomeOtherGender'],
			]));
		$order = Mage::getModel('sales/order', ['customer_gender' => $customerGender]);
		$constructorArgs = [
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$create = Mage::getModel('ebayenterprise_order/create', $constructorArgs);
		$this->assertSame(
			$expected,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$create, '_getCustomerGender', [$order]
			)
		);
	}

	/**
	 * when checking out as a guest the the encrypted session id will be used as
	 * the customer id.
	 * When logged in, use the customer's increment id
	 * @param  bool $isGuest
	 * @dataProvider provideTrueFalse
	 */
	public function testGetCustomerId($isGuest)
	{
		if ($isGuest) {
			$this->_order->setCustomerId(null);
		}
		$session = $this->getModelMockBuilder('customer/session')
			// prevent extraneous mocking
			->disableOriginalConstructor()
			->setMethods(['getEncryptedSessionId'])
			->getMock();
		$constructorArgs = [
			'core_helper' => $this->_coreHelperStub,
			'api' => $this->_httpApi,
			'config' => $this->_config,
			'order' => $this->_order,
			'payload' => $this->_requestStub,
		];
		$create = $this->getModelMock('ebayenterprise_order/create', ['_getCustomerSession'], false, [$constructorArgs]);
		$create->expects($this->any())
			->method('_getCustomerSession')
			->will($this->returnValue($session));
		$session->expects($this->any())
			->method('getEncryptedSessionId')
			->will($this->returnValue('sessid'));
		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_getCustomerId');
		$hashedSessionId = hash('sha256', 'sessid');
		$this->assertLessThan(41, strlen($result));
		$this->assertSame($isGuest ? substr($hashedSessionId, 0, 35) : '123456789', substr($result, 5));
	}
}

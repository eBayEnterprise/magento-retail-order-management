<?php

class TrueAction_Eb2cAddress_Test_Model_ValidatorTest
	extends EcomDev_PHPUnit_Test_Case
{

	public function setUp()
	{
		parent::setUp();
		$this->_mockCoreHelper();
		$this->_mockApiModel();
		$this->_mockCustomerSession();
		$this->_mockCheckoutSession();
	}

	/**
	 * Create an address object to pass off to the validator.
	 * @return Mage_Customer_Model_Address
	 */
	protected function _createAddress($fields=array())
	{
		$addr = Mage::getModel('customer/address');
		$addr->setData($fields);
		return $addr;
	}

	/**
	 * Replace the eb2ccore/api model with a mock
	 * @param boolean $emptyResponse
	 */
	protected function _mockApiModel($emptyResponse=false)
	{
		$mock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$mock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('request')
			->will($this->returnValue($emptyResponse ? '' : '
<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
</AddressValidationResponse>
				'));
		$this->replaceByMock('model', 'eb2ccore/api', $mock);
		return $mock;
	}

	/**
	 * Replace the eb2ccore/data helper class with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock helper
	 */
	protected function _mockCoreHelper($emptyResponse=false)
	{
		$mockCoreHelper = $this->getHelperMock(
			'eb2ccore/data',
			array('apiUri')
		);
		$mockCoreHelper->expects($this->any())
			->method('apiUri')
			->will($this->returnValue('https://does.not.matter/as/this/isnot/actually/used.xml'));
		$this->replaceByMock('helper', 'eb2ccore', $mockCoreHelper);
		return $mockCoreHelper;
	}

	/**
	 * Replace the customer session object with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
	 */
	protected function _mockCustomerSession()
	{
		$sessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(null) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$this->replaceByMock('singleton', 'customer/session', $sessionMock);
		return $sessionMock;
	}

	/**
	 * Replace the customer session object with a mock.
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
	 */
	protected function _mockCheckoutSession()
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(null) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
		return $sessionMock;
	}

	/**
	 * Replace the eb2caddress/validation_response model with a mock
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock respose model
	 */
	protected function _mockValidationResponse(
		$isAddressValid=false,
		$hasSuggestions=false,
		$originalAddress=null,
		$validAddress=null,
		$addressSuggestions=array()
	)
	{
		$respMock = $this->getModelMock(
			'eb2caddress/validation_response',
			array('setMessage', 'isAddressValid', 'getValidAddress',
				'getOriginalAddress','hasAddressSuggestions', 'getAddressSuggestions'
			)
		);
		$respMock->expects($this->any())
			->method('isAddressValid')
			->will($this->returnValue($isAddressValid));
		$respMock->expects($this->any())
			->method('hasAddressSuggestions')
			->will($this->returnValue($hasSuggestions));
		$respMock->expects($this->any())
			->method('getOriginalAddress')
			->will($this->returnValue($originalAddress));
		$respMock->expects($this->any())
			->method('getValidAddress')
			->will($this->returnValue($validAddress));
		$respMock->expects($this->any())
			->method('getAddressSuggestions')
			->will($this->returnValue($addressSuggestions));
		$respMock->expects($this->any())
			->method('setMessage')
			->will($this->returnValue($respMock));
		$this->replaceByMock('model', 'eb2caddress/validation_response', $respMock);
		return $respMock;
	}

	/**
	 * Get the session object used by address validation.
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Setup the session data with the supplied addresses.
	 * @param Mage_Customer_Model_Address_Abstract $original
	 * @param Mage_Customer_Model_Address_Abstract[] $suggestions
	 * @param boolean $hasFreshSuggestions
	 */
	protected function _setupSessionWithSuggestions($original, $suggestions, $hasFreshSuggestions=true)
	{
		// populate the session with usable data - replaced with mock in setUp
		$addresses = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addresses->setOriginalAddress($original);
		$addresses->setSuggestedAddresses($suggestions);
		$addresses->setHasFreshSuggestions($hasFreshSuggestions);
		$this->_getSession()
			->setData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY, $addresses);
	}

	/**
	 * Test the various session interactions when there is not session data
	 * for address validation initialized.
	 * @test
	 */
	public function testValidatorSessionNotInitialized()
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertNull($validator->getOriginalAddress());
		$this->assertNull($validator->getSuggestedAddresses());
		$this->assertNull($validator->getStashedAddressByKey('original_address'));
		$this->assertFalse($validator->hasSuggestions());
	}

	/**
	 * Make sure getAddressCollection *always* returns a
	 * TrueAction_Eb2cAddress_Model_Suggestion_Group even if the
	 * session data where it expects to find the suggestions is polluted
	 * with something else.
	 * @test
	 */
	public function testGettingAddressCollectionAlwaysReturnsProperObjectType()
	{
		$expectedType = 'TrueAction_Eb2cAddress_Model_Suggestion_Group';
		$session = $this->_getSession();
		$sessionKey = TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY;

		$validator = Mage::getModel('eb2caddress/validator');

		$session->setData($sessionKey, 'a string');
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, new Varien_Object());
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, 23);
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->setData($sessionKey, new TrueAction_Eb2cAddress_Model_Suggestion_Group());
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());

		$session->unsetData($sessionKey);
		$this->assertInstanceOf($expectedType, $validator->getAddressCollection());
	}

	/**
	 * Test updating the submitted address with data from the chosen suggestion.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testUpdateAddressWithSelections($postValue)
	{
		$originalAddress = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		));
		$suggestions = array(
			$this->_createAddress(array(
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			)),
			$this->_createAddress(array(
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			))
		);
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		// set the submitted value in the request post data
		Mage::app()->getRequest()->setPost(TrueAction_Eb2cAddress_Block_Suggestions::SUGGESTION_INPUT_NAME, $postValue);

		// create an address object to act as the address submitted by the user
		$submittedAddress = $this->_createAddress(array(
			'street' => '1 Street Rd',
			'city' => 'Foo',
			'region_code' => 'PA',
			'region_id' => 51,
			'country_id' => 'US',
			'postcode' => '23456',
		));

		// this is necessary due to expectation not allowing a / in the key to get expectations
		$expectationKey = str_replace('/', '', $postValue);

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_updateAddressWithSelection');
		$method->setAccessible(true);

		$updated = $method->invoke($validator, $submittedAddress);

		$this->assertSame(
			$this->expected($expectationKey)->getStreet1(),
			$submittedAddress->getStreet1()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getCity(),
			$submittedAddress->getCity()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getPostcode(),
			$submittedAddress->getPostcode()
		);
		$this->assertSame(
			$this->expected($expectationKey)->getHasBeenValidated(),
			$submittedAddress->getHasBeenValidated()
		);
	}

	/**
	 * Test validation when the response indicates the address is correct
	 * and there are no suggestions or changes.
	 * @test
	 */
	public function testValidateAddressVerified()
	{
		// address to feed to the validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021-9523',
			'has_been_validated' => true,
		));
		// original address from the response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13021',
		));

		$mockResponse = $this->_mockValidationResponse(true, false, $address, $address);
		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($origAddress);
		$this->assertNull($errorMessage, 'Validation of a valid address produces no errors.');
		$this->assertTrue($origAddress->getHasBeenValidated(), 'has_been_validated "magic" data set on the address that has been validated.');
		$this->assertSame($origAddress->getStreet1(), '1671 Clark Street Rd');
		$this->assertSame($origAddress->getCity(), 'Auburn');
		$this->assertSame($origAddress->getRegionCode(), 'NY');
		$this->assertSame($origAddress->getCountryId(), 'US');
		$this->assertSame($origAddress->getPostcode(), '13021-9523', 'Corrections from address validation applied to the "original" address.');
	}

	/**
	 * Test validation when the response indicates the address is not correct
	 * and multiple suggestions are available.
	 * @test
	 */
	public function testValidateAddressMultiSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025-1234',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);
		$this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($address);

		$this->assertTrue(
			$address->getHasBeenValidated(),
			'"has_been_validated" data added to the address object.'
		);
		$this->assertSame(
			$origAddress->getPostcode(),
			$address->getPostcode(),
			'Corrected data in the "original_address" fields from EB2C should still be copied over.'
		);
		$this->assertSame(
			TrueAction_Eb2cAddress_Model_Validator::SUGGESTIONS_ERROR_MESSAGE,
			$errorMessage,
			'Invalid address with suggestions should have the appropriate message.'
		);
	}

	/**
	 * If a previously validated address is passed to the validate method,
	 * validation should assume the address is correct and
	 * should be successful (no errors returned) and
	 * should clear out any session data.
	 * @test
	 */
	public function testWithValidatedAddress()
	{
		$address = $this->_createAddress(array(
			'has_been_validated' => true
		));

		// add some data into the customer session mock.
		$session = $this->_getSession();
		$session->setData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY, 'this should be cleared out');
		$validator = Mage::getModel('eb2caddress/validator');
		$errors = $validator->validateAddress($address);

		$this->assertNull($errors, 'An address that is marked as already having been validated is assumed valid, hence no errors.');
		$this->assertInstanceOf('TrueAction_Eb2cAddress_Model_Suggestion_Group', $session->getData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY));
	}

	/**
	 * Test the session interactions of the validation.
	 * Test for when - address does not need to be validated/has already been validated
	 * and is a billing/use for shipping address.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testSessionInteractionsNoValidationNecessary($useForShipping)
	{
		$address = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Foo',
			'region_id' => 51,
			'country_id' => 'US',
			'postcode' => '12345',
			'address_type' => 'billing',
			'has_been_validated' => true
		));

		Mage::app()->getRequest()->setPost('billing', array('use_for_shipping' => $useForShipping));

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		// inspect session to make sure everything that needed to get set was
		// properly set
		$session = $this->_getSession();
		$group = $session->getData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY);

		$this->assertNotNull($group);

		// as the address was not validated/already valid, there shouldn't be
		// any address data on the group object
		// make retrieving address/suggestions don't change the has_fresh_suggestions flag
		$this->assertNull($group->getOriginalAddress(true));
		$this->assertNull($group->getSuggestedAddresses(true));
		$this->assertNull($group->getResponseMessage());
		$this->assertFalse($group->getHasFreshSuggestions());

		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('billing'));
		if ($useForShipping) {
			$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('shipping'));
		}

	}

	/**
	 * Test session interaction.
	 * Test for when - address is invalid
	 * @test
	 */
	public function testSessionInteractionsInvalidAddress()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'firstname' => 'Foo',
			'lastname' => 'Bar',
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'address_type' => 'shipping'
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);

		// invalid response, with suggestions, original address, no valid address and suggestions
		$mockResponse = $this->_mockValidationResponse(
			false, true, $origAddress, null, $suggestions
		);

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		$session = $this->_getSession();
		$group = $session->getData(TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY);

		$this->assertTrue($group->getHasFreshSuggestions());

		$originalAddress = $group->getOriginalAddress(true);
		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $originalAddress);
		// ensure the stash key was added to the session object
		$this->assertSame('original_address', $originalAddress->getStashKey());
		// ensure name data was copied over to the address from the response
		$this->assertSame($address->getName(), $originalAddress->getName());

		$suggestions = $group->getSuggestedAddresses(true);
		// should have 2 suggestions from the response
		$this->assertEquals(2, count($suggestions));
		// make sure stash keys were added
		$this->assertSame('suggested_addresses/0', $suggestions[0]->getStashKey());
		$this->assertSame('suggested_addresses/1', $suggestions[1]->getStashKey());
		// make sure name data was all copied over
		$this->assertSame($address->getName(), $suggestions[0]->getName());
		$this->assertSame($address->getName(), $suggestions[1]->getName());

		// the response message should have been added to the session in case it needs to
		// be queried for address data again later
		$this->assertSame($mockResponse, $group->getResponseMessage());

		// make sure the validated address data was added to the session
		// should have a shipping address but no billing address
		$this->assertInstanceOf('Mage_Customer_Model_Address_Abstract', $group->getValidatedAddress('shipping'));
		$this->assertNull($group->getValidatedAddress('billing'));
	}

	/**
	 * Test the removal of session values.
	 * @test
	 */
	public function testCleaningOfSession()
	{
		$mockSession = $this->_getSession();
		$validator = Mage::getModel('eb2caddress/validator');
		$staleData = 'STALE_DATA';
		$mockSession->setAddressValidationAddresses($staleData);
		$this->assertNotNull($mockSession->getAddressValidationAddresses(), 'Session has address validation data.');
		$validator->clearSessionAddresses();
		$this->assertNull($mockSession->getAddressValidationAddresses(), 'Session does not have address valdidation data.');
	}

	/**
	 * Asking the validator model to validate a new address should clear out
	 * any values it has populated in the session.
	 * @test
	 */
	public function ensureSessionClearedOnNewValidation()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		// suggestions from the response model
		$suggestions = array(
			$this->_createAddress(array(
				'street' => 'Suggestion 1 Line 1',
				'city' => 'Suggestion 1 City',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-9876',
				'has_been_validated' => true,
			)),
			$this->_createAddress(array(
				'street' => '1671 W Clark Street Rd',
				'city' => 'Auburn',
				'region_id' => 'NY',
				'country_id' => 'US',
				'postcode' => '13021-1234',
				'has_been_validated' => true,
			)),
		);
		$mockResponse = $this->_mockValidationResponse(false, true, $origAddress, null, $suggestions);

		// set up some stale data in the session that should be overwritten by the validator model
		$staleSessionData = 'STALE_DATA';
		$sessionKey = TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY;
		$mockSession = $this->_getSession();
		$mockSession->setData($sessionKey, $staleSessionData);
		// make sure it has been set
		$this->assertSame($staleSessionData, $mockSession->getData($sessionKey), 'Session has initial address validation data.');

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);

		$this->assertNotEquals(
			$staleSessionData,
			$mockSession->getData($sessionKey),
			'Stale session data replaced by new address validation data.'
		);
	}

	/**
	 * This is a very odd scenario and really should never happen.
	 * @test
	 */
	public function errorMessageWithInvalidMessageAndNoSuggestions()
	{
		// address to feed to validator
		$address = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		// original address from response model
		$origAddress = $this->_createAddress(array(
			'street' => '1671 Clark Street Rd',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
			'has_been_validated' => true,
		));
		$mockResponse = $this->_mockValidationResponse(false, false, $origAddress, null);

		$validator = Mage::getModel('eb2caddress/validator');
		$errorMessage = $validator->validateAddress($address);

		$this->assertSame(
			$errorMessage,
			TrueAction_Eb2cAddress_Model_Validator::NO_SUGGESTIONS_ERROR_MESSAGE
		);
	}

	/**
	 * Test that when no message is received from the address validation service
	 * the address is considered valid.
	 * @test
	 */
	public function testEmptyReponseFromService()
	{
		$this->_mockApiModel(true);
		// address to validate
		$address = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Auburn',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '13025',
		));
		$validator = Mage::getModel('eb2caddress/validator');
		$errors = $validator->validateAddress($address);

		$this->assertNull($errors);
	}

	/**
	 * When the API model's request method throws an error, validation should
	 * be considered successful.
	 * @test
	 */
	public function testErrorResponseFromService()
	{
		$mock = $this->getModelMock(
			'eb2ccore/api',
			array('setUri', 'request')
		);
		$mock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$mock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception()));
		$this->replaceByMock('model', 'eb2ccore/api', $mock);

		$address = Mage::getModel('customer/address');

		$validator = Mage::getModel('eb2caddress/validator');
		$errors = $validator->validateAddress($address);

		$this->assertNull($errors);
	}

	/**
	 * Test retrieval of address objects from the validator by key.
	 * Each address should have a stash_key which will be used to get
	 * the address back out of the address collection stored in the session.
	 * @test
	 */
	public function getStashedAddressByKeyByKey()
	{
		$originalAddress = $this->_createAddress(array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_id' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address',
		));
		$suggestions = array(
			$this->_createAddress(array(
				'street' => '321 Main Rd',
				'city' => 'Barton',
				'region_code' => 'NY',
				'country_id' => 'US',
				'postcode' => '54321-1234',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/0',
			)),
			$this->_createAddress(array(
				'street' => '321 Main St',
				'city' => 'Fooville',
				'country_id' => 'US',
				'postcode' => '12345-6789',
				'has_been_validated' => true,
				'stash_key' => 'suggested_addresses/1',
			))
		);
		$this->_setupSessionWithSuggestions($originalAddress, $suggestions);

		$validator = Mage::getModel('eb2caddress/validator');

		$this->assertSame(
			$originalAddress,
			$validator->getStashedAddressByKey($originalAddress->getStashKey())
		);
		$this->assertSame(
			$suggestions[1],
			$validator->getStashedAddressByKey($suggestions[1]->getStashKey())
		);
	}

	/**
	 * Trying to get a validated address with an unknown key will return null
	 * @test
	 */
	public function gettingValidatedAddressByUnknownKey()
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertNull($validator->getStashedAddressByKey('dont_know_about_this'), 'Unknown "stash_key" results in "null" response.');
	}

	/**
	 * Test getting the original address out of the session
	 * @test
	 */
	public function testGetOriginalAddress()
	{
		// set up an address object to be put into the session
		$origAddress = $this->_createAddress((array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345',
			'has_been_validated' => true,
			'stash_key' => 'original_address'
		)));
		// create the TrueAction_Eb2cAddress_Model_Suggestion_Group that stores address data in the session
		$addresses = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addresses->setOriginalAddress($origAddress);
		// add the address data to the session
		$this->_getSession()->setData(
			TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY,
			$addresses
		);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$origAddress,
			$validator->getOriginalAddress()
		);
	}

	/**
	 * Test getting the list of suggested addresses.
	 * @test
	 */
	public function testGetSuggestedAddresses()
	{
		// set up suggested addresses to place into the session
		$suggestions = array(
			$this->_createAddress(
				array(
					'street' => '123 Main St',
					'city' => 'Fooville',
					'region_code' => 'NY',
					'country_code' => 'US',
					'postcode' => '12345',
				),
				array(
					'street' => '321 Main Rd',
					'city' => 'Bartown',
					'region_code' => 'PA',
					'country_code' => 'US',
					'postcode' => '19231',
				)
			)
		);
		// create the TrueAction_Eb2cAddress_Model_Suggestion_Group that stores the address data in the session
		$addressCollection = new TrueAction_Eb2cAddress_Model_Suggestion_Group();
		$addressCollection->setSuggestedAddresses($suggestions);
		// add suggestions to the session
		$this->_getSession()->setData(
			TrueAction_Eb2cAddress_Model_Validator::SESSION_KEY,
			$addressCollection
		);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$suggestions,
			$validator->getSuggestedAddresses()
		);
	}

	/**
	 * Test copying name data form the source address to the destination address.
	 * @test
	 */
	public function testCopyNameData()
	{
		$destData = array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_code' => 'PA',
			'country_code' => 'US',
			'postcode' => '12345',
		);
		$destAddr = $this->_createAddress($destData);
		$sourceData = array(
			'firstname' => 'The',
			'lastname' => 'Bar',
			'street' => '555 Foo St',
			'city' => 'Bartown',
			'region_code' => 'NY',
			'country_code' => 'US',
			'postcode' => '12345-6789',
		);
		$sourceAddr = $this->_createAddress($sourceData);

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_copyAddressName');
		$method->setAccessible(true);

		$mergedClone = $method->invoke($validator, $destAddr, $sourceAddr);

		// first and last name should be copied to the dest address
		$this->assertSame($sourceAddr->getFirstname(), $destAddr->getFirstname());
		$this->assertSame($sourceAddr->getLastname(), $destAddr->getLastname());
		// rest of the dest address should not have changed
		$this->assertSame($destData['city'], $destAddr->getCity());
		$this->assertSame($destData['postcode'], $destAddr->getPostcode());
	}

	/**
	 * Test checking for fresh suggestions.
	 * Really just a pass through to another object which is responsible for managing the flag.
	 * @test
	 */
	public function testCheckSessionFreshness()
	{
		$this->_setupSessionWithSuggestions(null, null, false);
		$this->assertFalse(Mage::getModel('eb2caddress/validator')->hasFreshSuggestions());
	}

	/**
	 * Test the test for there to be suggestions.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testHasSuggestions($shouldHaveSuggestions)
	{
		// something to populate the Suggestion_Group suggestions with,
		// anything not empty will produce a true result for hasSuggestions
		$suggestions = ($shouldHaveSuggestions)
			? array(Mage::getModel('customer/address'), Mage::getModel('customer/address'))
			: array();
		$group = $this->getModelMock('eb2caddress/suggestion_group',
			array('getSuggestedAddresses')
		);
		$group->expects($this->once())
			->method('getSuggestedAddresses')
			->with($this->equalTo(true))
			->will($this->returnValue($suggestions));
		$this->replaceByMock('model', 'eb2caddress/suggestion_group', $group);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertEquals($shouldHaveSuggestions, $validator->hasSuggestions());
	}

	/**
	 * Test the should validate method for addresses that have already been validated,
	 * in particular, addresses that have had a 'has_been_validated' flag set,
	 * e.g. addresses that have been extracted from the EB2C response message.
	 * @test
	 */
	public function testShouldValidateFlaggedAsValidated()
	{
		$address = $this->getModelMock('customer/address', array('getHasBeenValidated'));
		$address->expects($this->once())
			->method('getHasBeenValidated')
			->will($this->returnValue(true));
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertFalse($validator->shouldValidateAddress($address));
	}

	/**
	 * Test the should validate method for addresses that have already been validated,
	 * in particular addresses that match an address of the same type that has already
	 * been validated. This check is primarilly used for detecting superflous calls
	 * to the validateAddress method. Tests any scenarios that will result in a false response
	 * indicating the address has already been validated and should not be validated.
	 *
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testShouldValidatedAlreadyValidatedAddress($addressType, $postBillingUseForShipping, $validatedType)
	{
		$address = $this->getModelMock('customer/address', array('getHasBeenValidated', 'getAddressType', 'getData'));
		$address->expects($this->any())
			->method('getHasBeenValidated')
			->will($this->returnValue(false));
		$address->expects($this->any())
			->method('getAddressType')
			->will($this->returnValue($addressType));
		$addressData = array(
			array('street',    null, '123 Main St'),
			array('city',      null, 'Fooville'),
			array('region_id', null, 51),
		);
		$address->expects($this->any())
			->method('getData')
			->will($this->returnValueMap($addressData));

		// setup the POST data accordingly
		if ($postBillingUseForShipping !== null) {
			$_POST['billing'] = array('use_for_shipping' => $postBillingUseForShipping);
		}

		$group = $this->getModelMock('eb2caddress/suggestion_group', array('getValidatedAddress'));
		$validatedAddress = $this->getModelMock('customer/address', array('getData'));
		$validatedAddressData = array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_id' => '51',
			'address_type' => 'shipping',
		);
		$validatedAddress->expects($this->any())
			->method('getData')
			->will($this->returnValue($validatedAddressData));
		$group->expects($this->any())
			->method('getValidatedAddress')
			->with($this->equalTo($validatedType))
			->will($this->returnValue($validatedAddress));
		$this->replaceByMock('model', 'eb2caddress/suggestion_group', $group);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertFalse($validator->shouldValidateAddress($address));
	}

	/**
	 * Test that an address should be validated when:
	 * - not a checkout address
	 * - and has not yet been validated.
	 * @test
	 */
	public function testShouldValidateNonCheckouNotValidated()
	{
		$address = $this->getModelMock('customer/address', array('getHasBeenValidated', 'getAddressType', 'getData'));
		$address->expects($this->any())
			->method('getHasBeenValidated')
			->will($this->returnValue(false));
		$address->expects($this->any())
			->method('getAddressType')
			->will($this->returnValue('shipping'));
		// any one of these being different from the validatedAddressData below
		// will cause the comparison check fail
		$addressData = array(
			array('street',    null, 'Borg'),
			array('city',      null, 'Barton'),
			array('region_id', null, 41),
		);
		$address->expects($this->any())
			->method('getData')
			->will($this->returnValueMap($addressData));

		$group = $this->getModelMock('eb2caddress/suggestion_group', array('getValidatedAddress'));
		$validatedAddress = $this->getModelMock('customer/address', array('getData'));
		$validatedAddressData = array(
			'street' => '123 Main St',
			'city' => 'Fooville',
			'region_id' => '51',
			'address_type' => 'shipping',
		);
		$validatedAddress->expects($this->any())
			->method('getData')
			->will($this->returnValue($validatedAddressData));
		$group->expects($this->any())
			->method('getValidatedAddress')
			->will($this->returnValue($validatedAddress));
		$this->replaceByMock('model', 'eb2caddress/suggestion_group', $group);

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertTrue($validator->shouldValidateAddress($address));
	}

	/**
	 * Test detecting that an address is being used in checkout.
	 * @test
	 */
	public function testDetectingACheckoutAddress()
	{
		$address = Mage::getModel('customer/address');
		$address->addData(array(
			'street' => '123 Main St',
			'city' => 'Foo',
			'region_id' => 41,
			'country_id' => 'US',
		));

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_isCheckoutAddress');
		$method->setAccessible(true);

		// checkout address will have a quote_id
		$address->setData('quote_id', 12);
		$this->assertTrue($method->invoke($validator, $address));

		// non-checkout address will not
		$address->unsetData('quote_id');
		$this->assertFalse($method->invoke($validator, $address));
	}

	/**
	 * Test that address being loaded from the address book are not validated when
	 * used in checkout.
	 * @dataProvider dataProvider
	 * @test
	 * @long
	 */
	public function testAddressBookAddressShouldNotBeValidated($id, $customerId, $customerAddressId)
	{
		$address = $this->getModelMock('customer/address', array('hasData', 'getId', 'getCustomerId', 'getCustomerAddressId'));
		// make sure this is a checkout address
		$address->expects($this->any())
			->method('hasData')
			->with($this->matches('quote_id'))
			->will($this->returnValue(1));
		$address->expects($this->any())
			->method('getId')
			->will($this->returnValue($id));
		$address->expects($this->any())
			->method('getCustomerId')
			->will($this->returnValue($customerId));
		$address->expects($this->any())
			->method('getCustomerAddressId')
			->will($this->returnValue($customerAddressId));

		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertEquals(
			!($id && $customerId && $customerAddressId),
			$validator->shouldValidateAddress($address)
		);
	}

	/**
	 * Check for an address that is marked to be saved in the users address book.
	 * As address that are saved get validated, which is the normal case,
	 * in order to ensure unique results for addresses being saved, we need to force
	 * one other the other, non-validation triggers to catch. Most direct way is likely by
	 * marking the order as a virtual order. As a result,
	 * this also tests should validate for virtual orders.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testAddressBeingSavedInAddressBookAndIsVirtual($postType, $postFlag, $checkoutMethod)
	{
		$address = Mage::getModel('customer/address');
		// address must be checkout address - have a quote_id,
		// and not be from the address book - no id, customer_id or customer_address_id
		$address->addData(array(
			'quote_id' => 12,
			'address_type' => 'shipping',
		));

		// set up the checkout session with a quote mock which will report
		// it as being a virtual order
		$quote = $this->getModelMock('sales/quote', array('isVirtual'));
		$quote->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(array('getQuote')) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$checkout->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$this->replaceByMock('singleton', 'checkout/session', $checkout);

		/*
		 * set up the post data necessary, there needs to be either a
		 * shipping[save_in_address_book] or billing[save_in_address_book] submitted
		 */
		if ($postType === 'billing' || $postType === 'shipping') {
			$_POST[$postType] = array('save_in_address_book' => $postFlag);
		}

		// set up the checkout type
		$onepage = $this->getModelMock('checkout/type_onepage', array('getCheckoutMethod'));
		$onepage->expects($this->once())
			->method('getCheckoutMethod')
			->will($this->returnValue($checkoutMethod));
		$this->replaceByMock('singleton', 'checkout/type_onepage', $onepage);

		$expectation = $this->expected('%s-%s-%s', $postType, $postFlag, $checkoutMethod);
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$expectation->getShouldValidateAddress(),
			$validator->shouldValidateAddress($address)
		);
	}

	/**
	 * When there is an order present, method should get the isVirtual result
	 * from it, otherwise should return false.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testVirtualQuoteAddress($hasQuote, $isVirtual)
	{
		$quote = null;
		if ($hasQuote) {
			$quote = $this->getModelMock('sales/quote', array('isVirtual'));
			$quote->expects($this->once())
				->method('isVirtual')
				->will($this->returnValue($isVirtual));
		}
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('getQuote'))
			->getMock();
		$sessionMock->expects($this->once())
			->method('getQuote')
			->will($this->returnValue($quote));
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);

		$validator = Mage::getModel('eb2caddress/validator');
		$reflection = new ReflectionClass('TrueAction_Eb2cAddress_Model_Validator');
		$method = $reflection->getMethod('_isVirtualOrder');
		$method->setAccessible(true);

		$this->assertSame($isVirtual, $method->invoke($validator));
	}

	/**
	 * Detect that an address is only used for billing and does not
	 * need to be validated.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testShouldValidateBillingOnlyAddress($addressType, $hasBillingPost, $useForShipping)
	{
		$address = Mage::getModel('customer/address');
		// must be checkout address - have quote_id
		$address->addData(array(
			'quote_id' => 1,
			'address_type' => $addressType
		));
		if ($hasBillingPost) {
			$_POST['billing'] = array('use_for_shipping' => $useForShipping);
		}

		$expectations = $this->expected('%s-%s-%s', $addressType, $hasBillingPost, $useForShipping);
		$validator = Mage::getModel('eb2caddress/validator');
		$this->assertSame(
			$expectations->getShouldValidate(),
			$validator->shouldValidateAddress($address)
		);
	}

	/**
	 * When a shipping address is marked same_as_billing but is not valid, the
	 * address should no longer be marked same_as_billing. This is part of the
	 * means of preventing a non-validated billing only address from
	 * being used as a shipping address and bypassing validation.
	 *
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testSetSameAsBillingFlagWhenAddressIsInvalid($isValid, $sameAsBilling)
	{
		// mock out necessary parts of the system
		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$api->expects($this->any())
			->method('request')
			->will($this->returnValue('<AddressValidationResponse></AddressValidationResponse>'));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$response = $this->getModelMock(
			'eb2caddress/validation_response',
			array('setMessage', 'isAddressValid', 'getOriginalAddress', 'getAddressSuggestions')
		);
		$response->expects($this->any())
			->method('setMessage')
			->will($this->returnSelf());
		$response->expects($this->any())
			->method('getOriginalAddress')
			->will($this->returnValue(Mage::getModel('customer/address')));
		$response->expects($this->any())
			->method('getAddressSuggestions')
			->will($this->returnValue(array(Mage::getModel('customer/address'), Mage::getModel('customer/address'))));
		$response->expects($this->any())
			->method('isAddressValid')
			->will($this->returnValue($isValid));
		$this->replaceByMock('model', 'eb2caddress/validation_response', $response);

		$address = $this->getModelMock('customer/address', array('getSameAsBilling', 'setSameAsBilling'));
		// when valid, don't care about the same_as_billing flag so neither should be called
		if ($isValid) {
			$address->expects($this->never())
				->method('getSameAsBilling');
			$address->expects($this->never())
				->method('setSameAsBilling');
		} else {
			// when invalid address, need to check if the address is marked same as billing
			$address->expects($this->once())
				->method('getSameAsBilling')
				->will($this->returnValue($sameAsBilling));
			// when same_as_billing is true, flag should be set to false
			if ($sameAsBilling) {
				$address->expects($this->once())
					->method('setSameAsBilling')
					->with($this->matches(false));
			} else {
				// when not the same, no attempt to change it should be made
				$address->expects($this->never())
					->method('setSameAsBilling');
			}
		}

		$validator = Mage::getModel('eb2caddress/validator');
		$validator->validateAddress($address);
	}

	/**
	 * Test for the last response from EB2C address validation to have
	 * contained a valid address.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testIsValid($hasResponse, $isValid)
	{
		$validator = Mage::getModel('eb2caddress/validator');
		$group = $this->getModelMock('eb2caddress/suggestion_group', array('getResponseMessage'));
		if ($hasResponse) {
			$response = $this->getModelMock('eb2caddress/validation_response', array('isAddressValid'));
			$response->expects($this->any())
				->method('isAddressValid')
				->will($this->returnValue($isValid));
			$group->expects($this->any())
				->method('getResponseMessage')
				->will($this->returnValue($response));
		} else {
			$group->expects($this->any())
				->method('getResponseMessage')
				->will($this->returnValue(null));
		}
		$this->replaceByMock('model', 'eb2caddress/suggestion_group', $group);
		$this->assertSame(
			$this->expected('%s-%s', $hasResponse, $isValid)->getIsValid(),
			$validator->isValid()
		);
	}

}

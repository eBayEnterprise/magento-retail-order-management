<?php

/**
 * Test the generation of the xml request to the EB2C address validation service
 */

class TrueAction_Eb2cAddress_Test_Model_Validation_RequestTest
	extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * Parts of an address to use when building out the address request
	 */
	protected $_addressParts = array(
		'line1' => '123 Main St',
		'line2' => 'STE 6',
		'line3' => 'Foo',
		'line4' => 'Bar',
		'city' => 'Auburn',
		'region_id' => '51',
		'region_code' => 'PA',
		'country_id' => 'US',
		'postcode' => '13021',
	);

	/**
	 * Create a Mage_Customer_Model_Address_Abstract stub for testing
	 */
	protected function _createAddressStub()
	{
		$parts = $this->_addressParts;

		$stub = $this->getMock(
			'Mage_Customer_Model_Address_Abstract',
			array('getStreet', 'getCity', 'getRegionCode', 'getCountry', 'getPostcode')
		);

		$stub->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(
				array($parts['line1'], $parts['line2'], $parts['line3'], $parts['line4'])
			));
		$stub->expects($this->any())
			->method('getCity')
			->will($this->returnValue($parts['city']));
		$stub->expects($this->any())
			->method('getRegionCode')
			->will($this->returnValue($parts['region_code']));
		$stub->expects($this->any())
			->method('getCountry')
			->will($this->returnValue($parts['country_id']));
		$stub->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue($parts['postcode']));
		return $stub;
	}

	/**
	 * Create a TrueAction_Eb2cAddress_Model_Validation_Request to test against.
	 * Ensures that all of the necessary setup of the object is done.
	 */
	protected function _createRequest()
	{
		$request = Mage::getModel('eb2caddress/validation_request');
		$address = $this->_createAddressStub();
		$request->setAddress($address);
		return $request;
	}

	/**
	 * @test
	 * @large
	 */
	public function testRequestMessage()
	{
		$request = $this->_createRequest();
		$message = $request->getMessage();
		$xpath = new DOMXPath($message);
		$ns = $message->lookupNamespaceUri($message->namespaceURI);
		$xpath->registerNamespace('x', $ns);

		$this->assertEquals(
			$ns,
			Mage::getStoreConfig('eb2ccore/api/xml_namespace')
		);
		$maxSuggestions = $xpath
			->query('x:AddressValidationRequest/Header/MaxAddressSuggestions', $message)
			->item(0)
			->textContent;
		// test that the MaxAddressSuggestions pulled correctly from config
		$this->assertEquals(
			Mage::getStoreConfig('eb2caddress/general/max_suggestions'),
			$maxSuggestions
		);
		$address = $xpath
			->query('x:AddressValidationRequest/Address', $message);
		// make sure there is an address node - actual content tested elsewhere
		$this->assertSame(
			$address->length,
			1
		);
	}

}

<?php
class TrueAction_Eb2cAddress_Test_Model_Validation_ResponseTest
	extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testIsValid($valid, $message)
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		$response->setMessage($message);
		$this->assertEquals((bool) $valid, $response->isAddressValid());
	}

	/**
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testIsValidLogged($valid, $message, $logMessage)
	{
		$mockLog = $this->getHelperMock('trueaction_magelog/data', array('logWarn'));
		$mockLog->expects($this->once())
			->method('logWarn')
			->with($this->identicalTo($logMessage), $this->contains('TrueAction_Eb2cAddress_Model_Validation_Response'))
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'trueaction_magelog', $mockLog);
		$response = Mage::getModel('eb2caddress/validation_response');
		$response->setMessage($message);
		$this->assertEquals((bool) $valid, $response->isAddressValid());
	}

	/**
	 * Test creating a Mage_Customer_Model_Address from the response message.
	 * @test
	 */
	public function testGettingOriginalAddress()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* There must be a better way to do this but until I can figure one out
		 * this will have to do...xml response includes the following info on the
		 * original address:
		 * street address = 1671 Clark Street Rd\nOmnicare Building
		 * city = Auburn
		 * MainDivision = NY
		 * CountryCode = US
		 * PostalCode = 13025
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
	<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Header>
		<MaxAddressSuggestions>5</MaxAddressSuggestions>
	</Header>
	<RequestAddress>
		<Line1>1671 Clark Street Rd</Line1>
		<Line2>Omnicare Building</Line2>
		<City>Auburn</City>
		<MainDivision>NY</MainDivision>
		<CountryCode>US</CountryCode>
		<PostalCode>13025</PostalCode>
	</RequestAddress>
	<Result>
		<ResultCode>C</ResultCode>
		<ProviderResultCode>C</ProviderResultCode>
		<ProviderName>Address Doctor</ProviderName>
		<ErrorLocations>
			<ErrorLocation>PostalCode</ErrorLocation>
		</ErrorLocations>
		<ResultSuggestionCount>1</ResultSuggestionCount>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 Clark Street Rd</Line1>
				<City>Auburn</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 Clark Street RdOmnicare BuildingAuburn NY 13021-9523US</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$origAddress = $response->getOriginalAddress();
		$this->assertInstanceOf('Mage_Customer_Model_Address', $origAddress);
		$this->assertSame($origAddress->getStreet(1), '1671 Clark Street Rd');
		$this->assertSame($origAddress->getStreet(2), 'Omnicare Building');
		$this->assertSame($origAddress->getCity(), 'Auburn');
		$this->assertSame($origAddress->getRegionId(), 43);
		$this->assertSame($origAddress->getCountryId(), 'US');
		$this->assertSame($origAddress->getPostcode(), '13025');
	}

	/**
	 * Test creating Mage_Customer_Model_Address objects from the response message
	 * for each of the suggested addresses. The code in the helper/data for converting
	 * address xml to address objects is thoroughly covered so I'm not so much concerend
	 * in the address objects being instantiated and populated properly, hence minimal
	 * checking here of the data in the address objects. Main concern here is:
	 * - The number of suggested addresses returned
	 * - That each suggested address is a proper address object
	 *
	 * @test
	 */
	public function testGettingSuggestedAddresses()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* Following in suggested addresses:
		 * Suggestion 1:
		 *   Line1 = 1671 S Clark Street Rd
		 *   City = Foo
		 *   MainDivision = NY = 43
		 *   CountryCode = US
		 *   PostalCode = 13021-9523
		 * Suggestion 2:
		 *   Line1 = 1671 N Clark Street Rd
		 * City = Bar
		 * MainDivision = PA = 51
		 * CountryCode = US
		 * PostalCode = 19406-1234
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Result>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 S Clark Street Rd</Line1>
				<City>Foo</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 S Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
			<SuggestedAddress>
				<Line1>1671 N Clark Street Rd</Line1>
				<City>Bar</City>
				<MainDivision>PA</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>19406-1234</PostalCode>
				<FormattedAddress>1671 N Clark Street Rd\nAuburn NY 13021-9511\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>City</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$suggestions = $response->getAddressSuggestions();
		$this->assertSame(count($suggestions), 2);
		$first = $suggestions[0];
		$this->assertInstanceOf('Mage_Customer_Model_Address', $first);
		$this->assertSame($first->getCity(), 'Foo');

		$second = $suggestions[1];
		$this->assertInstanceOf('Mage_Customer_Model_Address', $second);
		$this->assertSame($second->getRegionId(), 51);
	}

	/**
	 * When there are multiple suggestions, and the supplied is not considered valid,
	 * there should be no valid address, hence getValidAddress should return null.
	 * @test
	 */
	public function testNoValidAddress()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* Following in suggested addresses:
		 * Suggestion 1:
		 *   Line1 = 1671 S Clark Street Rd
		 *   City = Foo
		 *   MainDivision = NY = 43
		 *   CountryCode = US
		 *   PostalCode = 13021-9523
		 * Suggestion 2:
		 *   Line1 = 1671 N Clark Street Rd
		 * City = Bar
		 * MainDivision = PA = 51
		 * CountryCode = US
		 * PostalCode = 19406-1234
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Result>
		<ResultCode>C</ResultCode>
		<ResultSuggestionCount>2</ResultSuggestionCount>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 S Clark Street Rd</Line1>
				<City>Foo</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 S Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
			<SuggestedAddress>
				<Line1>1671 N Clark Street Rd</Line1>
				<City>Bar</City>
				<MainDivision>PA</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>19406-1234</PostalCode>
				<FormattedAddress>1671 N Clark Street Rd\nAuburn NY 13021-9511\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>City</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$this->assertNull($response->getValidAddress());
	}

	/**
	 * When there are no suggestions and the original address is considered valid,
	 * the valid address should be the same as the original address.
	 * @test
	 */
	public function testOriginalAddressValid()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* Response code is V and there are no suggestions.
		 * Original address should be the same as the original address.
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Header>
		<MaxAddressSuggestions>5</MaxAddressSuggestions>
	</Header>
	<RequestAddress>
		<Line1>1671 Clark Street Rd</Line1>
		<Line2>Omnicare Building</Line2>
		<City>Auburn</City>
		<MainDivision>NY</MainDivision>
		<CountryCode>US</CountryCode>
		<PostalCode>13025</PostalCode>
	</RequestAddress>
	<Result>
		<ResultCode>V</ResultCode>
		<ProviderResultCode>C</ProviderResultCode>
		<ProviderName>Address Doctor</ProviderName>
	</Result>
</AddressValidationResponse>
		');
		$origAddress = $response->getOriginalAddress();
		$this->assertSame(
			$response->getOriginalAddress(),
			$response->getValidAddress()
		);
	}

	/**
	 * When there is only one suggestion, it should be considered the valid address.
	 * @test
	 */
	public function testOneSuggestedAddressIsValid()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* Following in suggested addresses:
		 * Suggestion 1:
		 *   Line1 = 1671 S Clark Street Rd
		 *   City = Foo
		 *   MainDivision = NY = 43
		 *   CountryCode = US
		 *   PostalCode = 13021-9523
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Result>
		<ResultCode>C</ResultCode>
		<ResultSuggestionCount>1</ResultSuggestionCount>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 S Clark Street Rd</Line1>
				<City>Foo</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 S Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$suggestions = $response->getAddressSuggestions();
		$this->assertSame($response->getValidAddress(), $suggestions[0]);
	}

	/**
	 * When there are more than one suggestion in the response message,
	 * should accurately detect that there are suggestions.
	 * @test
	 */
	public function testDetectingSuggestionsInMessage()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/**
		 * Response message includes 2 suggestions.
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Result>
		<ResultCode>C</ResultCode>
		<ResultSuggestionCount>2</ResultSuggestionCount>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 S Clark Street Rd</Line1>
				<City>Foo</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 S Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
			<SuggestedAddress>
				<Line1>1671 N Clark Street Rd</Line1>
				<City>Bar</City>
				<MainDivision>PA</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>19406-1234</PostalCode>
				<FormattedAddress>1671 N Clark Street Rd\nAuburn NY 13021-9511\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>City</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$this->assertTrue($response->hasAddressSuggestions());
	}

	/**
	 * When there is only one suggestions, the one suggestion will be considered valid,
	 * hence, it should not consider there to be any suggestions.
	 * @test
	 */
	public function testDetectOnlyOneSuggestionInMessage()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/**
		 * Response message includes 1 suggestions - which should be considered the valid address.
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Result>
		<ResultCode>C</ResultCode>
		<ResultSuggestionCount>1</ResultSuggestionCount>
		<SuggestedAddresses>
			<SuggestedAddress>
				<Line1>1671 S Clark Street Rd</Line1>
				<City>Foo</City>
				<MainDivision>NY</MainDivision>
				<CountryCode>US</CountryCode>
				<PostalCode>13021-9523</PostalCode>
				<FormattedAddress>1671 S Clark Street Rd\nAuburn NY 13021-9523\nUS</FormattedAddress>
				<ErrorLocations>
					<ErrorLocation>Line1</ErrorLocation>
					<ErrorLocation>MainDivision</ErrorLocation>
					<ErrorLocation>PostalCode</ErrorLocation>
				</ErrorLocations>
			</SuggestedAddress>
		</SuggestedAddresses>
	</Result>
</AddressValidationResponse>
		');
		$this->assertFalse($response->hasAddressSuggestions());
	}

	/**
	 * Should accurately detect and report that there are no suggestions when
	 * no suggestions exist in the response message.
	 * @test
	 */
	public function testDetectNoSuggestionsInMessage()
	{
		$response = Mage::getModel('eb2caddress/validation_response');
		/* Response code is V and there are no suggestions.
		 * Original address should be the same as the original address.
		 */
		$response->setMessage('<?xml version="1.0" encoding="UTF-8"?>
<AddressValidationResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Header>
		<MaxAddressSuggestions>5</MaxAddressSuggestions>
	</Header>
	<RequestAddress>
		<Line1>1671 Clark Street Rd</Line1>
		<Line2>Omnicare Building</Line2>
		<City>Auburn</City>
		<MainDivision>NY</MainDivision>
		<CountryCode>US</CountryCode>
		<PostalCode>13025</PostalCode>
	</RequestAddress>
	<Result>
		<ResultCode>V</ResultCode>
		<ProviderResultCode>C</ProviderResultCode>
		<ProviderName>Address Doctor</ProviderName>
	</Result>
</AddressValidationResponse>
		');
		$this->assertFalse($response->hasAddressSuggestions());
	}
}

<?php

class TrueAction_Eb2cAddress_Test_Helper_DataTest
	extends EcomDev_PHPUnit_Test_Case
{

	protected $_addressParts = array(
		'line1' => "123 Don't you wish you lived hére tøó at this place Ï like to call Mæn Street",
		'line1_trimmed' => "123 Don't you wish you lived hére tøó at this place Ï like to call Mæn",
		'line2' => '12345678901234567890123456789012345678901234567890123456789012345678901234567890',
		'line2_trimmed' => '1234567890123456789012345678901234567890123456789012345678901234567890',
		'line3' => '12345678901234567890123456789012345678901234567890123456789012345678901234567890',
		'line3_trimmed' => '1234567890123456789012345678901234567890123456789012345678901234567890',
		'line4' => '12345678901234567890123456789012345678901234567890123456789012345678901234567890',
		'line4_trimmed' => '1234567890123456789012345678901234567890123456789012345678901234567890',
		'city' => '1234567890123456789012345678901234567890',
		'city_trimmed' => '12345678901234567890123456789012345',
		'region_id' => '51',
		'region_code' => 'PA',
		'country_id' => 'US',
		'postcode' => '12345678901234567890',
		'postcode_trimmed' => '123456789012345'
	);

	/**
	 * Generate a reusable Mage_Customer_Model_Address object
	 */
	protected function _generateAddressObject($streetLines=4)
	{
		$address = Mage::getModel('customer/address');
		$street = array();
		for ($i = 1; $i <= $streetLines; $i++) {
			$street[] = $this->_addressParts['line' . $i];
		}
		$address->setStreet($street);
		$address->setCity($this->_addressParts['city']);
		$address->setRegionId($this->_addressParts['region_id']);
		$address->setCountryId($this->_addressParts['country_id']);
		$address->setPostcode($this->_addressParts['postcode']);
		return $address;
	}

	/**
	 * Create a DOMDocument containing a PhysicalAddressType
	 */
	protected function _generatePhysicalAddressElement($streetLines=4)
	{
		$dom = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$rootNs = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2caddress/config'))->apiNamespace;
		$root = $dom->appendChild($dom->createElement('address', null, $rootNs));
		for ($i = 1; $i <= $streetLines; $i++) {
			$root->addChild('Line' . $i, $this->_addressParts['line' . $i]);
		}
		$root->addChild('City', $this->_addressParts['city'])
			->addChild('MainDivision', $this->_addressParts['region_code'])
			->addChild('CountryCode', $this->_addressParts['country_id'])
			->addChild('PostalCode', $this->_addressParts['postcode']);
		return $root;
	}

	/**
	 * Test the generic method for getting the text contents from a set of XML
	 * based on a given XPath expression using element as XPath context.
	 * @test
	 */
	public function testTextValueFromXmlPathOnElement()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->addElement('root');
		$root = $doc->documentElement;
		$root->createChild('foo')->addChild('bar', 'one')->addChild('baz', 'two');
		$root->createChild('color', 'red');

		$multiValues = Mage::helper('eb2caddress')->getTextValueByXPath('foo/*', $root);
		$singleValue = Mage::helper('eb2caddress')->getTextValueByXPath('color', $root);
		$nullValue = Mage::helper('eb2caddress')->getTextValueByXPath('nope_not_here', $root);
		$this->assertSame($multiValues, array('one', 'two'));
		$this->assertSame($singleValue, 'red');
		$this->assertNull($nullValue);
	}

	/**
	 * Test the generic method for getting text contents from a set of XML
	 * based on a given XPath expression using document as XPath context.
	 * @test
	 */
	public function testTextValueFromXmlPathOnDocument()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->addElement('root');
		$root = $doc->documentElement;
		$root->createChild('foo')->addChild('bar', 'one')->addChild('baz', 'two');
		$root->createChild('color', 'red');

		$multiValuesFromDoc = Mage::helper('eb2caddress')->getTextValueByXPath('root/foo/*', $doc);
		$singleValueFromDoc = Mage::helper('eb2caddress')->getTextValueByXPath('root/color', $doc);
		$nullValueFromDoc = Mage::helper('eb2caddress')->getTextValueByXPath('root/nope_not_here', $doc);
		$this->assertSame($multiValuesFromDoc, array('one', 'two'));
		$this->assertSame($singleValueFromDoc, 'red');
		$this->assertNull($nullValueFromDoc);
	}

	/**
	 * Test getting the address lines from PhysicalAddressType XML
	 * @test
	 */
	public function testAddressStreetLines()
	{
		$expectedParts = $this->_addressParts;
		$street = Mage::helper('eb2caddress')
			->physicalAddressStreet($this->_generatePhysicalAddressElement());
		$this->assertEquals($street, array($expectedParts['line1'], $expectedParts['line2'], $expectedParts['line3'], $expectedParts['line4']));
		$street = Mage::helper('eb2caddress')
			->physicalAddressStreet($this->_generatePhysicalAddressElement(3));
		$this->assertEquals($street, array($expectedParts['line1'], $expectedParts['line2'], $expectedParts['line3']));
		$street = Mage::helper('eb2caddress')
			->physicalAddressStreet($this->_generatePhysicalAddressElement(2));
		$this->assertEquals($street, array($expectedParts['line1'], $expectedParts['line2']));
		$street = Mage::helper('eb2caddress')
			->physicalAddressStreet($this->_generatePhysicalAddressElement(1));
		$this->assertEquals($street, $expectedParts['line1']);
	}

	/**
	 * Test getting the city from PhysicalAddressType XML
	 * @test
	 */
	public function testAddressCity()
	{
		$city = Mage::helper('eb2caddress')
			->physicalAddressCity($this->_generatePhysicalAddressElement());
		$this->assertSame($city, $this->_addressParts['city']);
	}

	/**
	 * Test getting a Magento region id from PhysicalAddressType XML
	 * @test
	 */
	public function testAddressRegion()
	{
		$region = Mage::helper('eb2caddress')
			->physicalAddressRegionId($this->_generatePhysicalAddressElement());
		$this->assertSame($region, 51); // 'PA' maps to region id '51' in Magento addreses
	}

	/**
	 * Test getting the country id from PhysicalAddressType XML
	 * @test
	 */
	public function testAddressContry()
	{
		$country = Mage::helper('eb2caddress')
			->physicalAddressCountryId($this->_generatePhysicalAddressElement());
		$this->assertSame($country, 'US');
	}

	/**
	 * Test getting the postcode from PhysicalAddressType XML
	 * @test
	 */
	public function testAddressPostcode()
	{
		$postcode = Mage::helper('eb2caddress')
			->physicalAddressPostcode($this->_generatePhysicalAddressElement());
		$this->assertSame($postcode, $this->_addressParts['postcode']);
	}

	/**
	 * Test the conversion of an address object to XML
	 * @test
	 */
	public function testAddressToXml()
	{
		$dom = new TrueAction_Dom_Document();
		$address = $this->_generateAddressObject();
		$addressFragment = Mage::helper('eb2caddress')
			->addressToPhysicalAddressXml($address, $dom, 'test-ns');
		$fragmentNodes = $addressFragment->childNodes;
		$this->assertEquals($fragmentNodes->item(0)->nodeName, 'Line1');
		$this->assertEquals($fragmentNodes->item(0)->textContent, $this->_addressParts['line1_trimmed']);
		$this->assertEquals($fragmentNodes->item(1)->nodeName, 'Line2');
		$this->assertEquals($fragmentNodes->item(1)->textContent, $this->_addressParts['line2_trimmed']);
		$this->assertEquals($fragmentNodes->item(2)->nodeName, 'Line3');
		$this->assertEquals($fragmentNodes->item(2)->textContent, $this->_addressParts['line3_trimmed']);
		$this->assertEquals($fragmentNodes->item(3)->nodeName, 'Line4');
		$this->assertEquals($fragmentNodes->item(3)->textContent, $this->_addressParts['line4_trimmed']);
		$this->assertEquals($fragmentNodes->item(4)->nodeName, 'City');
		$this->assertEquals($fragmentNodes->item(4)->textContent, $this->_addressParts['city_trimmed']);
		$this->assertEquals($fragmentNodes->item(5)->nodeName, 'MainDivision');
		$this->assertEquals($fragmentNodes->item(5)->textContent, $this->_addressParts['region_code']);
		$this->assertEquals($fragmentNodes->item(6)->nodeName, 'CountryCode');
		$this->assertEquals($fragmentNodes->item(6)->textContent, $this->_addressParts['country_id']);
		$this->assertEquals($fragmentNodes->item(7)->nodeName, 'PostalCode');
		$this->assertEquals($fragmentNodes->item(7)->textContent, $this->_addressParts['postcode_trimmed']);
	}

	/**
	 * Test converting a DOM element representing an address to a proper Mage_Customer_Model_Address object
	 * @test
	 */
	public function testXmlToAddress()
	{
		$address = Mage::helper('eb2caddress')
			->physicalAddressXmlToAddress($this->_generatePhysicalAddressElement());
		$this->assertInstanceOf('Mage_Customer_Model_Address', $address);
		$this->assertSame($address->getStreet1(), $this->_addressParts['line1']);
		$this->assertSame($address->getStreet2(), $this->_addressParts['line2']);
		$this->assertSame($address->getStreet3(), $this->_addressParts['line3']);
		$this->assertSame($address->getStreet4(), $this->_addressParts['line4']);
		$this->assertSame($address->getCity(), $this->_addressParts['city']);
		$this->assertSame($address->getRegionCode(), $this->_addressParts['region_code']);
		$this->assertSame($address->getCountry(), $this->_addressParts['country_id']);
		$this->assertSame($address->getPostcode(), $this->_addressParts['postcode']);
	}

}

<?php
use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

class TrueAction_Eb2cAddress_Test_Model_ObserverTest
	extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
	{
		parent::setUp();
		TestUtil::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
		TestUtil::tearDown();
	}

	protected function _mockConfig($enabled)
	{
		$config = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get', 'addConfigModel'))
			->getMock();
		$config->expects($this->once())
			->method('addConfigModel')
			->with($this->equalTo(Mage::getSingleton('eb2caddress/config')))
			->will($this->returnSelf());
		$config->expects($this->once())
			->method('__get')
			->with($this->identicalTo('isValidationEnabled'))
			->will($this->returnValue($enabled));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $config);
		return $config;
	}

	/**
	 * When disabled, observer method should do nothing.
	 * @test
	 */
	public function testValidationValidationDisabled()
	{
		$config = $this->_mockConfig(0);

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->never())
			->method('getEvent');
		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));
		$validator->expects($this->never())
			->method('validateAddress');

		Mage::getSingleton('eb2caddress/observer')->validateAddress($observer);
	}

	/**
	 * When disabled, observer method should do nothing.
	 * @test
	 */
	public function testAddSuggestionsValidationDisabled()
	{
		$config = $this->_mockConfig(0);

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->never())
			->method('getEvent');
		$validator = $this->getModelMock('eb2caddress/validator', array('hasSuggestions'));
		$validator->expects($this->never())
			->method('hasSuggestions');
		$addressObserver = $this->getModelMock('eb2caddress/observer', array('_getAddressBlockHtml'));
		$addressObserver->expects($this->never())
			->method('_getAddressBlockHtml');

		$addressObserver->addSuggestionsToResponse($observer);
	}

	/**
	 * Test that when address validation fails, the errors are added to the address
	 * object's set of errors.
	 * @test
	 */
	public function testValidateAddressValidationErrors()
	{
		$this->_mockConfig(1);

		$expectedError = 'Error from validation';
		$address = $this->getModelMock('customer/address', array('addError'));
		$address->expects($this->once())
			->method('addError')
			->with($this->identicalTo($expectedError))
			->will($this->returnSelf());

		$event = new Varien_Object();
		$event->setAddress($address);

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));
		$validator->expects($this->once())
			->method('validateAddress')
			->with($this->equalTo($address))
			->will($this->returnValue($expectedError));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$addressObserver = Mage::getSingleton('eb2caddress/observer');
		$addressObserver->validateAddress($observer);
	}

	/**
	 * Ensure when validation is successful, that no errors are added to the address
	 * @test
	 */
	public function testValidateAddressSuccess()
	{
		$this->_mockConfig(1);

		$address = $this->getModelMock('customer/address', array('addError'));
		$address->expects($this->never())
			->method('addError');

		$event = new Varien_Object();
		$event->setAddress($address);

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		$validator = $this->getModelMock('eb2caddress/validator', array('validateAddress'));
		$validator->expects($this->once())
			->method('validateAddress')
			->with($this->equalTo($address))
			->will($this->returnValue(null));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$addressObserver = Mage::getSingleton('eb2caddress/observer');
		$addressObserver->validateAddress($observer);
	}

	/**
	 * When address validation was successful/there are no errors in the response
	 * the response body should go unchanged.
	 */
	public function testResponseSuggestionsNoErrors()
	{
		$validator = $this->getModelMock('eb2caddress/validator', array('isValid'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->never())
			->method('isValid');
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case not inlcude an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{}'));
		// body of the response should not be changed
		$response->expects($this->never())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->never())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
		);
		$layout->expects($this->never())
			->method('getUpdate');
		$layout->expects($this->never())
			->method('generateXml');
		$layout->expects($this->never())
			->method('generateBlocks');
		$layout->expects($this->never())
			->method('getOutput');

		// controller should be asked for the response but shouldn't generage a layout
		$controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
			->disableOriginalConstructor()
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->never())
			->method('getLayout');

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}

	/**
	 * When the response is invalid and there are suggestions to show,
	 * the response JSON should be modified to include the markup for the suggestions.
	 * @test
	 */
	public function testResponseWithErrors()
	{
		$validator = $this->getModelMock('eb2caddress/validator', array('isValid'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->once())
			->method('isValid')
			->will($this->returnValue(false));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$this->_mockConfig(1);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case not inlcude an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{"error":1}'));
		// body of the response should not be changed
		$response->expects($this->once())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->once())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
		);
		$layout->expects($this->once())
			->method('getUpdate')
			->will($this->returnValue($update));
		$layout->expects($this->once())
			->method('generateXml');
		$layout->expects($this->once())
			->method('generateBlocks');
		$layout->expects($this->once())
			->method('getOutput');

		// controller should be asked for the response and the layout
		$controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
			->disableOriginalConstructor()
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->exactly(2))
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->once())
			->method('getLayout')
			->will($this->returnValue($layout));

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}

	/**
	 * Suggestions should not be added to the OPC response when the address is valid,
	 * even if there are errors in the response.
	 * @test
	 */
	public function testResposeValidAddressWithErrors()
	{
		$this->_mockConfig(1);

		$validator = $this->getModelMock('eb2caddress/validator', array('isValid'));
		// when there aren't errors in the response, this shouldn't get called
		$validator->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2caddress/validator', $validator);

		$response = $this->getMock('Mage_Core_Controller_Response_Http', array('getBody', 'setBody'));
		// response body must be JSON and in this case not inlcude an "error" property
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('{"error":1}'));
		// body of the response should not be changed
		$response->expects($this->never())
			->method('setBody');

		// core/layout_update should not be touched in this scenario
		$update = $this->getModelMock('core/layout_update', array('load'));
		$update->expects($this->never())
			->method('load');

		// core/layout should not be touched in this scenario
		$layout = $this->getModelMock(
			'core/layout',
			array('getUpdate', 'generateXml', 'generateBlocks', 'getOutput')
		);
		$layout->expects($this->never())
			->method('getUpdate');
		$layout->expects($this->never())
			->method('generateXml');
		$layout->expects($this->never())
			->method('generateBlocks');
		$layout->expects($this->never())
			->method('getOutput');

		// controller should be asked for the response but shouldn't generage a layout
		$controller = $this->getMockBuilder('Mage_Checkout_Controller_Action')
			->disableOriginalConstructor()
			->setMethods(array('getResponse', 'getLayout'))
			->getMock();
		$controller->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));
		$controller->expects($this->never())
			->method('getLayout');

		$event = $this->getMock('Varien_Event', array('getControllerAction'));
		$event->expects($this->once())
			->method('getControllerAction')
			->will($this->returnValue($controller));

		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$addressObserver = Mage::getSingleton('eb2caddress/observer')->addSuggestionsToResponse($observer);
	}
}

<?php
abstract class TrueAction_Eb2cCore_Test_Base
	extends EcomDev_PHPUnit_Test_Case {

	const EB2CCORE_CONFIG_REGISTRY_MODEL = 'eb2ccore/config_registry';

	public function getLocalFixture($key=null)
	{
		$fixture = $this->getFixture()->getStorage()->getLocalFixture();
		if (!is_null($key)) {
			if (isset($fixture[$key])) {
				$fixture = $fixture[$key];
			} else {
				throw new Exception("Unable to get fixture data for key [$key]");
			}
		}
		return $fixture;
	}

	/**
	 * invoke a restricted method using EcomDev's Reflection utility class.
	 * @see lib/EcomDev/Utils/Reflection.php
	 */
	public function invokeRestrictedMethod($object, $methodName, $args=array())
	{
		return EcomDev_Utils_Reflection::invokeRestrictedMethod($object, $methodName, $args);
	}

	/**
	 * @todo Is this the best way to do this?
	 * Mocks an Observer, use this as an configuration option as shown. Needed a 'null' stubObserver to bypass
	 *  dispatch functionality in a specific test.
	 *
	 * config:
	 *   global/events/Name_Of_Your_Dispatched_Event/observers/Your_Observers_Name/class: 'TrueAction_YourTest_Class'
	 *   global/events/Name_Of_Your_Dispatched_Event/observers/Your_Observers_Name/method: 'stubObserver'
	 *
	 */
	public function stubObserver($observerArgs)
	{
		return $this;
	}

	/**
	 * Replaces the Magento eb2ccore/config_registry model.
	 *
	 * @param array name/ value map
	 */
	public function replaceCoreConfigRegistry($userConfigValuePairs=array())
	{
		$configValuePairs = array (
			// Core Values:
			'clientId'                => 'TAN-OS-CLI',
			'feedDestinationType'     => 'MAILBOX',
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

		$mockConfig = $this->getModelMock(self::EB2CCORE_CONFIG_REGISTRY_MODEL, array('__get'));
		$mockConfig->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($valueMap));

		$this->replaceByMock('model', self::EB2CCORE_CONFIG_REGISTRY_MODEL, $mockConfig);
	}

	/**
	 * clears the config cache in the specified store.
	 * @param  mixed $store a code, id, or model of a magento storm
	 * @return null
	 */
	public function clearStoreConfigCache($store=null)
	{
		$store = EcomDev_PHPUnit_Test_Case_Util::app()->getStore($store);
		$this->_reflectProperty($store, '_configCache')->setValue($store, array());
	}

	/**
	 * @return null
	 */
	public function setUp()
	{
		EcomDev_PHPUnit_Test_Case_Util::setUp();
	}

	/**
	 * performs the following cleanup tasks:
	 * - discards fixtures
	 * @return null
	 */
	public function tearDown()
	{
		Mage::app()->setCurrentStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		EcomDev_PHPUnit_Test_Case_Util::tearDown();
	}

	protected function _reflectProperty($object, $propName, $accessible=true)
	{
		$p = new ReflectionProperty($object, $propName);
		$p->setAccessible($accessible);
		return $p;
	}

	protected function _reflectMethod($object, $methodName, $accessible=true)
	{
		$p = new ReflectionMethod($object, $methodName);
		$p->setAccessible($accessible);
		return $p;
	}

	protected function _buildModelMock($alias, array $methods)
	{
		$mock = $this->getModelMock($alias, array_keys($methods));
		foreach ($methods as $name => $will) {
			if (!is_null($will)) {
				$mock->expects($this->any())
					->method($name)
					->will($will);
			}
		}
		return $mock;
	}

	/**
	 * Replace the checkout session object with a mock.
	 * @param  array $methods Enables original methods usage if null
	 * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
	 */
	protected function mockCheckoutSession($methods=null)
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods($methods)
			->getMock();
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);
		return $sessionMock;
	}

	protected function _setupBaseUrl()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	protected function _mockCookie()
	{
		$cookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(array('set')) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$cookieMock->expects($this->any())
			->method('set')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/cookie', $cookieMock);
	}
}

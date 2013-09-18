<?php
abstract class TrueAction_Eb2cCore_Test_Base
	extends EcomDev_PHPUnit_Test_Case {

	const EB2CCORE_CONFIG_REGISTRY_MODEL = 'eb2ccore/config_registry';

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

			// Sample non-core Values:
			'developerMode' => true,
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
	public function setUp()
	{
		EcomDev_PHPUnit_Test_Case_Util::setUp();
	}
	public function tearDown()
	{
		EcomDev_PHPUnit_Test_Case_Util::tearDown();
	}
}

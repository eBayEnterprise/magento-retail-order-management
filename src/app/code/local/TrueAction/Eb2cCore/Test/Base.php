<?php
class TrueAction_Eb2cCore_Test_Base extends EcomDev_PHPUnit_Test_Case {
	protected function _reflectProperty($object, $propName, $accessible = true)
	{
		$p = new ReflectionProperty($object, $propName);
		$p->setAccessible($accessible);
		return $p;
	}

	protected function _reflectMethod($object, $methodName, $accessible = true)
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

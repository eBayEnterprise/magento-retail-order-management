<?php
class TrueAction_Eb2cTax_Test_Base extends EcomDev_PHPUnit_Test_Case {
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

	protected function _buildModelMock($alias, $methods, $initData = null)
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
}
<?php
/**
 * Test Suite for the Order permissions suppression.
 */
class TrueAction_Eb2cOrder_Test_Model_Suppression_PermissionsTest extends TrueAction_Eb2cCore_Test_Base
{
	public function testGetResourceConfigNode()
	{
		$testModel = Mage::getModel('eb2corder/suppression_permissions');
		$result = $this->_reflectMethod($testModel, '_getResourceConfigNode')->invoke($testModel);
		$this->assertInstanceOf('Mage_Core_Model_Config_Element', $result);
		$this->assertSame('permissions', $result->getName());
	}

	/**
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testLoadResources()
	{
		$config = Mage::getModel('core/config');
		$config->loadString($this->getLocalFixture('resources_xml_string'));

		$testModel = $this->getModelMock('eb2corder/suppression_permissions', array('_getResourceConfigNode'));
		$testModel->expects($this->any())
			->method('_getResourceConfigNode')
			->will($this->returnValue(
				$config->getNode(TrueAction_Eb2cOrder_Model_Suppression_Permissions::RESOURCE_CONFIG_PATH)
			));
		$result = $this->_reflectMethod($testModel, '_loadResources')->invoke($testModel);
		$e = $this->expected('permissions_array');
		$this->assertSame($e->getDeny(), $result['deny']);
		$this->assertSame($e->getAllow(), $result['allow']);
	}

	/**
	 * @loadExpectation
	 */
	public function testApply()
	{
		$e = $this->expected('test_apply');
		$permArray = $e->getPermissionsArray();
		$this->assertArrayHasKey('allow', $permArray);
		$testModel = $this->getModelMock('eb2corder/suppression_permissions', array(
			'_loadResources',
			'_applyPermissionsToRole',
			'_getRolesToModify',
		));
		$testModel->expects($this->once())
			->method('_loadResources')
			->will($this->returnValue($permArray));
		$testModel->expects($this->exactly(2))
			->method('_applyPermissionsToRole')
			->with(
				$this->isInstanceOf('Mage_Admin_Model_Role'),
				$this->isType('array'),
				$this->logicalOr(
					$this->identicalTo('allow'),
					$this->identicalTo('deny')
				)
			);
		$collection = $this->getResourceModelMock('admin/user_collection', array(
			'load'
		));
		$collection->addItem(Mage::getModel('admin/role'));
		$testModel->expects($this->once())
			->method('_getRolesToModify')
			->will($this->returnValue($collection));
		$testModel->apply();
	}

	/**
	 * verify the collection is filtered as expected.
	 */
	public function testGetRolesToModify()
	{
		$collection = $this->getResourceModelMock('admin/role_collection', array(
			'load',
			'addFieldToFilter',
		));
		$collection->expects($this->exactly(3))
			->method('addFieldToFilter')
			->will($this->returnCallback(
				function($field, $conditions) use ($collection)
				{
					PHPUnit_Framework_Assert::assertTrue(
						in_array($field, array('role_type', 'role_id', 'parent_id')),
						"$field is not expected to be in the filter"
					);
					if ($field === 'role_type') {
						PHPUnit_Framework_Assert::assertSame(
							array('eq' => 'G'), $conditions,
							"the conditions for the $field are not what is expected"
						);
					} elseif ($field === 'role_id') {
						PHPUnit_Framework_Assert::assertSame(
							array('neq' => '1'), $conditions,
							"the conditions for the $field are not what is expected"
						);
					} elseif ($field === 'parent_id') {
						PHPUnit_Framework_Assert::assertSame(
							array('eq' => '0'), $conditions,
							"the conditions for the $field are not what is expected"
						);
					}
					return $collection;
				}));
		$this->replaceByMock('resource_model', 'admin/role_collection', $collection);

		$testModel = Mage::getModel('eb2corder/suppression_permissions');
		$result = $this->_reflectMethod($testModel, '_getRolesToModify')->invoke($testModel);
		$this->assertInstanceOf('Mage_Admin_Model_Resource_Role_Collection', $result);
	}

	public function testGetRules()
	{
		$roleId = 500;
		$roleType = 'G';
		$resourceList = array();

		$collection = $this->getResourceModelMock('admin/permissions_collection', array(
			'load',
			'addFieldToFilter',
		));
		$collection->expects($this->exactly(3))
			->method('addFieldToFilter')
			->will($this->returnCallback(
				function($field, $conditions) use ($collection, $roleId, $roleType, $resourceList)
				{
					PHPUnit_Framework_Assert::assertTrue(
						in_array($field, array('role_type', 'role_id', 'resource_id')),
						"$field is not expected to be in the filter"
					);
					if ($field === 'role_type') {
						PHPUnit_Framework_Assert::assertSame(
							array('eq' => $roleType), $conditions,
							"the conditions for the $field are not what is expected"
						);
					} elseif ($field === 'role_id') {
						PHPUnit_Framework_Assert::assertSame(
							array('eq' => $roleId), $conditions,
							"the conditions for the $field are not what is expected"
						);
					} elseif ($field === 'resource_id') {
						PHPUnit_Framework_Assert::assertSame(
							array('in' => $resourceList), $conditions,
							"the conditions for the $field are not what is expected"
						);
					}
					return $collection;
				}));
		$this->replaceByMock('resource_model', 'admin/permissions_collection', $collection);

		$role = $this->getModelMock('admin/role', array('getId', 'getRoleType'));
		$role->expects($this->once())
			->method('getId')
			->will($this->returnValue($roleId));
		$role->expects($this->once())
			->method('getRoleType')
			->will($this->returnValue($roleType));

		$testModel = Mage::getModel('eb2corder/suppression_permissions');
		$result = $this->_reflectMethod($testModel, '_getRules')->invoke($testModel, $role, $resourceList);
		$this->assertInstanceOf('Mage_Admin_Model_Resource_Permissions_Collection', $result);
	}

	/**
	 * verify existing rules have their permission updated and new rules are created
	 * otherwise.
	 */
	public function testApplyPermissionsToRole()
	{
		$roleId = 500;
		$roleType = 'G';
		$existingResource = 'existing/resource';
		$nonExistingResource = 'non/existing/resource';
		$resourceList = array($existingResource, $nonExistingResource);
		$action = 'theaction';

		// handle the case where we the rule exists
		$rule = $this->getModelMock('admin/rules', array(
			'setRoleId',
			'setRoleType',
			'setPermission',
			'getResourceId',
			'save',
		));
		$rule->expects($this->never())
			->method('setRoleType');
		$rule->expects($this->never())
			->method('setRoleId');
		$rule->expects($this->once())
			->method('setPermission')
			->with($this->identicalTo($action));
		$rule->expects($this->once())
			->method('getResourceId')
			->will($this->returnValue($existingResource));
		$rule->expects($this->once())
			->method('save');
		$collection = $this->getResourceModelMock('admin/permissions_collection', array('load'));
		$collection->addItem($rule);

		// setup a rule to make sure we're setting up a new rule if the old doesn't exist
		$rule = $this->getModelMock('admin/rules', array(
			'setRoleId',
			'setRoleType',
			'setPermission',
			'save',
		));
		$rule->expects($this->once())
			->method('setRoleType')
			->with($roleType);
		$rule->expects($this->once())
			->method('setRoleId')
			->with($roleId);
		$rule->expects($this->once())
			->method('setPermission')
			->with($action);
		$rule->expects($this->never())
			->method('getResourceId');
		$rule->expects($this->once())
			->method('save');
		$this->replaceByMock('model', 'admin/rules', $rule);

		$testModel = $this->getModelMock('eb2corder/suppression_permissions', array(
			'_getRules',
		));
		$testModel->expects($this->once())
			->method('_getRules')
			->will($this->returnValue($collection));

		$role = $this->getModelMock('admin/role', array('getId', 'getRoleType'));
		$role->expects($this->once())
			->method('getId')
			->will($this->returnValue($roleId));
		$role->expects($this->once())
			->method('getRoleType')
			->will($this->returnValue($roleType));

		$this->_reflectMethod($testModel, '_applyPermissionsToRole')->invoke($testModel, $role, $resourceList, $action);
	}
}

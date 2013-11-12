<?php
/**
 * apply permissions to suppress user access to admin resources involving order
 * management.
 * @package Eb2c\Order
 * @author mphang@ebay.com
 *
 */
class TrueAction_Eb2cOrder_Model_Suppression_Permissions
{
	/**
	 * path to the permissions node
	 */
	const RESOURCE_CONFIG_PATH = 'admin/eb2corder/permissions';

	/**
	 * name of the xml file to load the permissions from
	 */
	const RESOURCE_CONFIG_FILE = 'permissions.xml';

	/**
	 * the id of the default administrator role (usually 1)
	 * @var integer
	 */
	protected $_administratorRoleId;

	/**
	 * get the config node for the permissions.
	 * @return Mage_Core_Model_Config_Element
	 */
	protected function _getResourceConfigNode()
	{
		$config = Mage::getConfig()
			->loadModulesConfiguration(self::RESOURCE_CONFIG_FILE);
		return $config->getNode(self::RESOURCE_CONFIG_PATH);
	}

	/**
	 * get a collection of roles to apply permissions to.
	 * @return Mage_Admin_Model_Resource_Role_Collection
	 */
	protected function _getRolesToModify()
	{
		$collection = Mage::getResourceModel('admin/role_collection');
		$collection->addFieldToFilter('role_type', array('eq' => 'G'));
		$collection->addFieldToFilter('parent_id', array('eq' => '0'));
		$collection->addFieldToFilter('role_id', array('neq' => '1'));
		return $collection;
	}

	public function prepareResourceString($resourceString)
	{
		return str_replace('.', '/', $resourceString);
	}

	/**
	 * get a collection of rules that match the resource list.
	 * @param  Mage_Admin_Model_Role $role
	 * @param  array                 $resourceList
	 * @return Mage_Admin_Model_Resource_Permissions_Collection
	 */
	protected function _getRules(Mage_Admin_Model_Role $role, array $resourceList)
	{
		$collection = Mage::getModel('admin/rules')->getCollection();
		$collection->addFieldToFilter('role_type', array('eq' => $role->getRoleType()));
		$collection->addFieldToFilter('resource_id', array('in' => $resourceList));
		$collection->addFieldToFilter('role_id', array('eq' => $role->getId()));
		return $collection;
	}

	/**
	 * load permissions from the config and return an array of the resources
	 * allowed/denied.
	 * @return array()
	 */
	protected function _loadResources()
	{
		$permissions = array();
		$permissionsNode = $this->_getResourceConfigNode();
		foreach (array('allow', 'deny') as $action) {
			$permissionNode = $permissionsNode->descend($action);
			if ($permissionNode === false) {
				$permissions[$action] = array();
			} else {
				$permissions[$action] = array_map(
					array($this, 'prepareResourceString'),
					array_keys((array) $permissionNode)
				);
			}
		}
		return $permissions;
	}

	/**
	 * set all resources in $resourceList for $role to have $action as the permission.
	 *
	 * NOTE: saveRel is not designed to be able to save deny permissions, so the regular save method has to be used.
	 * @param  Mage_Admin_Model_Role $role
	 * @param  array                 $resourceList
	 * @param  string                $action 'apply' or 'deny'
	 * @return self
	 */
	protected function _applyPermissionsToRole(Mage_Admin_Model_Role $role, array $resourceList, $action)
	{
		$existingResources = array();
		$existingRules = $this->_getRules($role, $resourceList);
		foreach ($existingRules as $rule) {
			$existingResources[] = $rule->getResourceId();
			if ($rule->getPermission() !== $action) {
				$rule->setPermission($action);
				$rule->save();
			}
		}
		$newResources = array_diff($resourceList, $existingResources);
		foreach ($newResources as $resource) {
			$rule = Mage::getModel('admin/rules');
			$rule->setResourceId($resource);
			$rule->setRoleId($role->getId());
			$rule->setRoleType($role->getRoleType());
			$rule->setPermission($action);
			$rule->save();
		}
	}

	/**
	 * apply permissions to all groups except the administrator group.
	 * @return self
	 */
	public function apply()
	{
		$permArray = $this->_loadResources();
		foreach($this->_getRolesToModify() as $role) {
			foreach (array('allow', 'deny') as $action) {
				$this->_applyPermissionsToRole($role, $permArray[$action], $action);
			}
		}
		return $this;
	}
}

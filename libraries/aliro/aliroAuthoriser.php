<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the 
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more 
 * information on this, please see the index.php file or visit 
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * aliroAuthoriser is a singleton class that handles questions concerning the Role Based
 * Access Control (RBAC) system for Aliro.  It is a companion to aliroAuthorisationAdmin
 * which is the class that deals with updating the RBAC information.  Since the information
 * used in this class is often particular to the current user, it makes poor sense to
 * have a general cache.  Instead, information is cached using session variables. An
 * exception to this principle is the linking structure that enables implied roles to
 * be derived - e.g. a Publisher implicitly also has the rights belonging to an Editor.
 * Since this information is tricky to construct and general to all users, it is cached
 * in the file system.
 *
 */

class aliroAuthoriserCache extends cachedSingleton {
	protected static $instance = __CLASS__;

	protected $linked_roles = array();
	protected $user_roles = array();
	protected $all_roles = array();
	protected $all_subjects = array();
	protected $translations = array (
		'Registered' => 'Registered(translated)',
		'Visitor' => 'Visitor(translated)',
		'Nobody' => 'Nobody(translated)',
		'none' => 'None of these(trans)'
		);
	protected $cleartime = 0;

	protected function __construct () {
		// Making protected enforces singleton
		$this->loadData();
		$this->cleartime = time();
	}
	
	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = parent::getCachedSingleton(__CLASS__));
	}

	protected function loadData () {
		$database = aliroCoreDatabase::getInstance();
		$this->loadLinkData($database);
		$this->loadAssignmentData($database);
		$this->loadPermissionData($database);
	}

	protected function loadLinkData ($database) {
		$database->setQuery($this->loadLinkSQL());
		$links = $database->loadObjectList();
		if ($links) foreach ($links as $link) {
			$this->all_roles[$link->role] = $link->role;
			$this->linked_roles[$link->role][$link->implied] = 1;
			foreach ($this->linked_roles as $role=>$impliedarray) {
				foreach ($impliedarray as $implied=>$marker) {
					if ($implied == $link->role OR $implied == $link->implied) {
						$this->linked_roles[$role][$link->implied] = 1;
						if (isset($this->linked_roles[$link->implied])) foreach ($this->linked_roles[$link->implied] as $more=>$marker) {
							$this->linked_roles[$role][$more] = 1;
						}
					}
				}
			}
		}
	}

	protected function loadLinkSQL () {
		return "SELECT role, implied FROM #__role_link UNION SELECT DISTINCT role, role AS implied FROM #__assignments UNION SELECT DISTINCT role, role AS implied FROM #__permissions";
	}

	protected function loadAssignmentData ($database) {
		$user_roles = $database->doSQLget("SELECT role, access_id FROM #__assignments WHERE access_type = 'aUser' AND (access_id = '*' OR access_id = '0')");
		foreach ($user_roles as $role) $this->user_roles[$role->access_id][$role->role] = 1;
		if (!isset($this->user_roles['0'])) $this->user_roles['0'] = array();
		if (isset($this->user_roles['*'])) $this->user_roles['0'] = array_merge($this->user_roles['0'], $this->user_roles['*']);
	}

	protected function loadPermissionData ($database) {
		$allsubject = $database->doSQLget("SELECT role, control, action FROM #__permissions WHERE subject_type = '*' AND subject_id - '*'");
		foreach ($allsubject as $asub) $this->all_subjects[$asub->role] = $asub;
	}
	
	public function getClearTime () {
		return $this->cleartime;
	}

	public function getTranslatedRole ($role) {
		if (isset($this->translations[$role])) return $this->translations[$role];
		else return $role;
	}
	
	public function getAllRoles ($addSpecial=false) {
		$roles = $this->all_roles;
		if ($addSpecial) foreach ($this->translations as $raw=>$translated) $roles[$raw] = $translated;
		return $roles;
	}
	
	public function getOrdinaryRoles () {
		$roles = $this->getAllRoles();
		foreach (array_keys($this->translations) as $raw) if (isset($roles[$raw])) unset($roles[$raw]);
		if (isset($roles['Super Administrator'])) unset ($roles['Super Administrator']);
		return $roles;
	}

	public function barredRole ($role) {
		if (isset($this->translations[$role])) return true;
		else return false;
	}
	
	public function getBarredRoles () {
		return array_keys($this->translations);
	}

	public function getLinkedRoles () {
	    return $this->linked_roles;
	}

	public function getUserRoles ($id) {
	    return isset($this->user_roles[$id]) ? array_keys($this->user_roles[$id]) : array();
	}
	
	public function canRoleAccessAll ($role, $action, $control) {
		if (isset($this->all_subjects[$role])) {
			$asub = $this->all_subjects[$role];
			if ($action == $asub->action AND ($control & $asub->control)) return true;
		}
		return false;
	}

}


class aliroAuthoriser {
	protected static $instance = null;

	protected $cacheclass = 'aliroAuthoriserCache';

	protected $subj_found = array();
	protected $permissions = array();
	protected $access_found = array();
	protected $access_roles = array();
	protected $refused = array();

	protected $linked_roles = array();
	protected $auth_vars = array ('subj_found', 'permissions', 'access_found', 'access_roles', 'refused');
	protected $old_groupids = array ('Registered' => 18, 'Author' => 19, 'Editor' => 20, 'Publisher' => 21, 'Manager' => 23, 'Administrator' => 24, 'Super Administrator' => 25);

	protected $handler = null;
	protected $database = null;
	protected $visitor_cache = null;
	protected $cache_data = array();
	protected $myid = 0;

	protected function __construct () {
		$this->myid = $this->getUserID();
		$this->handler = call_user_func(array($this->cacheclass, 'getInstance'));
		if ($this->myid) {
			// Use session data as the source for cached user related data
			foreach ($this->auth_vars as $one_var) {
				if (!isset($_SESSION['__aliro_auth'][$one_var])) $_SESSION['__aliro_auth'][$one_var] = array();
				$this->$one_var =& $_SESSION['__aliro_auth'][$one_var];
			}
		}
		else {
			$this->visitor_cache = new aliroSimpleCache('aliroAuthoriser');
			$this->cache_data = $this->visitor_cache->get('visitorData');
			if (!is_array($this->cache_data)) {
				foreach ($this->auth_vars as $one_var) $this->cache_data[$one_var] = array();
			}
			foreach ($this->auth_vars as $one_var) $this->$one_var =& $this->cache_data[$one_var];
		}
		if (!isset($_SESSION['__aliro_auth']['timer']) OR _ALIRO_AUTHORISER_SESSION_CACHE_TIME < (time() - $_SESSION['__aliro_auth']['timer'])) {
			$this->clearCache();
		}
		elseif ($_SESSION['__aliro_auth']['timer'] < $this->handler->getClearTime()) $this->clearCache(false);
		$this->linked_roles = $this->handler->getLinkedRoles();
		$this->database = aliroCoreDatabase::getInstance();
	}
	
	protected function __clone () {
		// Enforce singleton class
	}

	public static function getInstance () {
	    return self::$instance instanceof self ? self::$instance : self::$instance = new self();
	}

	// CMS Specific method that may need to be overriden in other environments
	protected function getUserID () {
		$user = call_user_func(array('aliroUser', 'getInstance'));
		return $user->id;
	}

	// Only intended for the user of aliroAuthorisationAdmin
	public function getCache () {
		return $this->handler;
	}

	public function clearCache ($clearHandler = true) {
		if ($clearHandler) $this->handler->clearCache(true);
		foreach ($this->auth_vars as $one_var) $this->$one_var = array();
		if (0 == $this->myid) $this->visitor_cache->clean();
		if (isset($_SESSION)) $_SESSION['__aliro_auth']['timer'] = time();
	}

	public function getAllRoles ($addSpecial=false) {
		return $this->handler->getAllRoles($addSpecial);
	}
	
	public function getOrdinaryRoles () {
		return $this->handler->getOrdinaryRoles();
	}
	
	public function getBarredRoles () {
		return $this->handler->getBarredRoles();
	}

	public function getTranslatedRole ($role) {
		return $this->handler->getTranslatedRole($role);
	}

	// This is a helper method to assist applications handling roles
	public function unpackRoleList ($string) {
		$utroles = explode(',', $string);
		foreach ($utroles as $role) {
			$role = trim($role);
			if ($role) $results[$role] = $this->getTranslatedRole($role);
		}
		return isset($results) ? $results : array();
	}

	// Another helper method for handling sets of roles
	public function minimizeRoleSet ($roleset) {
		if (empty($roleset)) return array();
		$roleset = array_unique($roleset);
		if (empty($this->linked_roles)) return $roleset;
		$first = array_shift($roleset);
		foreach ($roleset as $key=>$role) {
			if (isset($this->linked_roles[$first][$role])) unset ($roleset[$key]);
			if (isset($this->linked_roles[$role][$first])) return $this->minimizeRoleSet ($roleset);
		}
		array_unshift($roleset, $first);
		return $roleset;
	}

	// The formal name is to aid in uniqueness, and the property name then only has to be unique within
	// a particular application.
	public function getRolePropertiesForAccessor ($access_type, $access_id, $formalname, $property) {
		$this->database->setQuery("SELECT value FROM #__role_properties AS p INNER JOIN #__assignments AS a ON p.role = a.role"
		." WHERE a.access_type = '$access_type' AND a.access_id = '$access_id' AND p.formalname = '$formalname' AND p.property = '$property'"
		);
		$results = $this->database->loadResultArray();
		if ($results) {
			$results = array_map('base64_decode', $results);
			return array_map('unserialize', $results);
		}
		return array();
	}

	protected function getSubjectData ($subject, $id, $action) {
		$stamp = time();
		if (isset($this->subj_found[$subject][$action][$id]) AND (($stamp - $this->subj_found[$subject][$action][$id]) < _ALIRO_AUTHORISER_SESSION_CACHE_TIME)) return;
		if (isset($this->subj_found[$subject][$action]['*']) AND ($stamp - $this->subj_found[$subject][$action]['*'] < _ALIRO_AUTHORISER_SESSION_CACHE_TIME)) return;
		$this->database->setQuery("SELECT COUNT(*) FROM `#__permissions` WHERE `subject_type`='$subject' AND (`action`='$action' OR `action`='*')");
		$subject_count = $this->database->loadResult();
		if (0 == $subject_count) {
			$this->subj_found[$subject][$action]['*'] = $stamp;
		}
		elseif ($subject_count < 100) {
			$new_permissions = $this->database->doSQLget("SELECT `role`, `control`, `subject_id`, `action` FROM `#__permissions` WHERE `subject_type`='$subject' AND (`action`='$action' OR `action`='*')");
			unset($this->subj_found[$subject][$action]);
			$this->subj_found[$subject][$action]['*'] = $stamp;
		}
		else {
			$new_permissions = $this->database->doSQLget("SELECT role, control, subject_id, action FROM #__permissions WHERE subject_type='$subject' AND (subject_id='$id' OR subject_id='*') AND (action='$action' OR action='*')");
			unset($this->subj_found[$subject][$action][$id]);
		}
		if (!empty($new_permissions)) foreach ($new_permissions as $permit) {
			$this->permissions[$subject][$permit->action][$permit->subject_id][$permit->role] = $permit->control;
			$this->subj_found[$subject][$permit->action][$permit->subject_id] = $stamp;
		}
		if (0 == $this->myid) $this->visitor_cache->save($this->cache_data);
	}

	public function getAccessorRoles ($type, $id) {
	    if ('aUser' == $type AND ('0' == $id OR '*' == $id)) return $this->handler->getUserRoles($id);
		if (isset($this->access_found[$type][$id])) {
			if ((time() - $this->access_found[$type][$id]) < _ALIRO_AUTHORISER_SESSION_CACHE_TIME) {
				return $this->mergeAccessorResults($type, $id);
			}
			unset ($this->access_found);
			$this->access_roles = array();
		}
		$results = $this->database->doSQLget($this->accessorRoleSQL($type, $id));
		foreach ($results as $result) {
			$this->access_roles[$type][$result->access_id][$result->role] = 1;
		}
		$this->access_found[$type][$id] = time();
		if (0 == $this->myid) $this->visitor_cache->save($this->cache_data);
		return $this->mergeAccessorResults($type, $id);
	}

	protected function accessorRoleSQL ($type, $id) {
		$sql = "SELECT role, access_id FROM #__assignments AS a WHERE a.access_type='$type'";
		$sql .= isset($this->access_found[$type]) ? " AND a.access_id='$id'" : " AND (a.access_id='$id' OR a.access_id='*' OR a.access_id='+')";
		return $sql;
	}

	protected function mergeAccessorResults ($type, $id) {
		if (isset($this->access_roles[$type][$id])) $result = $this->access_roles[$type][$id];
		else $result = array();
		if (isset($this->access_roles[$type]['*'])) $result = array_merge($result, $this->access_roles[$type]['*']);
		if ($id AND isset($this->access_roles[$type]['+'])) $result = array_merge($result, $this->access_roles[$type]['+']);
		if ('aUser' == $type AND $id) $result['Registered'] = 1;
		return count($result) ? array_keys ($result) : array();
	}

	protected function blanket ($action, $type) {
		return (!empty($this->permissions[$type][$action]['*']));
	}

	protected function specific ($action, $type, $id) {
		return (!empty($this->permissions[$type][$action][$id]));
	}

	protected function accessorPermissionOrControl  ($mask, $a_type, $a_id, $action, $s_type='*', $s_id='*') {
		$this->getSubjectData ($s_type, $s_id, $action);
		if ('*' != $s_type AND 2 == $mask AND !$this->blanket($action, $s_type) AND !($this->specific($action, $s_type, $s_id))) return 1;
		if ((!isset($this->permissions[$s_type][$action][$s_id]) OR 0 == count($this->permissions[$s_type][$action][$s_id]))
		AND (!isset($this->permissions[$s_type][$action]['*']) OR 0 == count($this->permissions[$s_type][$action]['*']))) return 1;
		$roles = $this->getAccessorRoles ($a_type, $a_id);
		return $this->rolePermissionOrControl ($mask, $roles, $action, $s_type, $s_id);
	}

	// Purely provided to help with debugging problems, not for live use
	public function explain  ($mask, $a_type, $a_id, $action, $s_type='*', $s_id='*') {
		$this->getSubjectData ($s_type, $s_id, $action);
		if ('*' == $s_type) var_dump($this->permissions);
		elseif ('*' == $action) var_dump($this->permissions[$s_type]);
		elseif ('*' == $s_id) var_dump($this->permissions[$s_type][$action]);
		else var_dump($this->permissions[$s_type][$action][$s_id]);
		if ('*' != $s_type AND 2 == $mask AND !$this->blanket($action, $s_type) AND !($this->specific($action, $s_type, $s_id))) {
			echo '<br />Permission is granted: because there is no blanket permission and there is no specific permission, therefore there is no restriction';
			return;
		}
		if (empty($this->permissions[$s_type][$action][$s_id]) AND empty($this->permissions[$s_type][$action]['*'])) {
			echo '<br />Permission is granted: because there are no permissions on the subject type and action along with either the subject ID or *';
			return;
		}
		$roles = $this->getAccessorRoles ($a_type, $a_id);
		echo '<br />Here is a list of roles for the accessor: ';
		var_dump($roles);
		foreach ((array) $roles as $role) {
			foreach ((array) $action as $anaction) if ($this->handler->canRoleAccessAll ($role, $anaction, $mask)) {
				echo "<br />Permission is granted: role $role can access all";
				return;
			}
		}
		foreach ((array) $action as $anaction) $this->getSubjectData ($s_type, $s_id, $anaction);
		if (in_array('Visitor', (array) $roles)) foreach ((array) $action as $anaction) {
			if (empty($this->permissions[$s_type][$anaction][$s_id])) {
				echo '<br />Permission is granted: there are no permissions for this subject type and ID and action';
				return;
			}
		}
		if (count((array) $roles)) foreach ($this->permissions[$s_type] as $act=>$level2) {
			if (!in_array($act, (array) $action) AND !in_array('*', (array) $action)) continue;
			foreach ($level2 as $id=>$level3) {
				if ($id != $s_id AND $id != '*') continue;
				foreach ($level3 as $role=>$control) if (in_array($role, (array) $roles) AND ($mask & $control)) {
					echo "<br />Permission is granted: action $act, ID $id, role $role ";
					return;
				}
			}
		}
		echo '<br />Permission is not granted';
	}
	
	public function checkPermission ($a_type, $a_id, $action, $s_type='*', $s_id='*') {
		return $this->accessorPermissionOrControl(2, $a_type, $a_id, $action, $s_type, $s_id);
	}

	public function checkUserPermission ($action, $s_type='*', $s_id='*') {
		return $this->checkPermission ('aUser', $this->myid, $action, $s_type, $s_id);
	}

	public function checkControl ($a_type, $a_id, $action, $s_type='*', $s_id='*') {
		return $this->accessorPermissionOrControl(1, $a_type, $a_id, $action, $s_type, $s_id);
	}

	public function checkGrant ($a_type, $a_id, $action, $s_type='*', $s_id='*') {
		return $this->accessorPermissionOrControl(4, $a_type, $a_id, $action, $s_type, $s_id);
	}

	protected function rolePermissionOrControl ($mask, $roles, $actions, $s_type, $s_id) {
		foreach ((array) $roles as $role) {
			foreach ((array) $actions as $action) if ($this->handler->canRoleAccessAll ($role, $action, $mask)) return 1;
		}
		foreach ((array) $actions as $action) $this->getSubjectData ($s_type, $s_id, $action);
		if (in_array('Visitor', (array) $roles)) foreach ((array) $actions as $action) {
			if (empty($this->permissions[$s_type][$action][$s_id])) return 1;
		}
		if (count((array) $roles)) foreach ($this->permissions[$s_type] as $act=>$level2) {
			if (!in_array($act, (array) $actions) AND !in_array('*', (array) $actions)) continue;
			foreach ($level2 as $id=>$level3) {
				if ($id != $s_id AND $id != '*') continue;
				foreach ($level3 as $role=>$control) if (in_array($role, (array) $roles) AND ($mask & $control)) {
					return 1;
				}
			}
		}
		return 0;
	}

	public function checkRolePermission  ($role, $action, $s_type, $s_id) {
		return $this->rolePermissionOrControl(2, $role, $action, $s_type, $s_id);
	}

	public function checkRoleControl  ($role, $action, $s_type, $s_id) {
		return $this->rolePermissionOrControl(1, $role, $action, $s_type, $s_id);
	}

	public function checkRoleGrant  ($role, $action, $s_type, $s_id) {
		return $this->rolePermissionOrControl(4, $role, $action, $s_type, $s_id);
	}

	function getRefusedList ($a_type, $a_id, $s_type, $actionlist) {
		$roles = $this->getAccessorRoles($a_type, $a_id);
		$actions = array_map('trim', explode(',', $actionlist));
		$alist = implode("','", $actions);
		if (isset($this->refused[$s_type][$alist])) $ids = $this->refused[$s_type][$alist];
		else {
			$ids = array();
			$results = $this->database->doSQLget("SELECT role, subject_id, action FROM #__permissions WHERE subject_type = '$s_type' AND action IN('$alist')");
			foreach ($results as $result) $ids[$result->subject_id][$result->action][] = $result->role;
			$this->refused[$s_type][$alist] = $ids;
			if (0 == $this->myid) $this->visitor_cache->save($this->cache_data);
		}
		if (count($ids)) {
			$refused = array_keys($ids);
			foreach ($ids as $id=>$actionset) {
				foreach ($actions as $action) if (!isset($actionset[$action])) $permits[$id] = 1;
				if (!isset($permits[$id])) foreach ($actionset as $action=>$permittedroles) {
					if (count(array_intersect($permittedroles, $roles))) $permits[$id] = 1;
				}
			}
			if (isset($permits)) $refused = array_diff ($refused, array_keys($permits));
		}
		else $refused = array();
		return $refused;
	}

	public function getRefusedListSQL ($a_type, $a_id, $s_type, $actionlist, $keyname, $abs=false) {
		$refused = $this->getRefusedList ($a_type, $a_id, $s_type, $actionlist);
		if (count($refused)) {
			$excludelist = implode("','", $refused);
			$keycode = $abs ? "abs($keyname)" : $keyname;
			return " CAST($keycode AS CHAR) NOT IN ('$excludelist')";
		}
		return '';
	}

	public function listPermissions ($a_type, $a_id, $action) {
		$roles = $this->getAccessorRoles ($a_type, $a_id);
		$role_list = "IN ('".implode("','", $roles)."')";
		$this->database->setQuery("SELECT DISTINCT subject_type FROM #__permissions WHERE role $role_list AND action='$action' AND (control & 2) ORDER BY subject_type");
		$subjects = $this->database->loadResultArray();
		return $subjects;
	}

	public function &listUserPermissions ($action) {
		$results = $this->listPermissions ('aUser', $this->myid, $action);
		return $results;
	}

	public function listAccessors ($accessor_type, $role) {
		$this->database->setQuery($this->accessorSQL($accessor_type, $role));
		$result = $this->database->loadResultArray();
		return $result ? $result : array();
	}

	protected function accessorSQL ($accessor_type, $role) {
		return "SELECT access_id FROM #__assignments WHERE access_type = '$accessor_type' AND role = '$role'";
	}

	public function doesAccessorHaveRole ($accessor_type, $accessor_id, $role) {
		$this->database->setQuery("SELECT COUNT(*) FROM #__assignments WHERE access_type = '$accessor_type' AND access_id = '$accessor_id' AND role = '$role'");
		return $this->database->loadResult() ? true : false;
	}
	
	public function listAccessorsToSubject ($subject_type, $subject_id, $accessor_type, $action="*") {
		$subject_id = intval($subject_id);
		if (!empty($action) AND (is_array($action) OR '*' != $action)) $actions = "'".implode("','", (array) $action)."'";
		else $actions = '';
		if (!empty($subject_id) AND (is_array($subject_id) OR '*' != $subject_id)) $subject_ids = "'".implode("','", (array) $subject_id)."'";
		else $subject_ids ='';
		$sql = $this->accessorsToSubjectInitialSQL($subject_type, $accessor_type);
		if ($actions) $sql .= " AND p.action IN ($actions)";
		if ($subject_ids) $sql .= " AND p.subject_id IN ($subject_ids)";
		$this->database->setQuery($sql);
		return $this->database->loadResultArray();
	}

	protected function accessorsToSubjectInitialSQL ($subject_type, $accessor_type) {
		$sql = "SELECT DISTINCT a.access_id FROM #__assignments AS a INNER JOIN #__permissions AS p ON a.role = p.role";
		$sql .= " WHERE (control & 2) AND p.subject_type = '$subject_type' AND a.access_type = '$accessor_type'";
		return $sql;
	}
}

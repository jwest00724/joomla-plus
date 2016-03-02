<?php

/* ******************************************************************
* This file is a generic interface to Aliro, Joomla 1.5+, Joomla 1.0.x and Mambo
* Copyright (c) 2008 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://acmsapi.org
* To contact Martin Brampton, write to martin@remository.com
*
*/

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

class cmsapiInstaller {
	protected $http = null;

	// CMS Specific code
	protected function T_ ($string) {
		return $string;
	}

	// This method is specific to Jaliro, not for general use
	public function handleUserPass () {
		$interface = cmsapiInterface::getInstance('com_cmsapi');
		$jalirouser = $interface->getParam($_POST, 'jaliro_user');
		$jaliropass = $interface->getParam($_POST, 'jaliro_pass');
		$jaliroconfig = aliroComponentConfiguration::getConfiguration('plugin_jaliro');
		if ($jalirouser OR $jaliropass) {
			if ($jalirouser) $jaliroconfig->user = $jalirouser;
			if ($jaliropass) $jaliroconfig->password = $jaliropass;
			$jaliroconfig->save();
		}
		if (!(@$jaliroconfig->user AND @$jaliroconfig->password)) return false;
		$codeduser = base64_encode($jaliroconfig->user);
		$codedpass = base64_encode($jaliroconfig->password);
		$codedsite = base64_encode($interface->getCfg('live_site'));
		return "Authorization: JALIRO $codedsite:$codeduser:$codedpass";
	}

	public function getFileData ($url, $security='') {
		$this->http = new httpRequest();
		if ($security) $this->http->header($security);
		$contents = $this->http->get($url);
		$result = $this->http->getHttpStatus();
		if (200 != $result) {
			JError::raiseError($result, $this->T_('SERVER_CONNECT_FAILED').', '.$result);
			JError::refuseMore();
			return false;
		}
		return $contents;
	}

	public function storeFile ($contents, $url) {
		if ($this->http instanceof httpRequest) {
			$response_headers = $this->http->getHeaders();
			foreach ($response_headers as $header) {
				if (0 === strpos($header, 'Content-Disposition')) {
					$contentfilename = explode ("\"", $header);
					if (isset($contentfilename[1])) {
						$target = $contentfilename[1];
						break;
					}
				}
			}
		}
		// Set the target path if not given
		$config =& JFactory::getConfig();
		if (empty($target)) $target = $config->getValue('config.tmp_path').DS.JInstallerHelper::getFilenameFromURL($url);
		else $target = $config->getValue('config.tmp_path').DS.basename($target);

		// Write buffer to file
		JFile::write($target, $contents);
		return $target;
	}

	public function unpack ($name) {
		$tmp_dest 	= JFactory::getConfig()->getValue('config.tmp_path');
		// Unpack the downloaded package file
		$package = JInstallerHelper::unpack($tmp_dest.DS.$name);
		return $package;
	}

	public function installPackage ($package) {
		$jinstaller =& JInstaller::getInstance();
		// Install the package
		return $jinstaller->install($package['dir']);
	}

	public function cleanUp ($package) {
		// Cleanup the install files
		if (!is_file($package['packagefile'])) {
			$config =& JFactory::getConfig();
			$package['packagefile'] = $config->getValue('config.tmp_path').DS.$package['packagefile'];
		}
		JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
	}

	public function securityFieldsHTML () {
		$usertext = $this->T_( 'USERNAME' );
		$passtext = $this->T_( 'PASSWORD' );
		$username = @aliroComponentConfiguration::getConfiguration('plugin_jaliro')->user;
		$password = @aliroComponentConfiguration::getConfiguration('plugin_jaliro')->password;
		return <<<SECURITY_FIELDS

			<label for="jaliro_user"> Jaliro $usertext:</label>
			<input class="input_box" id="jaliro_user" name="jaliro_user" type="text" size="20" value="$username" />
			<label for="jaliro_pass"> Jaliro $passtext:</label>
			<input class="input_box" id="jaliro_pass" name="jaliro_pass" type="password" size="20" value="$password" />

SECURITY_FIELDS;

	}

	public function installModulesPlugins ($manifest, $remove=false) {
		// Set the installation path
		// For the time being handle only user side modules
		$mtype = 'user';
		$files = $manifest->getElementByPath('files');
		$installer = JInstaller::getInstance();
		if (is_object($files)) foreach ($files->children() as $file) {
			if ('tar' == $file->attributes('filetype')) {
				$filepath = $installer->getPath('extension_site').'/'.$file->data();
				$remove = $file->attributes('remove');
				if ($remove) {
					$oldfolders = explode(',', $remove);
					clearstatcache();
					foreach ($oldfolders as $oldfolder) {
						$olddir = dirname($filepath).'/'.$oldfolder;
						if (is_dir($olddir)) JFolder::delete($olddir);
					}
				}
				if (!$remove) {
					$archive = new Archive_Tar($filepath);
					$archive->setErrorHandling( PEAR_ERROR_PRINT );

					if ($archive->extractModify(dirname($filepath), '')) @unlink($filepath);
					else die (sprintf($this->T_('Installer unrecoverable TAR error in %s'), $filepath));
				}
			}
		}
		$modules = $manifest->getElementByPath('modules');
		$ROOT_PATH = 'admin' == $mtype ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$database = aliroDatabase::getInstance();
		if (is_object($modules)) foreach ($modules->children() as $module) {
			$files = $module->getElementByPath('files');
			$names = $files->children();
			foreach ($names as $filename) {
				$modname = $filename->attributes('module');
				if ($remove) {
					if (!empty($modname)) $modulekeys[] = $modname;
					@unlink($ROOT_PATH.'/modules/'.$modname.'/'.$filename->data());
				}
				else {
					if (!empty ($modname)) {
						$installer->setPath('extension_root', $ROOT_PATH.'/modules/'.$modname);
						$message = $this->installFiles($files, 'module');
						if ($message) return '<br />'.$message;
						$database->setQuery("SELECT COUNT(*) FROM #__modules WHERE module = '$modname'");
						if (0 == $database->loadResult()) {
							$message = $this->makeModule($modname);
							if ($message) return '<br />'.$message;
							echo '<br />'.sprintf($this->T_('Module %s has been installed'), $modname);
						}
						else echo '<br />'.sprintf($this->T_('Module %s has been updated'), $modname);
					}
				}
			}
		}
		if (!empty($modulekeys)) {
			$modulelist = implode("','", $modulekeys);
			$database->doSQL("DELETE FROM #__modules WHERE module IN ('$modulelist')");
		}

		$plugins = $manifest->getElementByPath('plugins');
		if (is_object($plugins)) foreach ($plugins->children() as $plugin) {
			$groupname = $plugin->attributes('group');
			$title = $plugin->attributes('title');
			$files = $plugin->getElementByPath('files');
			$names = $files->children();
			foreach ($names as $filename) {
				$plugname = $filename->attributes('plugin');
				if ($remove) {
					if (!empty($plugname)) $pluginkeys[] = $plugname;
					@unlink(JPATH_SITE.'/plugins/'.$groupname.'/'.$filename->data());
				}
				else {
					if (!empty ($plugname) AND !empty($groupname)) {
						$installer->setPath('extension_root', JPATH_SITE.'/plugins/'.$groupname);
						$message = $this->installFiles($files, 'plugin');
						if ($message) return '<br />'.$message;
						$database->setQuery("SELECT COUNT(*) FROM #__plugins WHERE element = '$plugname'");
						if (0 == $database->loadResult()) {
							$message = $this->makePlugin($plugname, $groupname, $title);
							if ($message) return '<br />'.$message;
							echo '<br />'.sprintf($this->T_('Plugin %s has been installed'), $plugname);
						}
						else echo '<br />'.sprintf($this->T_('Plugin %s has been updated'), $plugname);
					}
				}
			}
		}
		if (!empty($pluginkeys)) {
			$pluginlist = implode("','", $pluginkeys);
			$database->doSQL("DELETE FROM #__plugins WHERE element IN ('$pluginlist')");
		}
	}

	private function installFiles ($element, $type) {
		$installer = JInstaller::getInstance();
		/*
		 * If the directory already exists, then we will assume that the
		 * add-on is already installed or another add-on is using that
		 * directory.
		 */
		if ('module' == $type AND file_exists($installer->getPath('extension_root')) AND !$installer->getOverwrite()) {
			$installer->abort($this->T_('Module').' '.$this->T_('Install').': '.$this->T_('Another module is already using directory').': "'.$installer->getPath('extension_root').'"');
			return sprintf($this->T_('Another %s is already using directory'), $type);
		}
		// If the add-on directory does not exist, lets create it
		$created = false;
		if (!file_exists($installer->getPath('extension_root'))) {
			if (!$created = JFolder::create($installer->getPath('extension_root'))) {
				$installer->abort($this->T_($type).' '.$this->T_('Install').': '.$this->T_('Failed to create directory').': "'.$installer->getPath('extension_root').'"');
				return $this->T_('Failed to create directory');
			}
		}
		/*
		 * Since we created the module directory and will want to remove it if
		 * we have to roll back the installation, lets add it to the
		 * installation step stack
		 */
		if ($created) {
			$installer->pushStep(array ('type' => 'folder', 'path' => $installer->getPath('extension_root')));
		}

		// Copy all necessary files
		if ($installer->parseFiles($element, -1) === false) {
			// Install failed, roll back changes
			$installer->abort();
			return $this->T_('Unable to parse files for copying');
		}
	}

	private function removeFiles ($element, $type) {

	}

	private function makeModule ($modname) {
		$clientId = 0;
		$row = JTable::getInstance('module');
		$row->title = $modname;
		$row->ordering = $row->getNextOrder( "position='left'" );
		$row->position = 'left';
		$row->showtitle = 1;
		$row->iscore = 0;
		$row->access = $clientId == 1 ? 2 : 0;
		$row->client_id = $clientId;
		$row->module = $modname;
		$row->published = 0;
		$row->params = '';
		if (!$row->store()) {
			// Install failed, roll back changes
			JInstaller::getInstance()->abort($this->T_('Module').' '.$this->T_('Install').': '.$db->stderr(true));
			return $this->T_('Unable to write module information to database');
		}
	}

	private function makePlugin ($plugname, $groupname, $title) {
	    $row = JTable::getInstance('plugin');
	    $row->name = $title;
	    $row->ordering = 0;
	    $row->folder = $groupname;
	    $row->iscore = 0;
	    $row->access = 0;
	    $row->client_id = 0;
	    $row->element = $plugname;
	    $row->published = 1;
	    $row->params = '';

	    if (!$row->store()) {
	        // Install failed, roll back changes
	        JInstaller::getInstance()->abort($this->T_('Plugin').' '.$this->T_('Install').': '.$db->stderr(true));
	        $status->errmsg[]=$this->T_('Unable to write plugin information to database');
			return $status;

	    }
	}
}
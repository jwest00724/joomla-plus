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
 * aliroFileManager is the singleton class to insulate Aliro from actual file system
 * operations.  This gives the option to elaborate the class to handle issues such as
 * coping with safe mode.  The class also provides a utility method "forceCopy" (used
 * by the installer) to make a copy happen if at all possible.  Some old methods are
 * kept here for backwards compatibility, but they really need review.
 *
 * aliroDirectory allows the creation of an object that corresponds to a directory in
 * the file system, and then provides a number of useful methods to manipulate the
 * directory and its contents, including finding out what the contents are.
 *
 * Please note that although aliroFileManager is declared as a final class in Aliro,
 * it is designed to be capable of being subclassed for use in other systems.
 *
 * aliroDirectory is likewise written to be capable of being subclassed.
 *
 */

final class aliroFileManager {

	private static $instance = null;
	private static $tempdirs = array();

	protected function __construct () {
		// Enforce singleton
	}

	public function __destruct () {
		foreach (self::$tempdirs as $tempdir) {
			$dir = $this->makeDirectory($tempdir);
			$dir->deleteAll();
		}
	}

	protected function __clone () {
		// Enforce singleton
	}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self());
	}

	protected function makeDirectory ($dirpath) {
		return new aliroDirectory($dirpath);
	}

    public function deleteFile ($file) {
        return @unlink($file);
    }

	// First parameter is full path to file, second parameter is directory, no trailing slash
	public function moveFile ($file, $destination) {
		rename ($file, $destination.'/'.basename($file));
	}

    public function deleteDirectory ($dir) {
    	clearstatcache();
        if (file_exists($dir)) {
            if (is_dir($dir)) {
                @chmod($dir, 0755);
                return rmdir($dir);
            }
            return false;
        }
        return true;
    }

    public function setPermissions ($fileSysObject, $mode=null) {
    	$result = true;
    	if (file_exists($fileSysObject))  {
    		if ($mode);
    		elseif (is_dir($fileSysObject)) $mode = $this->dirPermissions();
    		else $mode = $this->filePermissions();
    		if ($mode) {
		    	$origmask = @umask(0);
		    	$result = @chmod($fileSysObject, $mode);
				@umask($origmask);
			}
		}
		return $result;
	}

	protected function dirPermissions () {
		global $mosConfig_dirperms;
		return $mosConfig_dirperms;
		return aliro::getInstance()->installed ? octdec(aliroCore::getInstance()->getCfg('dirperms')) : 0755;
	}

	protected function filePermissions () {
		global $mosConfig_fileperms;
		return $mosConfig_fileperms;
		return aliro::getInstance()->installed ? octdec(aliroCore::getInstance()->getCfg('fileperms')) : 0644;
	}

    public function createDirectory ($dir, $onlyCheck=false) {
        if (file_exists($dir)) {
            if (is_dir($dir) AND is_writable($dir)) return true;
            else return false;
        }
        list($upDirectory, $count) = $this->containingDirectory($dir);
        if ($count > 1 AND !file_exists($upDirectory) AND !($result = $this->createDirectory($upDirectory, $onlyCheck))) return false;
        if ($onlyCheck AND isset($result)) return true;
        if (!is_dir($upDirectory) OR !is_writable($upDirectory)) return false;
        if ($onlyCheck) return true;
		$result = @mkdir($dir, 0755);
		if ($result) $this->setPermissions($dir);
		return $result;
    }

    protected function containingDirectory ($dir) {
        $dirs = preg_split('*[/|\\\]*', $dir);
        for ($i = count($dirs)-1; $i >= 0; $i--) {
            $text = trim($dirs[$i]);
            unset($dirs[$i]);
            if ($text) break;
        }
        $result2 = count($dirs);
        $result1 = implode('/',$dirs).($result2 > 1 ? '' : '/');
        return array($result1, $result2);
    }

    public function simpleCopy ($from, $to, $mode=null) {
        if (@copy($from, $to)) {
            $this->setPermissions($to, $mode);
            return true;
        }
        else return false;
    }

    public function forceCopy ($from, $to, $reportErrors=false) {
    	if (!file_exists($from)) {
        	if ($reportErrors) $this->setErrorMessage (sprintf(T_('Copy requested for %s, but source file not found'), $from), _ALIRO_ERROR_WARN);
        	return false;
    	}
        $todir = dirname($to);
        if (!file_exists($todir)) $this->createDirectory($todir);
        if (!file_exists($todir)) {
        	if ($reportErrors) $this->setErrorMessage (sprintf(T_('Copy requested for %s, but could not create destination directory'), $from), _ALIRO_ERROR_WARN);
        	return false;
        }
        $name = basename($from);
        $this->deleteFile($to.$name);
        if ($this->simpleCopy ($from, $to)) return true;
        elseif ($reportErrors) $this->setErrorMessage (sprintf(T_('Copy requested for %s, source found but could not copy to %s'), $from, $to), _ALIRO_ERROR_WARN);
        return false;
    }

	protected function setErrorMessage ($message, $severity) {
		aliroRequest::getInstance()->setErrorMessage($message, $severity);
	}

    public function lightCopy ($from, $to) {
        $name = basename($from);
        if (file_exists($to.$name)) return false;
        $todir = dirname($to);
        if (!file_exists($todir)) $this->createDirectory($todir);
        if (!file_exists($todir)) return false;
        return $this->simpleCopy ($from, $to);
    }

    public function acceptCopy ($to) {
        $todir = dirname($to);
        return $this->createDirectory($todir, true);
    }

	// Provided for compatibility - not really needed in full
	// PHP is happy with paths containing forward or backwards slashes, or even a mixture
	// Aliro normally converts all paths to use forward slashes for tidiness
    public function mosPathName($p_path, $p_addtrailingslash=true) {
        if (substr(PHP_OS, 0, 3) == 'WIN')	{
            $retval = str_replace( '/', '\\', $p_path );
            if ($p_addtrailingslash AND substr( $retval, -1 ) != '\\') $retval .= '\\';
            // Remove double \\
            $retval = str_replace( '\\\\', '\\', $retval );
        }
        else {
            $retval = str_replace( '\\', '/', $p_path );
            if ($p_addtrailingslash AND substr( $retval, -1 ) != '/') $retval .= '/';
            // Remove double //
            $retval = str_replace('//','/',$retval);
        }
        return $retval;
    }

    /**
	* Chmods files and directories recursively to mos global permissions. Available from 4.5.2 up.
	* @param path The starting file or directory (no trailing slash)
	* @param filemode Integer value to chmod files. NULL = dont chmod files.
	* @param dirmode Integer value to chmod directories. NULL = dont chmod directories.
	* @return TRUE=all succeeded FALSE=one or more chmods failed
	*/
    public function mosChmod($path)
    {
        $filemode = $this->filePermissions();
        $dirmode = $this->dirPermissions();
        if ($filemode OR $dirmode) return $this->mosChmodRecursive($path, $filemode, $dirmode);
        return true;
    } // mosChmod

    /**
	* Chmods files and directories recursively to given permissions. Available from 4.5.2 up.
	* @param path The starting file or directory (no trailing slash)
	* @param filemode Integer value to chmod files. NULL = dont chmod files.
	* @param dirmode Integer value to chmod directories. NULL = dont chmod directories.
	* @return TRUE=all succeeded FALSE=one or more chmods failed
	*/
    public function mosChmodRecursive($path, $filemode=NULL, $dirmode=NULL) {
        $ret = true;
        if (is_dir($path)) {
            $topdir = $this->makeDirectory($path);
            $files = $topdir->listFiles ('', 'file', true, true);
            $dirs = $topdir->listFiles ('', 'dir', true, true);
        }
        else {
            $files = array($path);
            $dirs = array();
        }
        if (isset($filemode)) foreach ($files as $file) $ret = @chmod($file, $filemode) ? $ret : false;
        if (isset($dirmode)) foreach ($dirs as $dir) $ret = @chmod($dir, $dirmode) ? $ret : false;
        return $ret;
    }
    
    public function makeUploadSafe ($name, $useRealName=false) {
    	$tempdir = $this->makeTemp();
    	$files = array();
    	$tempfiles = (array) $_FILES[$name]['tmp_name'];
    	$filenames = (array) $_FILES[$name]['name'];
    	foreach ($tempfiles as $key=>$temp) {
    		if ($useRealName) $filename = $filenames[$key];
    		else $filename = basename($temp);
    		if (move_uploaded_file($temp, $tempdir.$filename)) {
    			$files[$filenames[$key]] = $tempdir.$filename;
    			$this->setPermissions($tempdir.$filename);
    		}
    	}
    	return $files;
    }

	public function extractArchive ($filename, $extractDir='') {
		if (!$extractDir) $extractDir = $this->makeTemp();
		if (preg_match( '/\.zip$/i', $filename )) {
			$zipfile = new PclZip( $filename );
			$ret = $zipfile->extract( PCLZIP_OPT_PATH, $extractDir );
			if($ret == 0) {
				$this->setErrorMessage (sprintf(T_('Installer unrecoverable ZIP error %s in %s'), $zipfile->errorName(true), $filename), _ALIRO_ERROR_FATAL);
				return false;
			}
		} else {
			error_reporting(E_ALL);
			$archive = new Archive_Tar( $filename );
			$archive->setErrorHandling( PEAR_ERROR_PRINT );

			if (!$archive->extractModify( $extractDir, '' )) {
				$this->setErrorMessage (sprintf(T_('Installer unrecoverable TAR error in %s'), $filename), _ALIRO_ERROR_FATAL);
				return false;
			}
			error_reporting(E_ALL|E_STRICT);
		}
		$this->mosChmodRecursive($extractDir);
		return $extractDir;
	}
	
	public function makeTemp () {
		$tempDir = _ALIRO_SITE_BASE.'/tmp/'.uniqid('_aliro_temp_').'/';
		if ($this->createDirectory($tempDir)) {
			self::$tempdirs[] = $tempDir;
			return $tempDir;
		}
		return false;
	}
	
	public function makeVisibleTemp () {
		$relative = '/tmp/'.uniqid('_aliro_temp_').'/';
		$abspath = _ALIRO_ABSOLUTE_PATH.$relative;
		if ($this->createDirectory($abspath)) {
			self::$tempdirs[] = $abspath;
			return $relative;
		}
		return false;
	}
	
}


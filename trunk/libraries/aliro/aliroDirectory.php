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

class aliroDirectory {
    protected $path = '';

    public function __construct ($path) {
        $path = str_replace('\\', '/', $path);
        $this->path = ('/' == substr($path,-1)) ? $path : $path.'/';
	}

	public function getPath () {
		return $this->path;
	}

    public function listAll ($type='file', $recurse=false, $fullpath=false) {
        $results = array();
        clearstatcache();
		$dir = @opendir($this->path);
        if ($dir) {
            while ($file = readdir($dir)) {
                if (empty($file) OR '.' == $file OR '..' == $file) continue;
                if (is_dir($this->path.$file)) {
                    if ($recurse) {
                        $subdir = new self($this->path.$file);
                        $results = array_merge($results, $subdir->listAll($type, $recurse, $fullpath));
                        unset($subdir);
                    }
                    if ($type == 'file') continue;
                }
                elseif ($type == 'dir') continue;
                if ($fullpath) $results[] = $this->path.$file;
                else $results[] = $file;
            }
            closedir($dir);
        }
        return $results;
    }

	public function structure () {
		$result['files'] = $this->listAll();
		$result['directories'] = array();
		$directories = $this->listAll('dir');
		foreach ($directories as $directory) {
			$subdir = new self($this->path.'/'.$directory);
			$result['directories'][$directory] = $subdir->structure();
			unset ($subdir);
		}
		return $result;
	}

    public function soleDir () {
        $found = '';
        clearstatcache();
		$dir = @opendir($this->path);
        if ($dir) {
            while ($file = readdir($dir)) {
                if ($file == '.' OR $file == '..') continue;
                if (is_dir($this->path.$file)) {
                    if ($found) return '';
                    else $found = $file;
                }
                else return '';
            }
            closedir($dir);
        }
        return $found;
    }

    public function deleteAll () {
		$this->deleteContents();
        $this->delete();
    }

	public function delete () {
    	clearstatcache();
        if (file_exists($this->path)) {
            if (is_dir($this->path)) return @rmdir($this->path);
            else return false;
        }
        return true;
    }

	public function deleteContents ($keepstandard=false) {
		clearstatcache();
        if (!file_exists($this->path)) return;
        if (!$keepstandard) {
	        $subdirs = $this->listAll ('dir', false, true);
	        foreach ($subdirs as $subdir) {
	            $subdirectory = new self($subdir);
	            $subdirectory->deleteAll();
	            unset($subdirectory);
	        }
        }
		$this->deleteFiles($keepstandard);
	}

	public function deleteFiles ($keepstandard=false) {
        $files = $this->listAll ('file', false, true);
        foreach ($files as $file) {
			$filename = basename($file);
			if (!$keepstandard OR ('index.html' != $filename AND '.' != $filename[0])) @unlink($file);
		}
	}

	public function create ($dir='', $onlyCheck=false) {
		if (!$dir) $dir = $this->path;
        if (file_exists($dir)) {
            if (is_dir($dir) AND is_writable($dir)) return true;
            else return false;
        }
        list($upDirectory, $count) = $this->containingDirectory($dir);
        if ($count > 1 AND !file_exists($upDirectory) AND !($result = $this->create($upDirectory, $onlyCheck))) return false;
        if ($onlyCheck AND isset($result)) return true;
        if (!is_dir($upDirectory) OR !is_writable($upDirectory)) return false;
        if ($onlyCheck) return true;
		return @mkdir($dir);
	}

    protected function containingDirectory ($dir) {
        $dirs = preg_split('*[/|\\\]*', $dir);
        while (count($dirs)) {
            if (trim(array_pop($dirs))) break;
        }
        $result2 = count($dirs);
        $result1 = implode('/',$dirs).($result2 > 1 ? '' : '/');
        return array($result1, $result2);
	}

    public function createFresh () {
        $this->deleteAll();
        $this->create();
        return true;
    }

    public function createIfNeeded () {
        return dir_exists($this->path) ? true : $this->create();
    }

    public function listFiles ($pattern='', $type='file', $recurse=false, $fullpath=false) {
        $results = array();
        $all = $this->listAll($type, $recurse, $fullpath);
        foreach ($all as $file) {
            $name = basename($file);
            if ($pattern AND !preg_match( "/$pattern/", $name )) continue;
            if (($name != 'index.html') AND ($name[0] != '.')) $results[] = $file;
        }
        return $results;
    }

	// The extension parameter can be a string e.g. 'gif' or an array of strings
	public function selectiveMove ($topath, $extension) {
		if ('/' != substr($topath,-1)) $topath .= '/';
		if (empty($extension)) $pattern = '';
		else {
			$exts = array_map(array($this, 'addPeriod'), (array) $extension);
			$pattern = '.*'.implode('|', $exts).'$';
		}
		$files = $this->listFiles($pattern);
		foreach ($files as $file) if (!rename($this->path.$file, $topath.$file)) return false;
		$dirs = $this->listFiles('', 'dir');
		foreach ($dirs as $dir) {
			$newdir = new self($this->path.$dir);
			$this->create($topath.$dir);
			if (!$newdir->selectiveMove($topath.$dir, $extension)) return false;
		}
		$this->deleteAll();
		return true;
	}

	private function addPeriod ($string) {
		return '.'.$string;
    }

    public function getSize () {
        $totalsize = 0;
        $files = $this->listFiles();
        foreach ($files as $file) $totalsize += filesize($this->path.$file);
        return $totalsize;
    }

	// This uses the PHP ZipArchive class that is only available around version 5.2.5
	public function newzip ($zipname) {
        $zipfile = $this->path.$zipname;
		$files = $this->listAll('file', true, true);
		$zip = new ZipArchive();
		if (!$zip->open($zipfile, ZIPARCHIVE::CREATE)) trigger_error(T_('Unable to open zip file ').$zipfile);
		else foreach ($files as $file) {
			$zip->addFile($file,substr($file,strlen($this->path)));
		}
		$zip->close();
		return $zipfile;
	}

	public function zip ($zipname) {
		$zipper = new zipfile();
		$files = $this->listAll('file', true, true);
		foreach ($files as $file) {
			$zipper->addFile(file_get_contents($file), substr($file,strlen($this->path)));
		}
		$zipper->output($this->path.$zipname);
		return $this->path.$zipname;
	}
}

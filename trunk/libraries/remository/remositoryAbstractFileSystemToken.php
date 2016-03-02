<?php

/**************************************************************
* This file is part of Remository
* Copyright (c) 2006-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Remository started life as the psx-dude script by psx-dude@psx-dude.net
* It was enhanced by Matt Smith up to version 2.10
* Since then development has been primarily by Martin Brampton,
* with contributions from other people gratefully accepted
*/

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

abstract class remositoryAbstractFileSystemToken extends remositoryFileToken {
	protected $driver_type = 'FileSystem';
	protected $driver_name = '';
	protected $overwriteable = true;
	protected $downloadable = true;
	protected $copiable = true;
	protected $ftoken = null;
	protected $fmode = '';

	public function __construct ($id, $identifier, $insertID=false) {
		if (!$this->driver_name) $this->driver_name = $this->T_('Remository File System File Driver');
		parent::__construct($id, $identifier, $insertID);
		$this->diskname = $insertID ? parent::basicNameWithID ($this->id, $this->filename) : $this->filename;
	}

	protected function open ($mode) {
		$this->fmode = 'w' == $mode ? 'wb' : 'rb';
		if ($this->diskname) {
			if ('wb' == $this->fmode) $this->checkDirectory(dirname($this->diskname));
			else $this->exists(true);
			$this->ftoken = fopen($this->diskname, $this->fmode);
			if (!is_resource($this->ftoken)) throw new remositoryFileError(sprintf($this->T_('File System: Open %s failed'), $this->diskname));
		}
		else throw new remositoryFileError ($this->T_('File System: Attempted file open but no file path'));
	}

	protected function setReadOffset ($offset) {
		$this->openForRead();
		if (0 > fseek($this->ftoken, $offset)) throw new remositoryFileError(sprintf($this->T_('File System: Unable to seek to %d in %s'), $offset, $this->diskname));
	}

	protected function openForRead () {
		if (!is_resource($this->ftoken)) $this->open('r');
		if ('rb' != $this->fmode) throw new remositoryFileError($this->T_('File System: Attempt to read file opened for write'));
	}

	protected function read () {
		$this->openForRead();
		if (feof($this->ftoken)) {
			fclose($this->ftoken);
			throw new remositoryFileEnd ();
		}
		$data = fread ($this->ftoken, _REMOSITORY_FILE_BLOCK_SIZE);
		if (false === $data) throw new remositoryFileError(sprintf($this->T_('File System: %s suffered read error'), $this->diskname));
		return $data;
	}

	protected function write ($data) {
		if (!is_resource($this->ftoken)) $this->open('w');
		if ('wb' != $this->fmode) throw new remositoryFileError($this->T_('File System: Attempt to write file opened for read'));
		if (false === fwrite ($this->ftoken, $data)) throw new remositoryFileError(sprintf($this->T_('File System: %s suffered write error'), $this->diskname));
	}

	public function dieIfBlockedExtension () {
		$parts = explode('.', $this->filename);
		if (in_array(end($parts), array('php', 'htm', 'html'))) die($this->T_('Attempt to access insecure extension'));
	}

	public function delete () {
		if ($this->diskname) return @unlink($this->diskname);
		else throw new remositoryFileError($this->T_('File System: Attempt to delete file with no file path'));
	}

	public function copy ($totoken) {
		if ($totoken instanceof self) {
			$this->exists(true);
			return $this->diskname == $totoken->diskname ? true : copy($this->diskname, $totoken->diskname);
		}
		else return $this->copyAcrossType($totoken);
	}

	public function move ($totoken) {
		if ($totoken instanceof self) {
			if ($this->diskname == $totoken->diskname) return true;
			$result = rename($this->diskname, $totoken->diskname);
		}
		if (empty($result)) $result = $this->copy($totoken);
		if ($result) $this->delete();
		return $result;
	}

	public function exists ($errorOnFail=false) {
		clearstatcache();
		$result = ($this->diskname AND file_exists($this->diskname) AND !is_dir($this->diskname)) ? true : false;
		if ($errorOnFail AND !$result) $this->nonExistenceError ();
		else return $result;
	}

	protected function equal ($filetoken) {
		return $this->diskname = $filetoken->diskname;
	}

	public function getDate () {
		$unixtime = @filemtime($this->diskname);
		if (0 == $unixtime) $unixtime = time();
		return date('Y-m-d H:i:s', $unixtime);
	}

	public function getSize () {
		return filesize($this->diskname);
	}

	protected function endOfFile () {
		if (is_resource($this->ftoken)) @fclose($this->ftoken);
	}

	protected function checkDirectory ($path) {
		if (file_exists($path)) {
			if (is_dir($path)) {
				if (is_writeable($path)) {
					return true;
				}
				throw new remositoryFileError (sprintf($this->T_('File System: Directory %s is not writeable'), $path));
			}
			throw new remositoryFileError(sprintf($this->T_('File System: Path %s is not a directory')));
		}
		@mkdir($path, 0777 & ~umask(), true);
		if (file_exists($path)) return $this->checkDirectory($path);
		else throw new remositoryFileError(sprintf($this->T_('File System: Create directory %s failed'), $path));
	}
}
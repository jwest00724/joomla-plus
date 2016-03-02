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

// NOTE: Subclass must give suitable value to $maxsize

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

abstract class remositoryAbstractUploadToken extends remositoryFileToken {
	protected $driver_type = 'Upload';
	protected $driver_name = '';
	protected $overwriteable = false;
	protected $downloadable = false;
	protected $copiable = false;
	protected $ftoken = null;
	protected $fmode = '';
	protected $proper_name = '';
	protected $size = 0;
	protected $maxsize = 0;
	protected $date = '';

	public function __construct ($suffix='') {
		if (!$this->driver_name) $this->driver_name = $this->T_('Remository Uploaded File Driver');
		$key = 'userfile'.$suffix;
		if (empty($_FILES[$key]) OR empty($_FILES[$key]['tmp_name']) OR 'none' == $_FILES[$key]['tmp_name']){
			throw new remositoryFileNoUpload(_ERR1);
		}
		if ($_FILES[$key]['error']) throw new remositoryFileError(_ERR11);
		$this->proper_name = $_FILES[$key]['name'];
		if ($_FILES[$key]['size'] == 0) throw new remositoryFileError(_ERR3);
		$this->size = $_FILES[$key]['size'];
		if($this->size > $this->maxsize*1024) throw new remositoryFileError(_ERR5.$this->maxsize.' KB');
		$this->filename = $_FILES[$key]['tmp_name'];
		$this->exists(true);
		if (ini_get('safe_mode')) $this->date = date('Y-m-d H:i:s');
		else {
			$unixtime = @filemtime($filepath);
			if (0 == $unixtime) $unixtime = time();
			$this->date = date('Y-m-d H:i:s', $unixtime);
		}
	}

	protected function open ($mode) {
		$this->fmode = 'rb';
		if ($this->filename) {
			$this->exists(true);
			$this->ftoken = fopen($this->filename, $this->fmode);
			if (!is_resource($this->ftoken)) throw new remositoryFileError(sprintf($this->T_('File Upload: Open %s failed'), $this->filename));
		}
		else throw new remositoryFileError ($this->T_('File Upload: Attempted file open but no file path'));
	}

	protected function read () {
		if (!is_resource($this->ftoken)) $this->open('r');
		if (feof($this->ftoken)) throw new remositoryFileEnd ();
		$data = fread ($this->ftoken, _REMOSITORY_FILE_BLOCK_SIZE);
		if (false === $data) throw new remositoryFileError(sprintf($this->T_('File Upload: %s suffered read error'), $this->filename));
		return $data;
	}

	public function move ($totoken) {
		$this->exists(true);
		if ($totoken instanceof remositoryAbstractFileSystemToken) {
			return move_uploaded_file($this->filename, $totoken->diskname);
		}
		else return $this->copyAcrossType($totoken);
	}

	public function exists ($errorOnFail=false) {
		$result = ($this->filename AND is_uploaded_file($this->filename)) ? true : false;
		if ($errorOnFail AND !$result) $this->nonExistenceError ();
		else return $result;
	}

	protected function equal ($uploadtoken) {
		return $this->filename = $uploadtoken->filename;
	}

	public function getDate () {
		return $this->date;
	}

	public function getSize () {
		return $this->size;
	}
}
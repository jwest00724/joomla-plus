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

class remositoryAbstractTextToken extends remositoryFileToken {
	protected $driver_type = 'Text';
	protected $driver_type = '';
	protected $overwriteable = false;
	protected $downloadable = true;
	protected $copiable = true;
	protected $tablename = '';
	protected $fmode = '';
	protected $filesize = 0;
	protected $readoffset = 0;
	protected $doneread = false;
	protected $database = null;

	public function __construct ($id, $identifier, $insertID=false) {
		if (!$this->driver_name) $this->driver_name = $this->T_('Remository Database Text File Driver');
		parent::__construct($id, basename($identifier), $insertID);
		$this->database = aliroDatabase::getInstance();
	}

	protected function open ($mode) {
		$this->fmode = 'w' == $mode ? 'wb' : 'rb';
		if ($this->id) {
			if ('rb' == $this->fmode) {
				$this->exists(true);
			}
		}
		else throw new remositoryFileError ($this->T_('Database Text File: Attempted file open but no file ID'));
	}

	protected function setReadOffset ($offset) {
		$this->openForRead();
		$this->readoffset = $offset;
	}

	protected function openForRead () {
		if (!$this->fmode) $this->open('r');
		if ('rb' != $this->fmode) throw new remositoryFileError($this->T_('Database Text File: Attempt to read file opened for write'));
	}

	protected function read () {
		$this->openForRead();
		if ($this->doneread) throw new remositoryFileEnd ();
		$this->database->setQuery("SELECT filetext FROM $this->tablename WHERE fileid=$this->id");
		$data = $this->database->loadResult();
		$this->doneread = true;
		return $this->readoffset ? substr($data, $this->readoffset) : $data;
	}

	public function delete () {
		if (!$this->id) throw new remositoryFileError($this->T_('Database File: Attempt to delete file with no file path'));
		$this->database->doSQL("DELETE FROM $this->tablename WHERE fileid=$this->id");
	}

	public function copy ($totoken) {
		return $this->copyAcrossType($totoken);
	}

	public function move ($totoken) {
		if ($totoken instanceof self) {
			if ($totoken->id != $this->id) {
				$this->database->doSQL("UPDATE $this->tablename SET fileid = $totoken->id WHERE fileid = $this->id");
			}
		}
		else {
			$this->copyAcrossType($totoken);
			$this->delete();
		}
	}

	public function exists ($errorOnFail=false) {
		$this->database->setQuery("SELECT COUNT(*) FROM $this->tablename WHERE fileid=$this->id");
		$result = $this->database->loadResult() ? true : false;
		if ($errorOnFail AND !$result) $this->nonExistenceError ();
		return $result;
	}

	protected function equal ($texttoken) {
		return $this->id == $texttoken->id;
	}

	public function getDate () {
		return date('Y-m-d H:i:s');
	}

	public function getSize () {
		$this->database->setQuery("SELECT LENGTH(filetext) FROM $this->tablename WHERE fileid=$this->id");
		$result = $this->database->loadResult();
		return $result ? $result : 0;
	}

	protected function endOfFile () {
		$this->doneread = false;
	}
}
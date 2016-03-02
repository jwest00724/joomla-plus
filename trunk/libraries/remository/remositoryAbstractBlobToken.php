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

// NOTE: Subclass must give valid value to $tablename

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

abstract class remositoryAbstractBlobToken extends remositoryFileToken {
	protected $driver_type = 'Blob';
	protected $driver_name = '';
	protected $overwriteable = false;
	protected $downloadable = true;
	protected $copiable = true;
	protected $tablename = '';
	protected $chunkid = 0;
	protected $chunks = array();
	protected $bloblengths = array();
	protected $readoffset = 0;
	protected $fmode = '';
	protected $filesize = 0;
	protected $gotblobs = false;
	protected $database = null;

	public function __construct ($id, $identifier, $insertID=false) {
		if (!$this->driver_name) $this->driver_name = $this->T_('Remository Database File Driver');
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
		else throw new remositoryFileError ($this->T_('Database File: Attempted file open but no file ID'));
	}

	protected function setReadOffset ($offset) {
		$this->openForRead();
		while ($offset > $this->bloblengths[$this->chunkid]) {
			$offset -= $this->bloblengths[$this->chunkid++];
		}
		$this->readoffset = $offset;
	}

	protected function openForRead () {
		if (!$this->fmode) $this->open('r');
		if ('rb' != $this->fmode) throw new remositoryFileError($this->T_('Database File: Attempt to read file opened for write'));
	}

	protected function read () {
		$this->openForRead();
		if (!isset($this->chunks[$this->chunkid])) throw new remositoryFileEnd ();
		$sql = "SELECT datachunk FROM $this->tablename WHERE fileid=$this->id AND chunkid={$this->chunks[$this->chunkid++]}";
		$this->database->setQuery($sql);
		$data = $this->database->loadResult();
		$offset = $this->readoffset;
		if ($offset) {
			$this->readoffset = 0;
			return substr($data, $offset);
		}
		return $data;
	}

	protected function write ($data) {
		if (!($this->fmode)) $this->open('w');
		if ('wb' != $this->fmode) throw new remositoryFileError($this->T_('Database File: Attempt to write file opened for read'));
		$data = $this->database->getEscaped($data);
		$this->database->doSQL("INSERT INTO $this->tablename (fileid, chunkid, datachunk, bloblength) VALUES ($this->id, $this->chunkid, '$data', LENGTH(datachunk))");
		$this->chunkid++;
	}

	public function delete () {
		if (!$this->id) throw new remositoryFileError($this->T_('Database File: Attempt to delete file with no file path'));
		$this->database->doSQL("DELETE FROM $this->tablename WHERE fileid=$this->id");
		return true;
	}

	public function copy ($totoken) {
		return $this->copyAcrossType($totoken);
	}

	public function move ($totoken) {
		if ($totoken instanceof self) {
			if ($totoken->id != $this->id) {
				$this->database->doSQL("UPDATE $this->tablename SET fileid = $totoken->id WHERE fileid = $this->id");
				return true;
			}
		}
		else {
			if ($this->copyAcrossType($totoken)) return $this->delete();
			return false;
		}
	}

	public function exists ($errorOnFail=false) {
		$this->getBlobInfo();
		$result = empty($this->chunks) ? false : true;
		if ($errorOnFail AND !$result) $this->nonExistenceError ();
		else return $result;
	}

	protected function equal ($blobtoken) {
		return $this->id == $blobtoken->id;
	}

	public function getDate () {
		return date('Y-m-d H:i:s');
	}

	public function getSize () {
		$this->getBlobInfo();
		return $this->filesize;
	}

	private function getBlobInfo () {
		if (!$this->gotblobs) {
			if (!$this->database->tableExists($this->tablename)) {
				throw new remositoryFileError(sprintf($this->T_('Database File: Table %s does not exist'), $this->tablename));
			}
			$blobs = $this->database->doSQLget("SELECT chunkid, bloblength FROM $this->tablename WHERE fileid=$this->id ORDER BY chunkid");
			foreach ($blobs as $blob) {
				$this->chunks[] = $blob->chunkid;
				$this->bloblengths[] = $blob->bloblength;
			}
			$this->filesize = array_sum($this->bloblengths);
			$this->gotblobs = true;
		}
	}

	protected function endOfFile () {
		$this->chunkid = 0;
	}
}
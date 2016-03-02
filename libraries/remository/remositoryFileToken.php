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
 *
 * The core logic of file handling now included in Aliro.
 */

if (!defined('_REMOSITORY_DOWNLOAD_SLEEP*1000')) define('_REMOSITORY_DOWNLOAD_SLEEP*1000', 250);
if (!defined('_REMOSITORY_FILE_BLOCK_SIZE')) define('_REMOSITORY_FILE_BLOCK_SIZE', 60000);

class remositoryFileError extends Exception {}

class remositoryFileEnd extends Exception {}

class remositoryFileNoUpload extends Exception {}

abstract class remositoryFileToken {
	protected static $valid = array('FileSystem', 'Blob', 'Text');
	protected static $dbtypes = array('Blob', 'Text');

	protected $filename = '';
	protected $diskname = '';
	protected $id = 0;
	protected $realwithid = false;

	public function __construct ($id, $identifier, $insertID=false) {
		$this->filename = $identifier;
		$this->id = $id;
		$this->dieIfBlockedExtension();
	}

	// CMS specific code:
	protected function T_ ($string) {
		return JText::_($string);
	}

	// Getter for the driver type (an identifier string)
	public function getDriverType () {
		return $this->driver_type;
	}

	// Provide a string representation - mainly to aid diagnostics
	public function __toString () {
		return sprintf($this->T_('%s object, name is %s, ID is %d'), $this->getDriverType(), $this->filename, $this->id);
	}

	public function getExtension () {
		return strtolower(end(explode('.', $this->filename)));
	}

	abstract protected function read ();

	// Should be overriden if the write operation is supported
	protected function write ($data) {
		throw new remositoryFileError(sprintf($this->T_('%s: does not permit write'), $this->driver_name));
	}

	// Should be overriden if the delete operation is supported
	// Pass true to cause delete if possible, no error if not possible
	public function delete ($ifPossible=false) {
		if ($ifPossible) return;
		throw new remositoryFileError(sprintf($this->T_('%s: does not permit delete'), $this->driver_name));
	}

	// Should be overriden if the copy operation is supported
	public function copy ($totoken) {
		throw new remositoryFileError(sprintf($this->T_('%s: does not permit copy'), $this->driver_name));
	}

	abstract public function move ($totoken);

	abstract public function exists ($errorOnFail=false);

	// Each subclass must implement the equal method to test for equality between two objects of the same subclass
	public function testEqual ($token) {
		return ($this->driver_type == $token->driver_type) ? $this->equal($token) : false;
	}

	// Getter for the file proper name, what it should be called in a file system
	public function getProperName () {
		return empty($this->proper_name) ? basename($this->filename) : $this->proper_name;
	}

	abstract public function getDate ();

	abstract public function getSize ();

	// Getter for the overwriteable boolean
	public function isOverWriteable () {
		return $this->overwriteable;
	}

	// Getter for copiable boolean
	public function isCopiable () {
		return $this->copiable;
	}

	public function randomizeName () {
		$extension = $this->getExtension();
		$front = substr($this->filename, 0, -strlen($extension));
		$this->filename = $front.uniqid('', true).'.'.$extension;
	}

	// Should not need to be overriden
	public function download ($offset=0) {
		@set_time_limit(0);
		if (!$this->downloadable) throw new remositoryFileError(sprintf($this->T_('%s: does not permit download'), $this->driver_name));
		try {
			$sent = 0;
			$this->setReadOffset($offset);
			$mqr = ini_get('magic_quotes_runtime');
			ini_set('magic_quotes_runtime', 0);
			while (true) {
				$data = $this->read();
				echo $data;
				$sent += strlen($data);
				flush();
				usleep(_REMOSITORY_DOWNLOAD_SLEEP*1000);
			}
		}
		catch (remositoryFileEnd $e) {
			$this->endOfFile();
			ini_set('magic_quotes_runtime', $mqr);
			return $sent;
		}
	}

	// Drivers that want to refuse download of specific extensions should check
	// and if appropriate die.
	// For example, the File System driver will not handle a .php file
	public function dieIfBlockedExtension () {}

	// Must be implemented for every driver that can provide downloads
	protected function setReadOffset ($offset) {}

	// Drivers that use the ID to make a file name unique should implement this
	protected function insertID () {}

	// Should not need to be overriden
	protected function copyAcrossType ($totoken) {
		try {
			while (true) $totoken->write($this->read());
		}
		catch (remositoryFileEnd $exception) {
			$this->endOfFile();
			$totoken->endOfFile();
			return true;
		}
	}

	// Implement this in drivers that wish to take action on end of file
	protected function endOfFile () {}

	protected function nonExistenceError () {
		throw new remositoryFileError(sprintf($this->T_('%s: ID: %d, name: %s does not exist'), $this->driver_name, $this->id, $this->filename));
	}

	// Takes a typical file name and inserts ID number before extension
	// unless none is found, in which case ID goes last
	public static function basicNameWithID ($id, $name) {
		$elements = explode ('.', $name);
		if (1 < count($elements)) $extension = array_pop($elements);
		else $extension = '';
		array_push ($elements, (string) $id);
		if ($extension) array_push ($elements, $extension);
		return implode('.', $elements);
	}

	// Get a file token object by specifying driver type, file ID, file identifier (e.g. path to file)
	// and a boolean indicating whether the ID should be inserted into the name for uniqueness
	// if the file driver is vulnerable to clashes on file identifer.
	public static function getFileToken ($driver_type, $id=0, $identifier='', $insertID=false) {
		$class = 'remository'.$driver_type.'Token';
		$object = class_exists($class) ? new $class($id, $identifier, $insertID) : false;
		if (is_object($object)) return $object;
		// NOTE: CMS specific code below
		else throw new remositoryFileError (sprintf(JText::_('File Token: Invalid file token request, %s class derived from "%s"'), $class, $driver_type));
	}

	public static function validTypes () {
		return self::$valid;
	}

	public static function validDBTypes () {
		return self::$dbtypes;
	}
}
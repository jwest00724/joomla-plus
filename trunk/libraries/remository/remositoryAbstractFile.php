<?php

/**************************************************************
* This file is part of Remository
* Copyright (c) 2006 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Remository started life as the psx-dude script by psx-dude@psx-dude.net
* It was enhanced by Matt Smith up to version 2.10
* Since then development has been primarily by Martin Brampton,
* with contributions from other people gratefully accepted
*/

// Subclasses must set a valid value for $tableName
// Subclasses may want to vary $realwithidDefault - if true it forces names
// in a file system to have the unique ID number added
// A subclass implementing temporary files should set $temporary to true
// and may also want to set a preferred driver, normally Blob

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

abstract class remositoryAbstractFile extends aliroDatabaseRow {
	public static $flist = '';

	protected $DBclass = 'aliroDatabase';
	protected $tableName = '';
	protected $rowKey = 'id';
	protected $temporary = false;
	protected $realwithidDefault = true;

	public function  __construct ($id=0, $temporary=false) {
		$this->id = $id;
		$this->temporary = $temporary;
		$this->realwithid = $this->realwithidDefault;
		if ($this->temporary) $this->setSpecial();
	}

	// Joomla CMS Specific
	protected function T_ ($string) {
		return JTEXT::_($string);
	}

	// Subclasses may want to override this to do more
	public function setSpecial () {
		$this->driver_type = $this->temporaryDriver;
	}

	public function isTemporary () {
		return $this->temporary;
	}

	protected function getValues ($user=null, $onlypublished=true) {
		$this->load($this->id);
		$this->temporary = 0 < $this->containerid;
		$this->containerid = abs($this->containerid);
	}

	public function store ($updateNulls=false) {
		if (!$this->containerid) {
			echo jaliroDebug::trace();
			die ('Attempt to save file with no container ID');
		}
		$is_insert = $this->id ? false : true;
		parent::store($updateNulls);
		if ($is_insert) $this->incrementCounts('+1');
	}

	// Subclasses may wish to implement this
	protected function incrementCounts ($by) {}

	public function  realName () {
		return $this->islocal ? $this->realname : '';
	}

	public function  url () {
		return $this->islocal ? '' : $this->url;
	}

	// Subclasses may wish to implement a more extensive version of this
	public function  addPostData ($adminside=false, $ignore='') {
		// Clear all tick boxes - will be sent by POST data if and only if tick is present
		$this->bind($_POST, $ignore);
		// Carry out any special processing after acquiring input data
	}

	// Subclasses may wish to implement more extensive functionality
	public function  saveFile () {
		$this->filetype = $this->getExtension();
		if ($this->temporary) {
			$this->containerid = -abs($this->containerid);
			$this->published = 0;
		}
		else {
			$this->oldid = 0;
		}
		$this->store();
	}

	protected function getExtension () {
		$filename = $this->islocal ? $this->realname : $this->url;
		$parts = explode($filename);
		return (1 < count($parts)) ? end($parts) : '';
	}

	public function obtainPhysical () {
		return remositoryFileToken::getFileToken ($this->driver_type, $this->id, $this->filepath.$this->realname, $this->realwithid);
	}

	public function storePhysicalFile ($physicalFile, $extensiontitle=true, $checkExt=true) {
		$this->filetype = $this->getExtension();
		$this->getPhysicalData($physicalFile, $extensiontitle);
		$file_path = $this->filepath.$this->realname;
		if ($checkExt AND !$this->isExtensionOK($this->filetype)) {
			throw new remositoryFileError ($this->T_('You attempted to upload a file with a disallowed extension!'));
		}
		$this->realwithid = $this->realwithidDefault;
		$this->saveFile();
		$newphysical = $this->obtainPhysical();
		return $physicalFile->move($newphysical);
	}

	// Subclasses may want to implement more restrictive version of this
	protected function isExtensionOK ($extension) {
		return true;
	}

	public function  getPhysicalData ($physicalFile, $extensiontitle=true) {
		$this->realname = $physicalFile->getProperName();
		$this->filedate = $physicalFile->getDate();
		$this->filesize = (int) ceil($physicalFile->getSize()/1024);
		$this->filetype = strpos($this->realname, '.') ? end(explode('.', $this->realname)) : '';
		if (empty($this->filetitle)) {
			$nicetitle = str_replace('_', ' ', $this->realname);
			$this->filetitle =  $extensiontitle ? $nicetitle : basename($nicetitle, '.'.$this->filetype);
		}
		$this->islocal = 1;
		$this->url = '';
	}

	// Subclasses may want additional functionality in this method
	public function deleteFile () {
		$physical = $this->obtainPhysical();
		$physical->delete();
		$this->delete();
		if ($this->published) $this->incrementCounts('-1');
	}

	protected function commonApprove ($container, $oldfile) {
		if (!$this->temporary) throw new remositoryFileError($this->T_('Attempting to approve a non-temporary file'));
		$temphysical = $this->obtainPhysical();
		if (!$temphysical->exists()) throw new remositoryFileError($this->T_('File for approval - physical does not exist'));
		if (empty($oldfile->id)) {
			$file = $this;
		}
		else {
			$file = $oldfile;
			$oldphysical = $oldfile->obtainPhysical();
			$file->bind($this, 'published,featured');
			$file->id = $oldfile->oldid;
		}
		$file->temporary = false;
		$file->referred = false;
		$file->memoContainer($container);
		$file->realwithid = $file->realwithidDefault;
		$file->publish_from = date('Y-m-d H:i:s');
		if (!$file->id) throw new remositoryError('Destination file for approval has no ID');
		if (isset($oldphysical)) $oldphysical->delete();
		if ($temphysical->move($file->obtainPhysical())) {
			$file->saveFile();
			if (isset($oldphysical)) $this->delete();
			// Now it is approved, log the upload
			$file->newPublication($file->submittedby);
			return $file;
		}
		else throw new remositoryError('Unable to relocate file correctly on approval');
	}

	public function approve ($container, $oldfile) {
		$appfile = empty($oldfile->id) ? $this : $oldfile;
		$appfile->published = true;
		$appfile->featured = false;
		return $this->commonApprove($container, $oldfile);
	}

	public function approveFeatured ($container, $oldfile) {
		$appfile = empty($oldfile->id) ? $this : $oldfile;
		$appfile->published = true;
		$appfile->featured = true;
		return $this->commonApprove($container, $oldfile);
	}

	public function approveUnpublished ($container, $oldfile) {
		$appfile = empty($oldfile->id) ? $this : $oldfile;
		$appfile->published = false;
		$appfile->featured = false;
		return $this->commonApprove($container, $oldfile);
	}

	// Subclasses may wish to implement this - called when file is published
	protected function newPublication () {}

	// Subclasses may wish to extend the actions taken here
	public function memoContainer ($container) {
		$this->containerid = $container->id;
		$this->driver_type = $container->driver_type;
		$this->filepath = $container->filepath;
	}

	public static function setCtype ($file_extension) {
		//This will set the Content-Type to the appropriate setting for the file
		switch( $file_extension ) {
			 case "pdf": $ctype="application/pdf"; break;
		     case "exe": $ctype="application/octet-stream"; break;
		     case "zip": $ctype="application/zip"; break;
		     case "doc": $ctype="application/msword"; break;
		     case "xls": $ctype="application/vnd.ms-excel"; break;
		     case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
		     case "gif": $ctype="image/gif"; break;
		     case "png": $ctype="image/png"; break;
		     case "jpeg":
		     case "jpg": $ctype="image/jpg"; break;
		     case "mp3": $ctype="audio/mpeg"; break;
		     case "m4a": $ctype="audio/aacp"; break;
		     case "wav": $ctype="audio/x-wav"; break;
		     case "mpeg":
		     case "mpg":
		     case "mpe": $ctype="video/mpeg"; break;
		     case "mov": $ctype="video/quicktime"; break;
		     case "avi": $ctype="video/x-msvideo"; break;

		     default: $ctype="application/force-download";
		}
		return $ctype;
	}

	public static function writeHeaders ($ctype, $displayname, $fileinfo) {
		// Do IE specific things
		if (isset($_SERVER['HTTP_USER_AGENT']) AND
	    (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
	    	$displayname = urlencode($displayname);
	    }
	    else $displayname = str_replace(' ', '+', $displayname);

	    // Suppress output compression, can be harmful
		if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');

		//Use the switch-generated Content-Type
		header("Content-Type: $ctype; charset=utf-8");

		//Begin writing other headers
        // header("Pragma: public");
		// header("Cache-Control: no-cache");
		header("Expires: -1");

		//Force the download
		header("Content-Disposition: attachment; filename=$displayname");
		header("Content-Transfer-Encoding: binary");
		// Length header is now sent by the rangeHandler method
		// if ($len) header("Content-Length: ".$len);
	}

	public static function rangeHandler ($size) {
		if (!empty($_SERVER['HTTP_RANGE'])) {
			$regex = '/^bytes=([0-9]*)\-([0-9]*)/';
			preg_match($regex, $_SERVER['HTTP_RANGE'], $matches);
		}
		$seek_end = (empty($matches[2])) ? ($size - 1) : min((integer) $matches[2] ,($size - 1));
		$seek_start = (empty($matches[1]) OR $seek_end < (integer) $matches[1]) ? 0 : max((integer) $matches[1],0);
		$partial = ($seek_start > 0 OR $seek_end < ($size - 1));
		if ($partial) header($_SERVER['SERVER_PROTOCOL'].' 206 Partial Content');
		header('Accept-Ranges: bytes');
		if ($partial) header("Content-Range: bytes $seek_start-$seek_end/$size");
		header('Content-Length: '.($seek_end - $seek_start + 1));
		return $seek_start;
	}
}
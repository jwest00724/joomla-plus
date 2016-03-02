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

abstract class remositoryAbstractImageToken extends remositoryAbstractFileSystemToken {
	protected $driver_type = 'Image';
	protected $driver_name = '';

	public function __construct ($id, $identifier, $insertID=false) {
		if (!$this->driver_name) $this->driver_name = $this->T_('Remository Image File Driver');
		if (extension_loaded('gd') AND function_exists('gd_info')) {
			parent::__construct($id, $identifier, $insertID);
			if (!in_array($this->getExtension(), array('gif', 'png', 'jpg', 'jpeg'))) {
				throw new remositoryError($this->T_('Image file has invalid extension'.' '.$this->getExtension()));
			}
		}
		else throw new remositoryError($this->T_('GD library is not available - cannot process image files'));
	}

	//This function will resize any PNG or JPG image to whatever size you specify
	//It will keep aspect ratios.
	//usage resize(toImageToken,150,150);
	// Optionally ask for high quality, where possible
	// Optionally add a watermark by providing a watermark image token
	public function resize ($newimagetoken, $new_w, $new_h, $highQuality=false, $watermarkimage=null) {
		//determine starting type and create blank
		//you could also add gif and bmp in here
		$type = $this->getExtension();
		switch ($type) {
			case 'png':
				$src_img = imagecreatefrompng($this->diskname);
				break;
			case 'gif':
				$src_img = imagecreatefromgif($this->diskname);
				break;
			default:
				$src_img = imagecreatefromjpeg($this->diskname);
		}
		//grab original sizes
		$old_x = imagesx($src_img);
		$old_y = imagesy($src_img);

		// math to figure aspect ratio
		$ratio = min($new_w/$old_x, $new_h/$old_y);
		$thumb_h = $old_y * $ratio;
		$thumb_w = $old_x * $ratio;

		//generate a blank final image
		$dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
		//this resamples the original image and I think uses bicub to create a new image
		imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);

		$this->watermark($watermarkimage, $thumb_w, $thumb_h, $dst_img);

		// Delete new image if it already exists in destination
		$newimagetoken->delete();

		//create the final file
		switch ($type) {
			case 'png':
				if ($highQuality) imagepng ($dst_img, $newimagetoken->diskname, 0);
				else imagepng($dst_img, $newimagetoken->diskname);
				break;
			case 'gif':
				imagegif ($dst_img, $newimagetoken->diskname);
				break;
			default:
				if ($highQuality) imagejpeg ($dst_img, $newimagetoken->diskname, 100);
				else imagejpeg ($dst_img, $newimagetoken->diskname);
		}
		//free up memory
		imagedestroy($dst_img);
		imagedestroy($src_img);
	}

	public function watermark ($watermarkimage, $thumb_w, $thumb_h, $dst_img) {
		if ($watermarkimage instanceof remositoryAbstractImageToken) {
			$watermark = imagecreatefrompng($watermarkimage->diskname);
			$sized_water = ImageCreateTrueColor($thumb_w, $thumb_h);
			// Match desired image size
			imagecopyresampled($sized_water, $watermark, 0, 0, 0, 0, $thumb_w, $thumb_h, imagesx($watermark), imagesy($watermark));
			imagedestroy($watermark);
			// Make the background transparent
			imagecolortransparent($sized_water, imagecolorallocate($sized_water, 0, 0, 0));
			imagecopymerge($dst_img, $sized_water, 0, 0, 0, 0, $thumb_w, $thumb_h, 50);
			imagedestroy($sized_water);
		}
	}
}
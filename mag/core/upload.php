<?php
/*
<NPT, a web development framework.>
Copyright (C) <2009>  <NPT>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('XFS')) exit;

// Borrowed from http://us.php.net/manual/en/function.imagerotate.php#93151
if (!function_exists('imagerotate'))
{
	function rotate_x($x, $y, $theta)
	{
		return $x * cos($theta) - $y * sin($theta);
	}
	function rotate_y($x, $y, $theta)
	{
		return $x * sin($theta) + $y * cos($theta);
	}

	function imagerotate_eq($source, $angle, $ignore_transparent = 0, $bgcolor = 0)
	{
		$srcw = imagesx($source);
		$srch = imagesy($source);
		
		//Normalize angle
		$angle %= 360;
		
		//Set rotate to clockwise
		//$angle = -$angle;
		
		if (!$angle)
		{
			if ($ignore_transparent == 0)
			{
				imagesavealpha($source, true);
			}
			return $source;
		}
		
		// Convert the angle to radians
		$theta = deg2rad($angle);
		
		//Standart case of rotate
		if ((abs($angle) == 90) || (abs($angle) == 270))
		{
			$width = $srch;
			$height = $srcw;
			
			if (($angle == 90) || ($angle == -270))
			{
				$minx = 0;
				$maxx = $width;
				$miny = -$height + 1;
				$maxy = 1;
			}
			elseif (($angle == -90) || ($angle == 270))
			{
				$minx = -$width + 1;
				$maxx = 1;
				$miny = 0;
				$maxy = $height;
			}
		}
		elseif (abs($angle) === 180)
		{
			$width = $srcw;
			$height = $srch;
			$minx = -$width + 1;
			$maxx = 1;
			$miny = -$height + 1;
			$maxy = 1;
		}
		else
		{
			// Calculate the width of the destination image.
			$temp = array(rotate_x(0, 0, 0 - $theta), rotate_x($srcw, 0, 0 - $theta), rotate_x(0, $srch, 0 - $theta), rotate_x($srcw, $srch, 0 - $theta));
			$minx = floor(min($temp));
			$maxx = ceil(max($temp));
			$width = $maxx - $minx;
			
			// Calculate the height of the destination image.
			$temp = array(rotate_y(0, 0, 0 - $theta), rotate_y($srcw, 0, 0 - $theta), rotate_y(0, $srch, 0 - $theta), rotate_y($srcw, $srch, 0 - $theta));
			$miny = floor(min($temp));
			$maxy = ceil(max($temp));
			$height = $maxy - $miny;
		}
		
		$dest = imagecreatetruecolor($width, $height);
		if ($ignore_transparent === 0)
		{
			imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 255,255, 255, 127));
			imagesavealpha($dest, true);
		}
		
		// sets all pixels in the new image
		for ($x = $minx; $x < $maxx; $x++)
		{
			for ($y = $miny; $y < $maxy; $y++)
			{
				// fetch corresponding pixel from the source image
				$srcx = round(rotate_x($x, $y, $theta));
				$srcy = round(rotate_y($x, $y, $theta));
				
				if ($srcx >= 0 && $srcx < $srcw && $srcy >= 0 && $srcy < $srch)
				{
					$color = imagecolorat($source, $srcx, $srcy);
				}
				else
				{
					$color = $bgcolor;
				}
				
				imagesetpixel($dest, $x - $minx, $y - $miny, $color);
			}
		}
		
		return $dest;
	}

	function imagerotate($source, $angle, $ignore_transparent = 0, $bgcolor = 0)
	{
		return imagerotate_eq($source, $angle, $ignore_transparent, $bgcolor);
	}
}

class upload
{
	private $errorlist = array();
	private $_default = array();
	private $remove_queue = array();
	private $ext_blacklist;
	private $watermark_path;
	
	// You can get your own API key at http://www.picnik.com/info/api
	private $picnik_api = '';
	private $picnik_url = 'http://www.picnik.com/service';
	private $picnik_arg = array();
	
	//'../home/style/images/w.png'
	
	public function __construct()
	{
		$this->_default = array(
			'name' => '',
			'name_1' => 'none',
			'size' => 0,
			'error' => 4
		);
		$this->ext_blacklist = implode('|', w('cgi pl js asp php html htm jsp jar exe dll bat'));
		
		return;
	}
	
	public function error($e, $remove = false)
	{
		$this->errorlist[] = $e;
		
		if ($remove !== false)
		{
			$this->remove_queue[] = $remove;
		}
		
		return false;
	}
	
	public function errors()
	{
		return count($this->errorlist) ? true : false;
	}
	
	public function get_errors()
	{
		return $this->errorlist;
	}
	
	public function set_watermark($f)
	{
		$this->watermark_path = $f;
	}
	
	private function _merge($a)
	{
		$a_file = w();
		if (!is_array($a))
		{
			return $a_file;
		}
		
		$a_keys = array_keys($a);
		$s_files = count($a);
		
		for ($i = 0, $end = count($a['name']); $i < $end; $i++)
		{
			foreach ($a_keys as $k)
			{
				$a_file[$i][$k] = ($s_files) ? $a[$k] : $a[$k][$i];
			}
		}
		
		foreach ($a_file as $i => $row)
		{
			foreach ($this->_default as $k => $v)
			{
				if ($row[$k] === $v) unset($a_file[$i]);
			}
		}
		
		return array_values($a_file);
	}
	
	public function chmod($filepath, $mode = 0755)
	{
		return @chmod($filepath, $mode);
	}
	
	public function rename($a, $b)
	{
		$filename = str_replace($a['random_name'], $b, $a['filepath']);
		@rename($a['filepath'], $filename);
		$this->chmod($filename);
		
		return $filename;
	}
	
	public function remove($f = false)
	{
		if ($f !== false)
		{
			$this->remove_queue[] = $f;
		}
		
		foreach ($this->remove_queue as $row)
		{
			if (@file_exists($row)) @unlink($row);
		}
		
		$this->remove_queue = w();
		return;
	}
	
	public function _row($filepath, $filename)
	{
		$row = array(
			'extension' => _extension($filename),
			'_name' => $filename,
			'name' => strtolower($filename),
			'random_name' => _filename(time(), substr(md5(unique_id()), 6), '_')
		);
		
		//$row['filename'] = _filename($row['random_name'], $row['extension']);
		$row['filename'] = $filename;
		$row['filepath'] = $filepath . $row['filename'];
		
		return $row;
	}
	
	public function process($filepath, $files, $extension, $filesize, $safe = true)
	{
		$umask = umask(0);
		
		if (!$files = $this->_merge($files))
		{
			return $this->error('UPLOAD_NO_FILES');
		}
		
		foreach ($files as $i => $row)
		{
			$row['extension'] = _extension($row['name']);
			$row['name'] = strtolower($row['name']);
			
			if (!in_array($row['extension'], $extension))
			{
				return $this->error(sprintf(_lang('UPLOAD_INVALID_EXT'), $row['name']), $row['filepath']);
			}
			elseif ($safe && preg_match('/\.(' . $this->ext_blacklist . ')$/', $row['name']))
			{
				$row['extension'] = 'txt';
			}
			elseif ($row['size'] > $filesize)
			{
				return $this->error(sprintf(_lang('UPLOAD_TOO_BIG'), $row['name'], ($filesize / 1048576)), $row['filepath']);
			}
			
			$row['random_name'] = time() . '_' . substr(md5(unique_id()), 6);
			$row['filename'] = _filename($row['random_name'], $row['extension']);
			$row['filepath'] = $filepath . $row['filename'];
			
			if (!@move_uploaded_file($row['tmp_name'], $row['filepath']))
			{
				return $this->error(sprintf(_lang('UPLOAD_FAILED'), $row['name']), $row['filepath']);
			}
			
			$this->chmod($row['filepath']);
			
			if (@filesize($row['filepath']) > $filesize)
			{
				return $this->error(sprintf(_lang('UPLOAD_TOO_BIG'), $row['name'], ($filesize / 1048576)), $row['filepath']);
			}
			
			$files[$i] = $row;
		}
		
		@umask($umask);
		return (count($files)) ? $files : false;
	}
	
	public function resize(&$row, $folder_a, $folder_b, $filename, $measure, $mscale = true, $watermark = true, $remove = false, $exif_source = false)
	{
		$a_filename = _filename($filename, $row['extension']);
		$source = $folder_a . $row['filename'];
		$destination = $folder_b . $a_filename;
		
		// Get image data from source
		list($width, $height, $type, $void) = @getimagesize($source);
		if ($width < 1 && $height < 1)
		{
			return false;
		}
		
		if ($width < $measure[0] && $height < $measure[1])
		{
			$measure[0] = $width;
			$measure[1] = $height;
		}
		
		$scale_mode = ($mscale === true) ? 'c' : 'v';
		$row = array_merge($row, array('width' => $width, 'height' => $height, 'mwidth' => $measure[0], 'mheight' => $measure[1]));
		$row = array_merge($row, $this->scale($scale_mode, $row));
		
		switch ($type)
		{
			case IMG_JPG:
				$image_f = 'imagecreatefromjpeg';
				$image_g = 'imagejpeg';
				$image_t = 'jpg';
				break;
			case IMG_GIF:
				$image_f = 'imagecreatefromgif';
				$image_g = 'imagegif';
				$image_t = 'gif';
				break;
			case IMG_PNG:
				$image_f = 'imagecreatefrompng';
				$image_g = 'imagepng';
				$image_t = 'png';
				break;
		}
		
		if (!$image = @$image_f($source))
		{
			return false;
		}
		
		@imagealphablending($image, true);
		$thumb = @imagecreatetruecolor($row['width'], $row['height']);
		@imagecopyresampled($thumb, $image, 0, 0, 0, 0, $row['width'], $row['height'], $width, $height);
		
		// Watermark
		if ($watermark)
		{
			$wm = imagecreatefrompng($this->watermark_path);
			$wm_w = imagesx($wm);
			$wm_h = imagesy($wm);
			
			// Bottom right
			$dest_x = $row['width'] - $wm_w - 5;
			$dest_y = $row['height'] - $wm_h - 5;
			
			// imagecopymerge($thumb, $wm, $dest_x, $dest_y, 0, 0, $wm_w, $wm_h, 100);
			// imagedestroy($wm);
			
			// Centered
			// $dest_x = round(($row['width'] / 2) - ($wm_w / 2));
			// $dest_y = round(($row['height'] / 2) - ($wm_h / 2));
			
			$thumb = $this->alpha_overlay($thumb, $wm, $wm_w, $wm_h, $dest_x, $dest_y, 100);
		}
		
		$hook_arr = array($thumb, $destination);
		if ($type == IMG_JPG)
		{
			$hook_arr[] = 85;
		}
		
		if (!hook($image_g, $hook_arr) || !@file_exists($destination))
		{
			return false;
		}
		
		if ($exif_source !== false && @file_exists($exif_source) && $d_exif = @exif_read_data($exif_source))
		{
			$this->_rotate($d_exif, $destination, $destination);
		}
		
		$this->chmod($destination);
		
		@imagedestroy($thumb);
		@imagedestroy($image);
		
		if ($remove && @file_exists($source))
		{
			$this->remove($source);
		}
		
		$row['filename'] = $a_filename;
		return $row;
	}
	
	private function scale($mode, $a)
	{
		switch ($mode)
		{
			case 'c':
				$width = $a['mwidth'];
				$height = round(($a['height'] * $a['mwidth']) / $a['width']);
				break;
			case 'v':
				if ($a['width'] > $a['height'])
				{
					$width = round($a['width'] * ($a['mwidth'] / $a['width']));
					$height = round($a['height'] * ($a['mwidth'] / $a['width']));
				}
				else
				{
					$width = round($a['width'] * ($a['mwidth'] / $a['height']));
					$height = round($a['height'] * ($a['mwidth'] / $a['height']));
				}
				break;
		}
		return array('width' => $width, 'height' => $height);
	}
	
	public function _rotate($a_exif, $source, $destination)
	{
		// Use if needed > ['IFD0']
		if (!isset($a_exif['Orientation']))
		{
			return false;
		}
		
		switch ($a_exif['Orientation'])
		{
			case 1: // nothing
				break;
			case 2: // horizontal flip
				$this->flip($source, $destination, 1);
				break;
			case 3: // 180 rotate left
				$this->rotate($source, $destination, 180);
				break;
			case 4: // vertical flip
				$this->flip($source, $destination, 2);
				break;
			case 5: // vertical flip + 90 rotate right
				$this->flip($source, $destination,  2);
				$this->rotate($source, $destination, -90);
				break;
			case 6: // 90 rotate right
				$this->rotate($source, $destination, -90);
				break;
			case 7: // horizontal flip + 90 rotate right
				$this->flip($source, $destination, 1);
				$this->rotate($source, $destination, -90);
				break;
			case 8: // 90 rotate left
				$this->rotate($source, $destination, 90);
				break;
		}
		
		return true;
	}
	
	private function rotate($source, $destination, $degrees)
	{
		$image = imagecreatefromjpeg($source);
		$rotate = imagerotate($image, $degrees);
		
		imagejpeg($rotate, $destination);
		imagedestroy($image);
		imagedestroy($rotate);
		
		return;
	}
	
	private function flip($src, $dest, $type)
	{
		$imgsrc = imagecreatefromjpeg($src);
		$width = imagesx($imgsrc);
		$height = imagesy($imgsrc);
		$imgdest = imagecreatetruecolor($width, $height);
		
		for ($x = 0; $x < $width; $x++)
		{
			for ($y = 0; $y < $height; $y++)
			{
				$x_width = $width - $x - 1;
				$y_height = $height - $y - 1;
				
				if ($type == 1) imagecopy($imgdest, $imgsrc, $x_width, $y, $x, $y, 1, 1);
				if ($type == 2) imagecopy($imgdest, $imgsrc, $x, $y_height, $x, $y, 1, 1);
				if ($type == 3) imagecopy($imgdest, $imgsrc, $x_width, $y_height, $x, $y, 1, 1);
			}
		}
		
		imagejpeg($imgdest, $dest);
		imagedestroy($imgsrc);
		imagedestroy($imgdest);
		
		return;
	}
	
	function alpha_overlay($destImg, $overlayImg, $imgW, $imgH, $onx, $ony, $alpha = 0)
	{
		for ($y = 0; $y < $imgH; $y++)
		{
			for ($x = 0; $x < $imgW; $x++)
			{
				$ovrARGB = imagecolorat($overlayImg, $x, $y);
				$ovrA = ($ovrARGB >> 24) << 1;
				$ovrR = $ovrARGB >> 16 & 0xFF;
				$ovrG = $ovrARGB >> 8 & 0xFF;
				$ovrB = $ovrARGB & 0xFF;
				
				$change = false;
				if ($ovrA == 0)
				{
					$dstR = $ovrR;
					$dstG = $ovrG;
					$dstB = $ovrB;
					$change = true;
				}
				elseif ($ovrA < 254)
				{
					$dstARGB = imagecolorat($destImg, $x, $y);
					$dstR = $dstARGB >> 16 & 0xFF;
					$dstG = $dstARGB >> 8 & 0xFF;
					$dstB = $dstARGB & 0xFF;
					$dstR = (($ovrR * (0xFF-$ovrA)) >> 8) + (($dstR * $ovrA) >> 8);
					$dstG = (($ovrG * (0xFF-$ovrA)) >> 8) + (($dstG * $ovrA) >> 8);
					$dstB = (($ovrB * (0xFF-$ovrA)) >> 8) + (($dstB * $ovrA) >> 8);
					$change = true;
				}
				
				if ($change)
				{
					$dstRGB = imagecolorallocatealpha($destImg, $dstR, $dstG, $dstB, $alpha);
					imagesetpixel($destImg, ($onx + $x), ($ony + $y), $dstRGB);
				}
			}
		}
		
		return $destImg;
	}
	
	//
	// PICNIK
	//
	// 'picnik_' methods based on the source code of the KingOfTheHill application.
	// This code can be freely copied, modified, and distributed.
	//
	
	public function picnik_set_api($k)
	{
		$this->picnik_api = $k;
	}
	
	public function picnik_get_api()
	{
		return (f($this->picnik_api)) ? $this->picnik_api : false;
	}
	
	public function picnik_set_arg($k, $v)
	{
		$this->picnik_arg[$k] = $v;
	}
	
	public function picnik_get_arg($k)
	{
		return (isset($this->picnik_arg[$k])) ? $this->picnik_arg[$k] : false;
	}
	
	private function picnik_args()
	{
		return $this->picnik_arg;
	}
	
	//
	// $arg expects this keys:
	//
	// _EXPORT: Tell Picnik where to send the exported image
	// _EXPORT_TITLE: Give the export button a title
	// _CLOSE_TARGET: Turn on the close button, and tell it to come back here
	// _IMPORT: Filename, Send in the previous "king" image in case the user feels like decorating it
	// _REDIRECT: Tell Picnik to redirect the user to the following URL after the HTTP POST instead of just redirecting to _export
	// _HOST_NAME: Tell Picnik our name.  It'll use it in a few places as appropriate
	//
	// Anything that doesn't start with an underscore will be sent back to us.
	//
	public function picnik_export($arg)
	{
		if (!$this->picnik_get_api())
		{
			return false;
		}
		
		// Set Picnik API key
		$this->picnik_set_arg('_apikey', $this->picnik_get_api());
		
		// Turn off the "Save & Share" tab so users don't get confused
		$this->picnik_set_arg('_exclude', '&_exclude=out');
		
		foreach ($arg as $k => $v)
		{
			$this->picnik_set_arg($k, $v);
		}
		
		return $this->picnik_args();
	}
	
	//
	// See if we've been given a new picture to use.
	// Note that the when Picnik is exporting from its servers, this page will be hit TWICE.
	// Once will be the POST with the image data contained in $_FILES.  The second will be
	// a GET of the _redirect URL we passed in above.
	//
	public function picnik_import($field, $filename)
	{
		global $core;
		
		// Retrieve the image's attributes from the $_FILES array
		$image_tmp_filename = $_FILES[$field]['tmp_name'];
		$image_data = file_get_contents($image_tmp_filename);
		
		file_put_contents($filename, $image_data);
		
		return true;
	}
	
	// When you're debugging this kind of application, keep in mind that Picnik will
	// invoke your script twice: once with the POST'd data, and then again with
	// a GET to the value of the _redirect parameter.  To see what happens
	// on the first (POST) call, you can use something like the below to dump 
	// variables to a debug file.
	public function picnik_debug($f)
	{
		$debug = '';
		$debug .= "\n\nFILES: " . print_r($_FILES, true);
		$debug .= "\n\nPOST: " . print_r($_POST, true);
		$debug .= "\n\nGET: " . print_r($_GET, true);
		
		return @file_put_contents($f, $debug);
	}
	
	public function picnik_tmp()
	{
		echo '<form method="POST" action="">';
		
		// put all the API parameters into the form as hidden inputs
		foreach ($aPicnikParams as $key => $value)
		{
			// '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}
		
		return;
	}
}

?>
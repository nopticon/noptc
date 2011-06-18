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

define('PCK', './pack/');

interface i_cvi
{
	public function home();
}

class __cvi extends xmd implements i_cvi
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function home()
	{
		$dim = array(900, 600);
		$dim = array(800, 533);
		$dim = array(600, 400);
		
		if (_button())
		{
			$v = $this->__(array('start' => 0, 'folder' => '', 'width' => 0, 'height' => 0));
			
			$images = $types = w();
			$dim = array($v['width'], $v['height']);
			
			@set_time_limit(0);
			
			$original = PCK . $v['folder'] . '/';
			$gallery = $original . 'gallery/';
			
			if (!@file_exists($original))
			{
				exit;
			}
			
			if (!@file_exists($gallery))
			{
				@mkdir($gallery, 0777);
				@chmod($gallery, 0777);
			}
			
			require_once(XFS . 'core/upload.php');
			require_once(XFS . 'core/zip.php');
			
			if (!is_writable($original) || !is_writable($gallery))
			{
				exit;
			}
			
			$upload = new upload();
			$zip = new createZip();
			
			$fp = @opendir(PCK . $v['folder']);
			while ($row = @readdir($fp))
			{
				if (preg_match('#^(.*?)\.(jpg|JPG)$#is', $row, $s) && @is_readable($original . $row))
				{
					$images[] = $row;
					
					$type = (preg_match('#^(\d+)$#is', $s[1])) ? 'numeric' : 'string';
					$types[$type] = true;
				}
			}
			@closedir($fp);
			
			if (!count($images))
			{
				exit('No hay archivos para convertir.');
			}
			
			$multisort = array(&$images, SORT_ASC);
			if (!isset($types['string']))
			{
				$multisort[] = SORT_NUMERIC;
			}
			hook('array_multisort', $multisort);
			
			foreach ($images as $image)
			{
				$row = $upload->_row($gallery, $image);
				$xa = $upload->resize($row, $original, $gallery, $start, $dim, false, false, false, $original . $image);
				$start++;
				
				$zip->addFile(file_get_contents($gallery . $xa['filename']), $xa['filename']);
			}
			
			$zipfile = PCK . $folder . '.zip';
			$fd = @fopen($zipfile, 'wb');
			$out = @fwrite($fd, $zip->getZippedfile());
			@fclose($fd);
			
			$zip->forceDownload($zipfile);
			@unlink($zipfile);
			exit;
		}
		
		$options = w();
		$fp = @opendir(PCK);
		while ($file = @readdir($fp))
		{
			if (substr($file, 0, 1) != '.' && is_dir(PCK . $file))
			{
				$options[] = $file;
			}
		}
		@closedir($fp);
		
		foreach ($options as $row)
		{
			echo '<option value="' . $row . '">' . $row . '</option>';
		}
		
		return;
	}
}

?>
<?php
/*
$Id: v 1.5 2009/11/24 15:15:00 $

<NPT, a web development framework.>
Copyright (C) <2009>  <Guillermo Azurdia, http://www.nopticon.com/>

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
define('XFS', './');
define('PCK', './pack/');

error_reporting(E_ALL);

foreach (array('core', 'upload', 'zip') as $row) {
	require_once(XFS . $row . '.php');
}

//$dim = array(900, 600);
//$dim = array(800, 533);
$dim = array(600, 400);

if (_button())
{
	$start = request_var('start', 0);
	$folder = request_var('folder', '');
	$width = request_var('width', $dim[0]);
	$height = request_var('height', $dim[1]);
	
	$images = $types = w();
	$dim = array($width, $height);
	
	@set_time_limit(0);
	
	$original = PCK . $folder . '/';
	$gallery = PCK . $folder . '/gallery/';
	
	if (!@file_exists($original))
	{
		exit('No se encuentra > ' . $folder);
	}
	
	if (!@file_exists($gallery))
	{
		@mkdir($gallery, 0777);
		@chmod($gallery, 0777);
	}
	
	if (!is_writable($original) || !is_writable($gallery))
	{
		die('Error en permisos.');
	}
	
	$upload = new upload();
	$zip = new createZip();
	
	//
	$fp = @opendir(PCK . $folder);
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
	
	if ($fd = @fopen($zipfile, 'wb'))
	{
		$out = @fwrite($fd, $zip->getZippedfile());
		@fclose($fd);
	
		$zip->forceDownload($zipfile);
		@unlink($zipfile);
	}
	
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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>NIC</title>

<?php

if (count($options))
{
?>
<form action="./" method="post" name="post">
<fieldset>
	<legend>Convertir im&aacute;genes</legend>
	
	<dl>
		<dt>Carpeta</dt>
		<dd>
		<select name="folder">
		<?php
		foreach ($options as $row)
		{
			echo '<option value="' . $row . '">' . $row . '</option>';
		}
		?>
		</select>
		</dd>
	</dl>
	<dl>
		<dt>Inicio</dt>
		<dd><input name="start" type="text" size="5" value="1" /></dd>
	</dl>
	<dl>
		<dt>Ancho</dt>
		<dd><input name="width" type="text" size="5" value="<?php echo $dim[0]; ?>" /></dd>
	</dl>
	<dl>
		<dt>Alto</dt>
		<dd><input name="height" type="text" size="5" value="<?php echo $dim[1]; ?>" /></dd>
	</dl>
	
	<input type="submit" class="submit" name="submit" value="Continuar" />
</fieldset>
</form>

<?php
}
else
{
	echo('No hay carpetas para convertir im&aacute;genes.');
}

?>

</body>
</html>

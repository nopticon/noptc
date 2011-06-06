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
define('XFS', './npt/');
require_once(XFS . 'core/core.php');

$key = '';
if (isset($_POST['submit']))
{
	$v = w('server user passwd name');
	$arg = w();
	
	foreach ($v as $k)
	{
		$arg[] = encode(request_var('v_' . $k, ''));
	}
	
	$key = encode(_implode(',', $arg, true));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>g.passwd</title>
<style type="text/css">
body {
	background: #EEE;
}
body, div, span, input, textarea, a, dt {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #444;
	cursor: default;
}
</style>
</head>

<body>

<?php

if (f($key))
{
?>
El c&oacute;digo HTDA del proyecto es:

<blockquote>
<?php echo $key; ?>
</blockquote>
<br />
<?php
}

$field = array(
	'server' => 'Servidor',
	'user' => 'Usuario',
	'passwd' => 'Contrase&ntilde;a',
	'name' => 'Base de datos',
);

?>
<form action="<?php echo basename(__FILE__); ?>" method="post">
<?php

foreach ($field as $row => $z)
{
	$value = ($row == 'server') ? 'localhost' : '';
	echo "\n" . $z . '<br /><input type="text" name="v_' . $row . '" value="' . $value . '" size="50" /><br />';
}

?>
<br />
<input type="submit" name="submit" value="Crear"  /><br />

</form>

</body>
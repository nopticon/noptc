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

$dat = w();
if (isset($_POST['submit']))
{
	$arg = decode(request_var('v_code', ''));
	$dat = explode(',', $arg);
	
	foreach ($dat as $i => $r)
	{
		$dat[$i] = decode($r);
	}
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

if (count($dat))
{
?>
Los datos son:

<blockquote>
<?php

echo '<pre>';
print_r($dat);
echo '</pre>';

?>
</blockquote>
<br />
<?php
}

?>
<form action="<?php echo basename(__FILE__); ?>" method="post">
C&oacute;digo<br />
<textarea name="v_code" cols="150" rows="2"></textarea><br />
<br />
<input type="submit" name="submit" value="Decifrar"  /><br />
</form>

</body>
<?php
/*
$Id: v 2.3 2009/11/13 09:23:00 $

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
if (version_compare(PHP_VERSION, '5.0.0', '<')) exit('Sorry, this application runs on PHP 5 or greater!');

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

error_reporting(E_ALL);

if (@ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $void)
	{
		unset(${$var_name});
	}
}

if (!defined('XFS')) define('XFS', './');
if (!defined('DD')) define('DD', 'mysql');
if (!defined('CA')) define('CA', 'sha1');
if (!defined('REQC')) define('REQC', (strtolower(ini_get('request_order')) == 'gp'));

foreach (array('core', 'dd/' . DD, 'styles', 'session') as $w)
{
	$f_core = XFS . 'core/' . $w . '.php';
	if (!@file_exists($f_core))
	{
		exit;
	}
	@require_once($f_core);
}

foreach (w((!defined('NDB') ? 'db ' : '') . 'style user core') as $w) $$w = new $w();

if (!defined('XCORE')) _xfs();

?>
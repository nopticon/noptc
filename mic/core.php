<?php
/*
$Id: v 3.2 2009/11/12 10:43:00 $

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
if (!defined('XFS')) exit;

function htmlencode($str, $multibyte = false)
{
	$result = trim(htmlentities(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $str)));
	$result = (get_magic_quotes_gpc()) ? stripslashes($result) : $result;
	if ($multibyte)
	{
		$result = preg_replace('#&amp;(\#\d+;)#', '&\1', $result);
	}
	$result = preg_replace('#&amp;((.*?);)#', '&\1', $result);
	
	return $result;
}

function set_var(&$result, $var, $type, $multibyte = false, $regex = '')
{
	settype($var, $type);
	$result = $var;
	
	if ($type == 'string')
	{
		$result = htmlencode($result, $multibyte);
	}
}

//
// Get value of request var
//
function request_var($var_name, $default = '', $multibyte = false, $regex = '')
{
	if (!isset($_REQUEST[$var_name]) || (is_array($_REQUEST[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($_REQUEST[$var_name])))
	{
		return (is_array($default)) ? w() : $default;
	}
	
	$var = $_REQUEST[$var_name];
	if (!is_array($default))
	{
		$type = gettype($default);
		$var = ($var);
	}
	else
	{
		list($key_type, $type) = each($default);
		$type = gettype($type);
		$key_type = gettype($key_type);
	}
	
	if (is_array($var))
	{
		$_var = $var;
		$var = w();

		foreach ($_var as $k => $v)
		{
			if (is_array($v))
			{
				foreach ($v as $_k => $_v)
				{
					set_var($k, $k, $key_type);
					set_var($_k, $_k, $key_type);
					set_var($var[$k][$_k], $_v, $type, $multibyte);
				}
			}
			else
			{
				set_var($k, $k, $key_type);
				set_var($var[$k], $v, $type, $multibyte);
			}
		}
	}
	else
	{
		set_var($var, $var, $type, $multibyte);
	}
	
	return $var;
}

function _utf8($a, $e = false)
{
	if (is_array($a))
	{
		foreach ($a as $k => $v)
		{
			$a[$k] = _utf8($v, $e);
		}
	}
	else
	{
		if ($e !== false)
		{
			$a = utf8_encode($a);
		}
		else
		{
			$a = utf8_decode($a);
		}
	}
	
	return $a;
}

function hook($name, $args = array(), $arr = false)
{
	switch ($name)
	{
		case 'isset':
			eval('$a = ' . $name . '($args' . ((is_array($args)) ? '[0]' . $args[1] : '') . ');');
			return $a;
			break;
		case 'in_array':
			if (is_array($args[1]))
			{
				if (hook('isset', array($args[1][0], $args[1][1])))
				{
					eval('$a = ' . $name . '($args[0], $args[1][0]' . $args[1][1] . ');');
				}
			} else {
				eval('$a = ' . $name . '($args[0], $args[1]);');
			}
			
			return (isset($a)) ? $a : false;
			break;
	}
	
	$f = 'call_user_func' . ((!$arr) ? '_array' : '');
	return $f($name, $args);
}

function f($s)
{
	return !empty($s);
}

function entity_decode($s, $compat = true)
{
	if ($compat)
	{
		return html_entity_decode($s, ENT_COMPAT, 'UTF-8');
	}
	return html_entity_decode($s);
}

function w($a = '', $d = false)
{
	if (!f($a) || !is_string($a)) return array();
	
	$e = explode(' ', $a);
	if ($d !== false)
	{
		foreach ($e as $i => $v)
		{
			$e[$v] = $d;
			unset($e[$i]);
		}
	}
	
	return $e;
}

function is_numb($v)
{
	return @preg_match('/^\d+$/', $v);
}

function _button($name = 'submit')
{
	return (isset($_POST[$name])) ? true : false;
}

function max_upload_size()
{
	return intval(ini_get('upload_max_filesize')) * 1048576;
}

function _extension($file)
{
	return strtolower(str_replace('.', '', substr($file, strrpos($file, '.'))));
}

function _filename($a, $b, $m = '.')
{
	return $a . $m . $b;
}

function unique_id()
{
	list($sec, $usec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	return uniqid(mt_rand(), true);
}

?>

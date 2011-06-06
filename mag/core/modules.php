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

if (@file_exists('./base/project.php'))
{
	require_once('./base/project.php');
}

if (!class_exists('project'))
{
	class project { }
}

abstract class xmd extends project
{
	public $arg = array();
	public $error = array();
	public $exclude = array();
	public $level = array();
	public $level_2 = array();
	private $methods = array();
	public $nav = array();
	public $page_title_v = array();
	public $je = array(
		'OK' => '~[200]',
		'CN' => '~[201]'
	);
	
	private $_auth;
	private $module;
	private $template;
	
	public function __construct()
	{
		$this->auth(true);
		
		if (@method_exists($this, 'imodule'))
		{
			$this->methods['imodule'] = w();
		}
		return;
	}
	
	final public function auth($v = -1)
	{
		return $this->m($v, '_auth');
	}
	
	final protected function _m($v = -1)
	{
		return $this->m($v, 'methods');
	}
	
	final public function m($v = -1, $w = 'module')
	{
		if ($v !== -1)
		{
			$this->{$w} = $v;
		}
		
		return $this->{$w};
	}
	
	final public function v($var_name, $default, $multibyte = false)
	{
		if (!isset($this->arg[$var_name]) || (is_array($this->arg[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($this->arg[$var_name])))
		{
			return (is_array($default)) ? w() : $default;
		}
	
		$var = $this->arg[$var_name];
		if (!is_array($default))
		{
			$type = gettype($default);
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
	
	final public function xlevel($arg = false)
	{
		if ($arg !== false)
		{
			$this->arg = $arg;
		}
		
		foreach ($this->arg as $k => $v)
		{
			if (preg_match('/^x(\d+)$/s', $k)) $this->level[$k] = $v;
		}
		
		foreach (w('x1 x2') as $k)
		{
			if (!isset($this->level[$k])) $this->level[$k] = '.';
		}
		ksort($this->level);
		
		$keys = w();
		foreach ($this->x() as $k => $v)
		{
			$keys[] = "['" . $v . "']";
			
			$exists = hook('isset', array($this->methods, implode('', $keys)));
			if (!$exists)
			{
				$key = count($keys) - 1;
				$keys[$key] = (isset($keys[$key])) ? $keys[$key] : '';
				$value = str_replace(array('[', ']', "'"), '', $keys[$key]);
				unset($keys[$key]);
				
				$i_keys = implode('', $keys);
				$exists = (hook('in_array', array($value, array($this->methods, $i_keys))) && hook('isset', array($this->methods, $i_keys)));
			}
			
			if (!$exists) $this->level[$k] = 'home';
		}
		
		return;
	}
	
	final public function rvar($k, $v)
	{
		$this->arg[$k] = $v;
		return $v;
	}
	
	final public function x($a = false, $v = false)
	{
		if ($a === false)
		{
			return $this->level;
		}
		
		$a = 'x' . $a;
		if ($v !== false)
		{
			$this->level[$a] = $v;
		}
		
		return (isset($this->level[$a]) && f($this->level[$a])) ? $this->level[$a] : false;
	}
	
	final public function __($v, $p = '', $m = 'v')
	{
		$v = _array_keys($v);
		
		foreach ($v as $varname => $options)
		{
			if (strpos($varname, '*') !== false)
			{
				$subvar = str_replace('*', '', $varname);
				$varpos = $varname;
				
				foreach ($this->arg as $j => $k)
				{
					if (preg_match('#' . preg_quote($subvar, '#') . '#', $j))
					{
						$varpos = array_push_after($v, array($j => $options), $varpos);
					}
				}
				unset($v[$varname]);
			}
			
			if (strpos($varname, ':') !== false)
			{
				$_v = explode(':', $varname);
				
				array_push_after($v, array($_v[0] => $_v[1]), $varname);
				unset($v[$varname]);
			}
		}
		
		$a = w();
		foreach ($v as $varname => $options)
		{
			if (f($p))
			{
				$varname = $p . '_' . $varname;
			}
			if (!is_array($options) || !isset($options['default']))
			{
				if (is_array($options) && !count($options)) $options = '';
				
				$options = array('default' => $options);
			}
			if (!isset($options['type']))
			{
				$options['type'] = 'text';
			}
			
			switch ($options['type'])
			{
				case 'checkbox':
					$a[$varname] = (isset($_POST[$varname])) ? true : false;
					break;
				default:
					$hook_a = ($m == 'v') ? array($this, 'v') : $m;
					$a[$varname] = hook($hook_a, array($varname, $options['default']));
					break;
			}
		}
		
		return $a;
	}
	
	// Throw an error excluding $arr input array
	final public function _vr($v, $arr, $px)
	{
		if (!is_array($arr)) $arr = w($arr);
		
		foreach ($v as $k => $kv)
		{
			if (!in_array($k, $arr) && $kv === '')
			{
				$this->_error('E_' . $px . '_' . $k, false);
			}
		}
		
		return;
	}
	
	final public function auth_access($uid = false)
	{
		global $bio;
		
		if ($uid === false)
		{
			$uid = $bio->v('user_id');
		}
		
		if ($bio->auth_founder($uid))
		{
			return true;
		}
		return _auth_get($this->m());
	}
	
	final public function _install()
	{
		global $core;
		
		// TODO: Improve module installation!
		
		// Pre run check
		if (!$modules = $core->cache_load('modules'))
		{
			$sql = 'SELECT *
				FROM _modules
				ORDER BY module_name';
			$modules = $core->cache_store(_rowset($sql));
		}
		
		$run_install = true;
		foreach ($modules as $row)
		{
			if ($row['module_alias'] === $this->m())
			{
				$run_install = false;
			}
		}
		
		// Run module install
		if ($run_install)
		{
			$proc = $this->install();
			
			// Post install
			$sql_insert = array(
				'alias' => $this->m(),
				'name' => $proc['NAME'],
				'author' => $proc['AUTHOR'],
				'link' => $proc['LINK']
			);
			$sql = 'INSERT INTO _modules' . _build_array('INSERT', prefix('module', $sql_insert));
			_sql($sql);
		}
		
		return;
	}
	
	final public function method()
	{
		$f = '';
		foreach ($this->x() as $k => $v)
		{
			$f .= '_' . $v;
		}
		
		if (!method_exists($this, $f))
		{
			_fatal();
		}
		return $this->{$f}();
	}
	
	final public function internal($address, $arg = false)
	{
		global $core;
		
		$arg_str = '';
		if ($arg !== false)
		{
			foreach ($arg as $i => $row)
			{
				$arg_str .= ((f($arg_str)) ? '&' : '') . urlencode($i) . '=' . urlencode($row);
			}
		}
		
		return netsock($core->v('address') . $a, $arg_str, 80, false, $core->v('internal_useragent'));
	}
	
	final public function error($str, $prefix = true)
	{
		$str = ($prefix) ? $this->m() . '_' . $str : $str;
		$this->error[] = strtoupper($str);
	}
	
	final public function get_errors($glue = '$')
	{
		global $bio;
	
		return implode($glue, preg_replace('#^([A-Z_]+)$#e', "(isset(\$bio->lang['\\1'])) ? \$bio->lang['\\1'] : '\\1'", $this->error));
	}
	
	final public function errors()
	{
		return count($this->error) ? true : false;
	}
	
	final public function _error($str, $prefix = true)
	{
		if (substr($str, 0, 1) == '#')
		{
			$prefix = false;
			$str = substr($str, 1);
		}
		$this->error($str, $prefix);
		
		// TODO: Ghost or normal request message
		
		return $this->e('!');
	}
	
	final public function _template($f = false)
	{
		if ($f !== false)
		{
			$this->template = $f;
		}
		
		return (f($this->template)) ? $this->template : false;
	}
	
	final public function imodule()
	{
		$v = $this->__(w('name'));
		
		if (!f($v['name']))
		{
			_fatal();
		}
		
		$template = XFS . 'base/__imodule.php';
		if (!@file_exists($template))
		{
			_fatal();
		}
		
		$base = './base/';
		if (@file_exists($base . '_' . $v['name'] . '.php'))
		{
			$this->e('Module already exists: ' . $v['name']);
		}
		
		if (@is_writable($base) && $fp = @fopen($base . '_' . $v['name'] . '.php', 'w'))
		{
			$replace_v = array(
				w('{V_NAME} {V_DATE}'),
				array($v['name'], date('Y/m/d H:i:s'))
			);
			
			@fwrite($fp, implode('', str_replace($replace_v[0], $replace_v[1], @file($template))));
			@chmod($base . '_' . $v['name'] . '.php', 0755);
			@fclose($fp);
			
			$this->e('New module in project: ' . $v['name']);
		}
		return $this->e('Error creating module: ' . $v['name']);
	}
	
	final public function e($m = '')
	{
		if ($m == '!' && !$this->errors())
		{
			return false;
		}
		
		// GZip
		if (_browser('gecko'))
		{
			ob_start('ob_gzhandler');
		}
		
		// Headers
		if (!headers_sent())
		{
			header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
			header('Expires: 0');
			header('Pragma: no-cache');
		}
		
		sql_close();
		
		if (is_object($m) || is_array($m))
		{
			_pre($m);
		}
		else
		{
			if (is_lang($m))
			{
				$m = _lang($m);
			}
			elseif ($m == '!')
			{
				$m = '#' . $this->get_errors();
			}
			elseif (($s = substr($m, 0, 1)) == '~')
			{
				$m = str_replace($s, '', $m);
				$m = isset($this->je[$m]) ? $this->je[$m] : $this->je['OK'];
			}
			
			echo($m);
		}
		exit();
	}
	
	final public function nav()
	{
		$nav = array('a' => w(), 'b' => '');
		foreach ($this->x() as $k => $v)
		{
			if ($v == 'home') continue;
			
			$nav['a'][$k] = $v;
			$nav['b'] .= '_' . $v;
			
			hook(array($this, 'navigation'), array($nav['b'], $nav['a']));
		}
		return true;
	}
	
	final public function navigation($k, $v = false, $m = false)
	{
		if (!f($k)) return;
		
		$m = ($m !== false || $k == 'home') ? $m : $this->m();
		$this->nav[$k] = ($v !== false) ? _link($m, $v) : '';
		return;
	}
	
	final public function get_navigation()
	{
		$format = '<a href="%s">%s</a>';
		$a = w();
		
		foreach ($this->nav as $k => $v)
		{
			$a[] = (f($v)) ? sprintf($format, $v, _lang($k)) : _lang($k);
		}
		
		return _implode(' &rsaquo; ', $a);
	}
	
	final public function page_title($k = false)
	{
		if ($k !== false)
		{
			$this->page_title_v[] = strtoupper($k);
		}
		
		return $this->page_title_v;
	}
	
	final public function xml($xml)
	{
		$response = '<?xml version="1.0"?><tree>' . "\n" . $xml . "\n" . '</tree>';
		
		// XML headers
		header("Expires: 0");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-Type: text/xml; charset=utf-8");
		
		return $this->e($response);
	}
}

?>
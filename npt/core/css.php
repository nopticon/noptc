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
//if (!defined('XFS')) exit;

if (!function_exists('_pre'))
{
	function _pre($a, $d = false)
	{
		echo '<pre>';
		print_r($a);
		echo '</pre>';
		
		if ($d === true)
		{
			exit;
		}
	}
}

if (!function_exists('f'))
{
	function f($s)
	{
		return !empty($s);
	}
}

class phpcss
{
	private $css = array();
	
	//
	// NOTE: Can not parse correctly CSS expressions, give it a try, maybe works in some of them...
	//
	public function parse($filename)
	{
		if (!$css_content = @file($filename))
		{
			return false;
		}
		
		$css_content = str_replace(array("\n", "\r", "\t"), '', implode('', $css_content));
		$css_content = preg_replace(array('/\s+/', '#\/\*(.*?)\*\/#', '#\<\!\-\- IF (.*?) ENDIF \-\-\>#'), array(' ', '', ''), $css_content);
		preg_match_all('#((\#|\.)?[\w \>\.\:\-\,\[\]\*]+)\s+?\{(.*?)\}#is', $css_content, $matches);
		
		// TODO: Process multiple selectors in one line separated by commas.
		
		foreach ($matches[3] as $i => $row)
		{
			$prop = array_filter(array_map('trim', explode(';', $row)), 'f');
			
			$props = array();
			foreach ($prop as $line)
			{
				list($k, $v) = array_map('trim', explode(':', $line));
				$props[$k] = $v;
			}
			
			$this->css[$matches[1][$i]] = $props;
		}
		
		return;
	}
	
	public function build()
	{
		return;
	}
	
	public function display()
	{
		_pre($this->css);
	}
	
	public function property_get($selector, $property)
	{
		if (!isset($this->css[$selector]))
		{
			return false;
		}
		
		if (!is_array($property))
		{
			$property = array($property);
		}
		
		$response = array();
		foreach ($property as $row)
		{
			if (isset($this->css[$selector][$row]))
			{
				$response[$row] = $this->css[$selector][$row];
			}
		}
		
		$count_r = count($response);
		return ($count_r) ? (($count_r == 1) ? $response[$property[0]] : $response) : false;
	}
	
	public function property_add($selector, $property, $value = false, $w = true)
	{
		$this->selector_add($selector);
		
		if ($value !== false && !is_array($property))
		{
			$property = array($property => $value);
		}
		
		foreach ($property as $k => $v)
		{
			if (!isset($this->css[$selector][$k]) || $w === true)
			{
				$this->css[$selector][$k] = $v;
			}
		}
		
		return true;
	}
	
	public function property_modify($selector, $property, $value = false, $w = true)
	{
		$this->property_add($selector, $property, $value, $w);
	}
	
	public function property_remove($selector, $property)
	{
		if (!isset($this->css[$selector]))
		{
			return false;
		}
		
		if (!is_array($property))
		{
			$property = array($property);
		}
		
		foreach ($property as $row)
		{
			if (isset($this->css[$selector][$row]))
			{
				unset($this->css[$selector][$row]);
			}
		}
		
		if (!count($this->css[$selector]))
		{
			unset($this->css[$selector]);
		}
		
		return true;
	}
	
	public function selector_get($selector)
	{
		if (!isset($this->css[$selector]))
		{
			return false;
		}
		
		if (!count($this->css[$selector]))
		{
			return false;
		}
		
		return $this->css[$selector];
	}
	
	public function selector_add($selector)
	{
		if (isset($this->css[$selector]))
		{
			return false;
		}
		
		$this->css[$selector] = array();
		return true;
	}
	
	public function selector_modify($selector, $new_selector, $similar = false, $w = true)
	{
		if (!isset($this->css[$selector]))
		{
			return false;
		}
		
		if (!$w && isset($this->css[$new_selector]))
		{
			return false;
		}
		
		if (!count($this->css[$selector]))
		{
			return false;
		}
		
		// TODO: Comma separated selectors
		
		if ($similar !== false)
		{
			foreach ($this->css as $k => $v)
			{
				if (preg_match('#^(' . $selector . ')[ ]?(.*?)$#is', $k, $k2))
				{
					$kselector = $new_selector . ((f($k2[2])) ? ' ' . $k2[2] : '');
					
					$this->css[$kselector] = $this->css[$k];
					unset($this->css[$k]);
				}
			}
		}
		else
		{
			$this->css[$new_selector] = $this->css[$selector];
			unset($this->css[$selector]);
		}
		
		return true;
	}
	
	public function selector_remove($selector, $similar = false)
	{
		if (!is_array($selector))
		{
			$selector = array($selector);
		}
		
		if ($similar === false)
		{
			foreach ($selector as $row)
			{
				if (isset($this->css[$row]))
				{
					unset($this->css[$row]);
				}
			}
		}
		else
		{
			foreach ($this->css as $k => $v)
			{
				foreach ($selector as $row)
				{
					if (preg_match('#^(' . preg_quote($row, '#') . ')[ ]?(.*?)$#is', $k, $k2))
					{
						$kselector = $row . ((f($k2[2])) ? ' ' . $k2[2] : '');
						unset($this->css[$kselector]);
					}
				}
			}
		}
		
		return true;
	}
}

?>
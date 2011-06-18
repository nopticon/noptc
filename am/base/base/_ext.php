<?php
/*
$Id: _ext.php,v 2.0 2009/01/12 08:27:00 Psychopsia Exp $

<Nopticon, a web development framework.>
Copyright (C) <2009>  <Nopticon>

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

class __ext extends xmd
{
	var $methods = array();
	
	function home()
	{
		global $user, $style;
		
		$v = $this->__(array('f', 'e'));
		if (empty($v['f']) || empty($v['e']))
		{
			_fatal();
		}
		
		$filepath = './style/' . $v['e'] . '/';
		$filename = _filename($v['f'], $v['e']);
		$browser = array('firefox' => 'Gecko', 'ie' => 'IE', 'comp' => 'compatible');
		$sv = w();
		
		switch ($v['e'])
		{
			case 'css':
				if ($v['f'] != 'default')
				{
					$v['field'] = 'alias';
					if (preg_match('#^\d+$#is', $v['f']))
					{
						$v['field'] = 'id';
					}
					
					$sql = 'SELECT *
						FROM _tree
						WHERE tree_' . $this->_escape($v['field']) . " = '" . $this->_escape($v['f']) . "'
						LIMIT 1";
					if (!$tree = $this->_fieldrow($sql))
					{
						_fatal();
					}
					
					$v['f'] = '_tree_' . $this->alias_id($tree);
					if (!@file_exists($filepath . _filename($v['f'], $v['e'])))
					{
						_fatal();
					}
					$browser[$this->alias_id($tree)] = true;
					$filename = _filename($v['f'], $v['e']);
				}
				
				$sv['CSSPATH'] = LIBD . 'style';
				
				foreach ($browser as $css_k => $css_v)
				{
					$css_match = (strstr($user->browser, $css_v) || $css_v === true) ? true : false;
					if ($css_match && @file_exists($filepath . '_tree_' . $css_k . '.css'))
					{
						$style->set_filenames(array('css' => 'css/_tree_' . $css_k . '.css'));
						$style->assign_var_from_handle('S_CSS', 'css');
						$style->assign_block_vars('includes', array('CSS' => $style->vars['S_CSS']));
					}
					
					$sv[strtoupper($css_k)] = $css_match;
				}
				$this->as_vars($sv);
				break;
			case 'js':
				if (!@file_exists($filepath . $filename))
				{
					_fatal();
				}
				
				require_once(XFS . 'core/jsmin.php');
				
				foreach ($browser as $css_k => $css_v)
				{
					$css_match = (strstr($user->browser, $css_v) || $css_v === true) ? true : false;
					$sv[strtoupper($css_k)] = $css_match;
				}
				break;
		}
		
		if ($sv['COMP'] || $sv['FIREFOX'])
		{
			ob_start('ob_gzhandler');
		}
		
		// Headers
		header('Content-type: text/css; charset=utf-8');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 30)) . ' GMT');
		
		// TODO: 304 Not modified response header
		
		/*$lastmodified = filemtime($filename);
		
		if ($lastmodified)
		{
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastmodified)
			{
				header("HTTP/1.0 304 Not Modified");
			}
			//exit;
		}
		else
		{
			header('Last-Modified: ' . gmdate('D, d, M Y H:i:s', $lastmodified) . ' GMT');
		}*/
		
		if ($v['e'] == 'js')
		{
			$style->replace_vars = false;
		}
		
		$this->sql_close();
		$style->set_filenames(array('body' => $v['e'] . '/' . $filename));
		$style->assign_var_from_handle('EXT', 'body');
		
		switch ($v['e'])
		{
			case 'css':
				$code = str_replace(array("\r\n", "\n", "\t"), '', $style->vars['EXT']);
				break;
			case 'js':
				$code = JSMin::minify($style->vars['EXT']);
				break;
		}
		
		echo $code;
		exit();
	}
}

?>
<?php
/*
$Id: _get.php,v 1.1 2009/01/28 10:11:00 Psychopsia Exp $

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

class __get extends xmd
{
	var $methods = array();
	
	function home()
	{
		global $user, $style;
		
		// /get/$1/$2.$3
		
		$v = $this->__(array('alias', 'filename', 'ext'));
		if (empty($v['alias']) || empty($v['filename']))
		{
			_fatal();
		}
		
		$sql = "SELECT tree_id
			FROM _tree
			WHERE tree_alias = '" . $this->_escape($v['alias']) . "'";
		if (!$tree = $this->_fieldrow($sql))
		{
			_fatal();
		}
		
		$sql = "SELECT *
			FROM _downloads
			WHERE download_alias = '" . $this->_escape($v['filename']) . "'
				AND download_tree = " . (int) $tree['tree_id'];
		if (!$download = $this->_fieldrow($sql))
		{
			_fatal();
		}
		
		if ($download['download_login'])
		{
			_login();
		}
		
		$filepath = LIB . 'get/' . _filename($download['download_id'], $download['download_extension']);
		
		return;
	}
}

?>
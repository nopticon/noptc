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

// /get/$1/$2.$3

class __fetch extends xmd
{
	public function __construct()
	{
		parent::__construct();
		$this->auth(false);
	}
	
	public function home()
	{
		global $user;
		
		$v = $this->__(w('alias filename ext'));
		if (!f($v['alias']) || !f($v['filename']))
		{
			_fatal();
		}
		
		$sql = 'SELECT tree_id
			FROM _tree
			WHERE tree_alias = ?';
		if (!$tree = _fieldrow(sql_filter($sql, $v['alias'])))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _downloads
			WHERE download_alias = ?
				AND download_tree = ?';
		if (!$download = _fieldrow(sql_filter($sql, $v['filename'], $tree['tree_id'])))
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
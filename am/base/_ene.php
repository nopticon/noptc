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

class __ene extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	public function home()
	{
		/*
		$sql = 'SELECT *
			FROM _store
			WHERE store_field = 4
			ORDER BY store_value';
		$countries = _rowset($sql);
		
		foreach ($countries as $row)
		{
			$sv = ucwords(_rm_acute($row['store_value']));
			
			$sql = 'UPDATE _store SET store_value = ?
				WHERE store_id = ?';
			_sql(sql_filter($sql, $sv, $row['store_id']));
		}
		*/
		
		$sql = 'SELECT DISTINCT store_value
			FROM _store
			WHERE store_field = 4
			ORDER BY store_value';
		$countries = _rowset($sql, 'store_value');
		
		_pre($countries, true);
		
		$this->e('');
		
		return;
	}
}

?>
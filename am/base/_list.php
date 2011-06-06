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

class __list extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	public function home()
	{
		$cols = w('Email Nickname Cumplea&ntilde;os Pa&iacute;s');
		
		$sql = 'SELECT user_email, user_username, user_birthday, country_name
			FROM _members m, _countries c
			WHERE m.user_type = ?
				AND m.user_country = c.country_id
			ORDER BY m.user_username';
		$members = _rowset(sql_filter($sql, 1));
		
		foreach ($members as $i => $rowm)
		{
			if (!$i)
			{
				_style('table');
				
				foreach ($cols as $j => $field)
				{
					if (!$j)
					{
						_style('table.head', array(
							'TITLE' => '#')
						);
					}
					
					_style('table.head', array(
						'TITLE' => $field)
					);
				}
			}
			
			_style('table.row');
			
			$j = 0;
			foreach ($rowm as $f => $row)
			{
				if (!$j)
				{
					_style('table.row.col', array(
						'VALUE' => ($i + 1))
					);
				}
				
				switch ($f)
				{
					case 'user_birthday':
						$row_year = substr($row, 0, 4);
						$row_month = substr($row, 4, 2);
						$row_day = substr($row, 6, 2);
						
						$row = _format_date(_timestamp($row_month, $row_day, $row_year), 'd F Y');
						break;
				}
			
				_style('table.row.col', array(
					'VALUE' => $row)
				);
				$j++;
			}
		}
		
		return;
	}
}

?>
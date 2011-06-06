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

class __import extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	public function home()
	{
		$sql = 'SELECT *
			FROM _store s, _store_fields f
			WHERE s.store_field = f.field_id
			ORDER BY store_field';
		$store = _rowset($sql, 'store_assoc', false, true);
		
		$a_country = array(
			'Alemania' => 82,
			'Argentina' => 10,
			'Bolivia' => 26,
			'Chile' => 43,
			'Colombia' => 47,
			'Ecuador' => 63,
			'El Salvador' => 65,
			'Espana' => 197,
			'Francia' => 74,
			'Greece' => 85,
			'Guatemala' => 90,
			'Honduras' => 97,
			'Italia' => 107,
			'Mexico' => 140,
			'Paraguay' => 168,
			'Peru' => 169,
			'Uruguay' => 227,
			'Venezuela' => 230
		);
		
		foreach ($store as $i => $row)
		{
			$nickname = '';
			$address = '';
			$birthday = '';
			$country = '';
			
			foreach ($row as $field)
			{
				switch ($field['field_alias'])
				{
					case 'nickname':
						$nickname = $field['store_value'];
						break;
					case 'address':
						$address = $field['store_value'];
						break;
					case 'birthday':
						$temp = explode('/', $field['store_value']);
						
						$birthday = $temp[2] . '' . $temp[1] . '' . $temp[0];
						break;
					case 'country':
						$country = $a_country[$field['store_value']];
						break;
				}
			}
			
			$sql_insert = array(
				'type' => 1,
				'active' => 0,
				'username' => $nickname,
				'password' => '',
				'registration' => time(),
				'lastvisit' => 0,
				'lastpage' => '',
				'country' => $country,
				'email' => $address,
				'birthday' => $birthday,
				'birthday_last' => 0,
				'gender' => 0,
				'date' => 0,
				'dateformat' => 'd M Y H:i',
				'timezone' => -6,
				'dst' => 0,
				'login_tries' => 0
			);
			$sql = 'INSERT INTO _members' . _build_array('INSERT', prefix('user', $sql_insert));
			_sql($sql);
 			//_pre($sql);
		}
		
		$this->e('Done.');
		
		return;
	}
}

?>
<?php
/*
$Id: _address.php,v 1.0 2009/01/30 09:23:00 Psychopsia Exp $

<Ximod, a web development framework.>
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

class __address extends xmd
{
	var $methods = array(
		'lastvisit' => array()
	);
	
	function home()
	{
		$sv = array(
			'USERNAME' => '',
			'ADDRESS' => ''
		);
		
		if (_button())
		{
			$v = $this->__(array('username', 'address', 'public_email'));
			
			$v['username'] = _alias($v['username']);
			
			$sql = "SELECT user_id, username, user_email, user_public_email
				FROM _members
				WHERE username_base = '" . $this->_escape($v['username']) . "'";
			if (!$userdata = $this->_fieldrow($sql))
			{
				$this->e('El usuario no existe.');
			}
			
			if (!empty($v['address']))
			{
				$sql_update = array('user_email' => $v['address']);
				
				if (!empty($v['public_email']))
				{
					$sql_update['user_public_email'] = $v['public_email'];
				}
				
				$sql = "UPDATE _members
					SET " . $this->_build_array('UPDATE', $sql_update) . "
					WHERE user_id = " . (int) $userdata['user_id'];
				$this->_sql($sql);
				
				$this->e($userdata['username'] . ' . ' . $userdata['user_email'] . ' . ' . $v['address']);
			}
			
			$sv = array(
				'USERNAME' => $userdata['username'],
				'ADDRESS' => $userdata['user_email'],
				'PUBLIC' => $userdata['user_public_email']
			);
		}
		
		$this->as_vars($sv);
		
		return;
	}
	
	function lastvisit()
	{
		$this->method();
	}
	
	function _lastvisit_home()
	{
		global $user;
		
		$v = $this->__(array('username'));
		
		$v['username'] = _alias($v['username']);
		
		$sql = "SELECT user_lastvisit
			FROM _members
			WHERE username_base = '" . $this->_escape($v['username']) . "'";
		if (!$lastvisit = $this->_field($sql, 'user_lastvisit'))
		{
			$this->e('El usuario no existe.');
		}
		
		$this->e($user->format_date($lastvisit));
	}
}

?>
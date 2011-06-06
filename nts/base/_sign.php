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

class __sign extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('in out')));
		$this->auth(false);
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function in()
	{
		global $user, $core;
		
		if ($user->v('is_member'))
		{
			redirect(_link());
		}
		
		if (_button())
		{
			$v = $this->__(w('username password lastpage'));
			
			$userdata = w();
			if (!f($v['username']) || !f($v['password']) || !preg_match('#^([a-z0-9\_\-]+)$#is', $v['username']))
			{
				$this->error('LOGIN_ERROR');
			}
			
			if (!$this->errors())
			{
				$v['username'] = array_key(explode('@', $v['username']), 0);
				
				$sql = 'SELECT *
					FROM _members
					WHERE user_username = ?
						AND user_id <> ?
						AND user_active = 1';
				if (!$userdata = _fieldrow(sql_filter($sql, $v['username'], U_GUEST)))
				{
					$this->error('LOGIN_ERROR');
				}
				
				if (!$this->errors())
				{
					if (!$core->v('signin_pop'))
					{
						if (isset($userdata['user_password']) && $userdata['user_password'] === _password($v['password']))
						{
							$user->session_create($userdata['user_id']);
							redirect($v['lastpage']);
						}
						
						$this->error('LOGIN_ERROR');
					}
					else
					{
						require_once(XFS . 'core/pop3.php');
						$pop3 = new pop3();
						
						if (!$pop3->connect($core->v('mail_server'), $core->v('mail_port')))
						{
							$this->error('LOGIN_ERROR');
						}
						
						if (!$this->errors() && !$pop3->user($v['username']))
						{
							$this->error('LOGIN_ERROR');
						}
						
						if (!$this->errors() && !$pop3->pass($v['password'], false))
						{
							$this->error('LOGIN_ERROR');
						}
						
						$pop3->quit();
						
						if (!$this->errors())
						{
							$user->session_create($userdata['user_id']);
							redirect($v['lastpage']);
						}
					}
				}
			}
		}
		
		_login(false, $this->get_errors());
	}
	
	public function out()
	{
		global $user;
		
		if (!$user->v('is_member'))
		{
			redirect(_link());
		}
		
		$user->session_kill();
		$user->v('is_member', false);
		$user->v('session_page', '');
		$user->v('session_time', time());
		
		_login('LOGGED_OUT');
	}
}

?>
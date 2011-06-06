<?php
/*
$Id: v 1.5 2009/08/03 11:25:00 $

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

class session
{
	protected $cookie_data = array();
	protected $data = array();
	
	public $session_id = '';
	public $browser = '';
	public $ip = '';
	public $i_ip = '';
	public $page = '';
	public $time = 0;
	
	function start($update_page = true)
	{
		global $core;
		
		$this->time = time();
		$this->browser = v_server('HTTP_USER_AGENT');
		$this->page = _page();
		$this->ip = htmlspecialchars(v_server('REMOTE_ADDR'));
		
		$i_ip = htmlspecialchars(v_server('HTTP_X_FORWARDED_FOR'));
		$this->i_ip = ($i_ip != '') ? $i_ip : $this->ip;
		
		if ($pos_ip = strpos($this->i_ip, ','))
		{
			$this->i_ip = substr($this->i_ip, 0, $pos_ip);
		}
		
		if (array_strpos($this->page, w('ext')) !== false)
		{
			$update_page = false;
		}
		
		$this->cookie_data = w();
		if (isset($_COOKIE[$core->v('cookie_name') . '_sid']) || isset($_COOKIE[$core->v('cookie_name') . '_u']))
		{
			$this->cookie_data['u'] = request_var($core->v('cookie_name') . '_u', 0);
			$this->session_id = request_var($core->v('cookie_name') . '_sid', '');
		}
		
		// Is session_id is set
		if (!empty($this->session_id))
		{
			$sql = "SELECT m.*, s.*
				FROM _sessions s, _members m
				WHERE s.session_id = ?
					AND m.user_id = s.session_user_id";
			$this->data = _fieldrow(sql_filter($sql, $this->session_id));
			
			// Did the session exist in the DB?
			if (isset($this->data['user_id']))
			{
				$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, 4));
				$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, 4));
				
				if ($u_ip == $s_ip && $this->data['session_browser'] == $this->browser)
				{
					// Only update session DB a minute or so after last update or if page changes
					if ($this->time - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->page)
					{
						$sql_update = array('session_time' => $this->time);
						if ($update_page)
						{
							$sql_update['session_page'] = $this->page;
						}
						
						$sql = 'UPDATE _sessions SET ' . _build_array('UPDATE', $sql_update) . sql_filter('
							WHERE session_id = ?', $this->session_id);
						_sql($sql);
					}
					
					if ($update_page)
					{
						$this->data['session_page'] = $this->page;
					}
					
					// Ultimately to be removed
					$this->data['is_member'] = ($this->data['user_id'] != U_GUEST) ? true : false;
					$this->data['is_founder'] = ($this->data['user_id'] != U_GUEST && $this->data['user_type'] == U_FOUNDER) ? true : false;
					$this->data['is_bot'] = false;
					
					if ($this->data['is_member'])
					{
						return true;
					}
				}
			}
		}
		
		// If we reach here then no (valid) session exists. So we'll create a new one
		return $this->session_create(false, $update_page);
	}
	
	/**
	* Create a new session
	*
	* If upon trying to start a session we discover there is nothing existing we
	* jump here. Additionally this method is called directly during login to regenerate
	* the session for the specific user. In this method we carry out a number of tasks;
	* garbage collection, (search)bot checking, banned user comparison. Basically
	* though this method will result in a new session for a specific user.
	*/
	function session_create($user_id = false, $update_page = true)
	{
		global $core;
		
		$this->data = w();
		
		// Garbage collection ... remove old sessions updating user information
		// if necessary. It means (potentially) 11 queries but only infrequently
		if ($this->time > $core->v('session_last_gc') + $core->v('session_gc'))
		{
			$this->session_gc();
		}
		
		// If we've been passed a user_id we'll grab data based on that
		if ($user_id !== false)
		{
			$this->cookie_data['u'] = $user_id;
			
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ?
					AND user_type <> ?';
			$this->data = _fieldrow(sql_filter($sql, $this->cookie_data['u'], 2));
		}
		
		// If no data was returned one or more of the following occured:
		// User does not exist
		// User is inactive
		// User is bot
		if (!count($this->data) || !is_array($this->data))
		{
			$this->cookie_data['u'] = U_GUEST;
			
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ?';
			$this->data = _fieldrow(sql_filter($sql, $this->cookie_data['u']));
		}
		
		if ($this->data['user_id'] != U_GUEST)
		{
			$sql = 'SELECT session_time, session_id
				FROM _sessions
				WHERE session_user_id = ?
				ORDER BY session_time DESC
				LIMIT 1';
			if ($sdata = _fieldrow(sql_filter($sql, $this->data['user_id'])))
			{
				$this->data = array_merge($sdata, $this->data);
				unset($sdata);
				$this->session_id = $this->data['session_id'];
  		}
			
			$this->data['session_last_visit'] = (isset($this->data['session_time']) && $this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : $this->time);
		}
		else
		{
			$this->data['session_last_visit'] = $this->time;
		}
		
		// At this stage we should have a filled data array, defined cookie u and k data.
		// data array should contain recent session info if we're a real user and a recent
		// session exists in which case session_id will also be set
		
		//
		// Do away with ultimately?
		$this->data['is_member'] = ($this->data['user_id'] != U_GUEST) ? true : false;
		$this->data['is_founder'] = ($this->data['user_id'] != U_GUEST && $this->data['user_type'] == U_FOUNDER) ? true : false;
		$this->data['is_bot'] = false;
		
		// Create or update the session
		$sql_ary = array(
			'session_user_id' => (int) $this->data['user_id'],
			'session_start' => (int) $this->time,
			'session_last_visit' => (int) $this->data['session_last_visit'],
			'session_time' => (int) $this->time,
			'session_browser' => (string) $this->browser,
			'session_ip' => (string) $this->ip
		);
		
		if ($update_page)
		{
			$sql_ary['session_page'] = (string) $this->page;
			$this->data['session_page'] = $sql_ary['session_page'];
		}
		
		$sql = 'UPDATE _sessions SET ' . _build_array('UPDATE', $sql_ary) . sql_filter('
			WHERE session_id = ?', $this->session_id);
		if (!$this->session_id || !_sql($sql) || !_affectedrows())
		{
			$this->session_id = $this->data['session_id'] = md5(unique_id());
			
			$sql_ary['session_id'] = (string) $this->session_id;
			
			$sql = 'INSERT INTO _sessions' . _build_array('INSERT', $sql_ary);
			_sql($sql);
		}
		
		$cookie_expire = $this->time + 31536000;
		$this->set_cookie('u', $this->cookie_data['u'], $cookie_expire);
		$this->set_cookie('sid', $this->session_id, 0);
		
		unset($cookie_expire);
		
		return true;
	}
	
	/**
	* Kills a session
	*
	* This method does what it says on the tin. It will delete a pre-existing session.
	* It resets cookie information and update the users information from the relevant
	* session data. It will then grab guest user information.
	*/
	function session_kill()
	{
		$sql = 'DELETE FROM _sessions
			WHERE session_id = ?
				AND session_user_id = ?';
		_sql(sql_filter($sql, $this->session_id, $this->data['user_id']));
		
		if ($this->data['user_id'] != U_GUEST)
		{
			// Delete existing session, update last visit info first!
			$sql = 'UPDATE _members
				SET user_lastvisit = ?
				WHERE user_id = ?';
			_sql(sql_filter($sql, $this->data['session_time'], $this->data['user_id']));
			
			// Reset the data array
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ?';
			$this->data = _fieldrow(sql_filter($sql, U_GUEST));
		}
		
		$cookie_expire = $this->time - 31536000;
		$this->set_cookie('u', '', $cookie_expire);
		$this->set_cookie('sid', '', $cookie_expire);
		unset($cookie_expire);
		
		$this->session_id = '';
		
		return true;
	}
	
	/**
	* Session garbage collection
	*
	* Effectively we are deleting any sessions older than an admin definable 
	* limit. Due to the way in which we maintain session data we have to 
	* ensure we update user data before those sessions are destroyed. 
	* In addition this method removes autologin key information that is older 
	* than an admin defined limit.
	*/
	function session_gc()
	{
		global $core;
		
		// Get expired sessions, only most recent for each user
		$sql = 'SELECT session_user_id, session_page, MAX(session_time) AS recent_time
			FROM _sessions
			WHERE session_time < ' . ($this->time - $core->v('session_length')) . '
			GROUP BY session_user_id, session_page
			LIMIT 5';
		$sessions = _rowset($sql);
		
		$del_user_id = '';
		$del_sessions = 0;
		foreach ($sessions as $row)
		{
			if ($row['session_user_id'] != U_GUEST)
			{
				$sql = 'UPDATE _members
					SET user_lastvisit = ?, user_lastpage = ?
					WHERE user_id = ?';
				_sql(sql_filter($sql, $row['recent_time'], $row['session_page'], $row['session_user_id']));
			}
			
			$del_user_id .= (($del_user_id != '') ? ', ' : '') . (int) $row['session_user_id'];
			$del_sessions++;
		}
		
		if ($del_user_id != '')
		{
			// Delete expired sessions
			$sql = 'DELETE FROM _sessions
				WHERE session_user_id IN (??)
					AND session_time < ?';
			_sql(sql_filter($sql, $del_user_id, ($this->time - $core->v('session_length'))));
		}
		
		if ($del_sessions < 5)
		{
			// Less than 5 sessions, update gc timer ... else we want gc
			// called again to delete other sessions
			$core->v('session_last_gc', $this->time);
		}

		return;
	}
	
	/**
	* Sets a cookie
	*
	* Sets a cookie of the given name with the specified data for the given length of time.
	*/
	function set_cookie($name, $cookiedata, $cookietime, $onlyhttp = false)
	{
		global $core;
		
		$name = $core->v('cookie_name') . '_' . $name;
		$domain = ($core->v('cookie_domain') != 'localhost') ? $core->v('cookie_domain') : '';
		$secure = (v_server('SERVER_PORT') === 443) ? true : false;
		
		setcookie($name, $cookiedata, $cookietime, $core->v('cookie_path'), $domain, $secure, $onlyhttp);
	}
	
	function v($d = false, $v = false)
	{
		if ($d === false)
		{
			$r = $this->data;
			
			if (!$this->data)
			{
				return false;
			}
			
			unset($r['user_password']);
			return $r;
		}
		
		if ($v !== false)
		{
			$this->data[$d] = $v;
		}
		
		return (isset($this->data[$d])) ? $this->data[$d] : false;
	}
	
	public function replace($a)
	{
		$this->data = $a;
		return true;
	}
}

/**
* Base user class
*
* This is the overarching class which contains (through session extend)
* all methods utilised for user functionality during a session.
*/
class user extends session
{
	public $lang = array();	
	public $auth;
	public $date_format, $timezone, $dst;
	
	function setup()
	{
		global $style, $core;
		
		$this->data['user_lang'] = $core->v('default_lang');
		$this->date_format = $this->v('user_dateformat');
		$this->timezone = $this->v('user_timezone') * 3600;
		$this->dst = $this->v('user_dst') * 3600;
		
		// Load global language
		$this->load_lang('main');
		
		// Load UI template
		$style->set_template('./style');
		return;
	}
	
	function load_lang($f, $d = false)
	{
		$lang = w();
		
		if ($d === false)
		{
			global $core;
			
			$d = $core->v('default_lang');
		}
		
		$filepath = './base/lang/' . $d . '/' . $f . '.php';
		if (@file_exists($filepath))
		{
			require_once($filepath);
		}
		
		$this->lang += $lang;
		return $lang;
	}
	
	function time_diff($timestamp, $detailed = false, $n = 0)
	{
		// If the difference is positive "ago" - negative "away"
		$now = time();
		$action = ($timestamp >= $now) ? 'away' : 'ago';
		$diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
		
		// Set the periods of time
		$periods = w('s m h d s m a');
		$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560);
		
		// Go from decades backwards to seconds
		$result = w();
		
		$i = count($lengths);
		$time = '';
		while ($i >= $n)
		{
			$item = $lengths[$i - 1];
			if ($diff < $item)
			{
				$i--;
				continue;
			}
			
			$val = floor($diff / $item);
			$diff -= ($val * $item);
			$result[] = $val . $periods[($i - 1)];
			
			if (!$detailed)
			{
				$i = 0;
			}
			$i--;
		}
		
		return (count($result)) ? $result : false;
	}
	
	function format_date($gmepoch, $format = false, $forcedate = false)
	{
		static $lang_dates, $midnight;
		
		if (empty($lang_dates))
		{
			foreach ($this->lang['datetime'] as $match => $replace)
			{
				$lang_dates[$match] = $replace;
			}
		}
		
		$format = (!$format) ? $this->date_format : $format;
		
		if (!$midnight)
		{
			list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
		}
		
		if ((strpos($format, '\M') === false && strpos($format, 'M') !== false) || (strpos($format, '\r') === false && strpos($format, 'r') !== false))
		{
			$lang_dates['May'] = $this->lang['datetime']['May_short'];
		}
		
		if ($forcedate != false)
		{
			$a = $this->time_diff($gmepoch, 1, 2);
			if ($a !== false)
			{
				if (count($a) < 4)
				{
					return implode(' ', $a);
				}
			}
			else
			{
				return _lang('AGO_LESS_MIN');
			}
		}
		
		return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $lang_dates);
	}
	
	function _groups()
	{
		global $core;
		
		if (!$groups = $core->cache_load('groups'))
		{
			$sql = 'SELECT *
				FROM _groups
				ORDER BY group_name';
			$groups = $core->cache_store(_rowset($sql, 'group_id'));
		}
		return $groups;
	}
	
	function auth_founder($uid)
	{
		global $core;
		
		if (!$founders = $core->cache_load('founders'))
		{
			$sql = 'SELECT user_id
				FROM _members
				WHERE user_type = ?
					AND user_active = 1';
			$founders = $core->cache_store(_rowset(sql_filter($sql, U_FOUNDER), 'user_id'));
		}
		
		return (is_array($founders) && in_array($uid, array_keys($founders)));
	}
	
	function auth_groups($uid = false)
	{
		if ($uid === false)
		{
			$uid = $this->v('user_id');
		}
		
		$groups = w();
		if ($this->auth_founder($uid))
		{
			$groups = array_keys($this->_groups());
		}
		
		if (!count($groups))
		{
			$sql = 'SELECT g.group_id
				FROM _groups g, _groups_members gm
				WHERE g.group_id = gm.member_group
					AND gm.member_uid = ?';
			$groups = _rowset(sql_filter($sql, $uid), false, 'group_id');
		}
		
		return _implode(',', $groups);
	}
	
	function auth_list()
	{
		global $core;
		
		if (!$fields = $core->cache_load('auth_fields'))
		{
			$sql = 'SELECT *
				FROM _members_auth_fields
				ORDER BY field_alias';
			$fields = $core->cache_store(_rowset($sql, 'field_id'));
		}
		
		return $fields;
	}
	
	function auth($k, $v = -1, $uid = false)
	{
		global $user;
		
		if ($uid === false)
		{
			$uid = $user->v('user_id');
		}
		
		if ($v !== -1)
		{
			$this->auth[$uid][$k] = $v;
		}
		
		return (isset($this->auth[$uid][$k])) ? $this->auth[$uid][$k] : false;
	}
	
	function auth_replace($orig, $repl, $uid = false)
	{
		if (!$this->auth_get($repl, $uid))
		{
			return false;
		}
		
		if ($uid === false)
		{
			$uid = $this->v('user_id');
		}
		
		$auth_fields = $this->auth_list();
		
		$this->auth[$uid][$orig] = true;
		
		return $this->auth_get($orig, $uid);
	}
	
	function auth_get($name, $uid = false, $global = false)
	{
		if ($uid === false)
		{
			$uid = $this->v('user_id');
		}
		
		if ($this->auth_founder($uid))
		{
			return true;
		}
		
		// Get auth fields
		$auth_fields = $this->auth_list();
		
		// Get all auth for uid
		if (!isset($this->auth[$uid]))
		{
			$this->auth[$uid] = w();
			
			$sql = 'SELECT *
				FROM _members_auth
				WHERE auth_uid = ?';
			$auth = _rowset(sql_filter($sql, $uid));
			
			foreach ($auth as $row)
			{
				if (!isset($row['auth_field']))
				{
					continue;
				}
				$this->auth[$uid][$auth_fields[$row['auth_field']]['field_alias']] = true;
			}
		}
		
		$name = _alias($name, w('-'));
		
		$response = false;
		if (isset($this->auth[$uid][$name]))
		{
			$response = true;
		}
		
		if ($response === false)
		{
			$field_found = false;
			foreach ($auth_fields as $row)
			{
				if ($name === $row['field_alias'])
				{
					if ($row['field_global'])
					{
						$response = true;
					}
					
					$field_found = true;
					break;
				}
			}
			
			if (!$field_found)
			{
				$sql_insert = array(
					'alias' => $name,
					'name' => $name,
					'global' => (int) $global
				);
				$sql = 'INSERT INTO _members_auth_fields' . _build_array('INSERT', prefix('field', $sql_insert));
				_sql($sql);
				
				global $core;
				
				$core->cache_unload();
				
				if ($global)
				{
					$response = true;
				}
			}
		}
		
		return $response;
	}
	
	function auth_update($f, $v = false, $uid = false)
	{
		global $core;
		
		if ($uid === false)
		{
			$uid = $this->v('user_id');
		}
		
		$field = $this->auth_field($f);
		if ($field !== false)
		{
			$cv = isset($this->auth[$uid][$field['field_alias']]);
			
			switch ($v)
			{
				case true:
					if ($cv)
					{
						return;
					}
					
					$sql_insert = array(
						'uid' => $uid,
						'field' => $field['field_id']
					);
					$sql = 'INSERT INTO _members_auth' . _build_array('INSERT', prefix('auth', $sql_insert));
					_sql($sql);
					
					$this->auth[$uid][$field['field_alias']] = true;
					break;
				case false:
					if (!$cv)
					{
						return;
					}
					
					$sql = 'DELETE FROM _members_auth
						WHERE auth_uid = ?
							AND auth_field = ?';
					_sql(sql_filter($sql, $uid, $field['field_id']));
					
					unset($this->auth[$uid][$field['field_alias']]);
					break;
			}
			
			$core->cache_unload();
		}
		
		return;
	}
	
	function auth_remove($f, $uid = false)
	{
		global $core;
		
		if ($uid === false)
		{
			$uid = $this->v('user_id');
		}
		
		$field = $this->auth_field($f);
		if ($field !== false)
		{
			if (!isset($this->auth[$uid][$field['field_alias']]))
			{
				return;
			}
			
			$sql = 'DELETE FROM _members_auth
				WHERE auth_uid = ?
					AND auth_field = ?';
			_sql(sql_filter($sql, $uid, $field['field_id']));
			
			unset($this->auth[$uid][$field['field_alias']]);
			$core->cache_unload();
		}
		
		return;
	}
	
	function auth_field($f)
	{
		$ff = (is_numb($f)) ? 'id' : 'alias';
		
		$sql = 'SELECT *
			FROM _members_auth_fields
			WHERE field_?? = ?';
		if (!$field = _fieldrow(sql_filter($sql, $ff, $f)))
		{
			return false;
		}
		return $field;
	}
}

class core
{
	var $cache = array();
	var $config = array();
	var $sf = array();
	
	var $cache_dir = '';
	var $cache_last = '';
	var $cache_f = false;
	
	function core()
	{
		$sql = 'SELECT *
			FROM _config';
		$this->config = _rowset($sql, 'config_name', 'config_value');
		
		if ($this->v('site_disabled'))
		{
			exit('SITE DISABLED');
		}
		
		$address = $this->v('address');
		$host_addr = array_key(explode('/', array_key(explode('://', $address), 1)), 0);
		
		if ($host_addr != get_host())
		{
			$allow_hosts = get_file('./base/domain_alias');
			
			foreach ($allow_hosts as $row)
			{
				if (substr($row, 0, 1) == '#') continue;
				
				$remote = (strpos($row, '*') === false);
				$row = (!$remote) ? str_replace('*', '', $row) : $row;
				$row = str_replace('www.', '', $row);
				
				if ($row == get_host())
				{
					$sub = str_replace($row, '', get_host());
					$sub = (f($sub)) ? $sub . '.' : ($remote ? 'www.' : '');
					
					$address = str_replace($host_addr, $sub . $row, $address);
					$this->v('address', $address, true);
					break;
				}
			}
		}
		
		if (strpos($address, 'www.') !== false && strpos(get_host(), 'www.') === false && strpos($address, get_host()))
 		{
			$a = $this->v('address') . str_replace(str_replace('www.', '', $address), '', _page());
			redirect($a, false);
		}
		
		$this->cache_dir = XFS . 'core/cache/';
		
		if (is_remote() && @file_exists($this->cache_dir) && @is_writable($this->cache_dir) && @is_readable($this->cache_dir))
		{
			$this->cache_f = true;
		}
		
		return;
	}
	
	function v($k, $v = false, $nr = false)
	{
		$a = (isset($this->config[$k])) ? $this->config[$k] : false;
		
		if ($nr !== false && $v !== false)
		{
			$this->config[$k] = $v;
			return $v;
		}
		
		if ($v !== false)
		{
			$sql_update = array('config_value' => $v);
			
			if ($a !== false)
			{
				$sql = 'UPDATE _config SET ' . _build_array('UPDATE', $sql_update) . sql_filter('
					WHERE config_name = ?', $k);
			}
			else
			{
				$sql_update['config_name'] = $k;
				$sql = 'INSERT INTO _config' . _build_array('INSERT', $sql_update);
			}
			_sql($sql);
			$this->config[$k] = $a = $v;
		}
		
		return $a;
	}
	
	// Used by template system $A[]
	function auth($a)
	{
		return _auth_get($a);
	}
	
	function _sf($a = false)
	{
		if ($a !== false)
		{
			$this->sf[] = $a;
		}
		
		if (!count($this->sf))
		{
			return false;
		}
		
		return $this->sf;
	}
	
	//
	// Cache data system
	//
	
	function cache_crypt($str)
	{
		return sha1($str);
	}
	
	function cache_check()
	{
		return $this->cache_f;
	}
	
	function cache_load($v, $force = false)
	{
		if (!$this->cache_check() && !$force)
		{
			return;
		}
		
		$filepath = $this->cache_dir . $this->cache_crypt($v);
		$this->cache_last = $v;
		
		if (!@file_exists($filepath))
		{
			return false;
		}
		
		// Cache expiration time
		if (time() - @filemtime($filepath) < 3600)
		{
			if ($plain = get_file($filepath))
			{
				return json_decode($plain[0], true);
			}
		}
		
		return $this->cache_unload($v);
	}
	
	function cache_unload()
	{
		if (!$this->cache_check())
		{
			return;
		}
		
		$files = w();
		if ($a = func_get_args())
		{
			foreach ($a as $row)
			{
				if (!f($row)) continue;
				
				$files[] = $this->cache_crypt($row);
			}
		}
		else
		{
			$files = _dirlist($this->cache_dir, '^([a-z0-9]+)$', 'files');
		}
		
		foreach ($files as $row)
		{
			$row = $this->cache_dir . $row;
			if (@file_exists($row))
			{
				@unlink($row);
			}
		}
		return false;
	}
	
	function cache_store($v, $k = false, $force = false)
	{
		if (!$this->cache_check() && !$force)
		{
			return $v;
		}
		
		$k = ($k === false) ? $this->cache_last : $k;
		
		if (!f($k)) return;
		
		$this->cache_unload($k);
		$filepath = $this->cache_dir . $this->cache_crypt($k);
		
		if ($fp = @fopen($filepath, 'w'))
		{
			if (@flock($fp, LOCK_EX))
			{
				fputs($fp, json_encode($v));
				@flock($fp, LOCK_UN);
			}
			
			fclose($fp);
			@chmod($filepath, 0777);
		}
		return $v;
	}
}

/*
Code from: kexianbin at diyism dot com
http://www.php.net/manual/en/language.oop5.overloading.php#93072

By using __call, we can use php as using jQuery.
*/
define('this', mt_rand());
define('echo', '_echo');

class fff
{
	function __call($fun, $pars)
	{
		if (!count($pars))
		{
			$pars = array(this);
		}
		
		foreach ($pars as &$v)
		{
			if ($v === this)
			{
				$v = $this->val;
				break;
			}
		}
		
		$tmp = eval(sprintf('return defined("%1$s") ? constant("%1$s") : "%1$s";', $fun));
		if ($tmp == 'x')
		{
			return $this->val;
		}
		
		$this->val = @hook($tmp, $pars);
		return $this;
	}
	
	function __construct($a = null)
	{
		$this->val = isset($a) ? $a : null;
	}
}

?>